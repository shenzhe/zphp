<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Fcgi;
use ZPHP\ZPHP;

class Client
{
    const VERSION_1 = 1;
    const BEGIN_REQUEST = 1;
    const ABORT_REQUEST = 2;
    const END_REQUEST = 3;
    const PARAMS = 4;
    const STDIN = 5;
    const STDOUT = 6;
    const STDERR = 7;
    const DATA = 8;
    const GET_VALUES = 9;
    const GET_VALUES_RESULT = 10;
    const UNKNOWN_TYPE = 11;
    const MAXTYPE = self::UNKNOWN_TYPE;

    const RESPONDER = 1;
    const AUTHORIZER = 2;
    const FILTER = 3;

    const REQUEST_COMPLETE = 0;
    const CANT_MPX_CONN = 1;
    const OVERLOADED = 2;
    const UNKNOWN_ROLE = 3;

    const MAX_CONNS = 'MAX_CONNS';
    const MAX_REQS = 'MAX_REQS';
    const MPXS_CONNS = 'MPXS_CONNS';

    const HEADER_LEN = 8;

    private $_sock = null;
    private $_host = null;
    private $_port = null;
    private $_keepAlive = false;

    public function __construct($host = '127.0.0.1', $port = 9000)
    {
        $this->_host = $host;
        $this->_port = $port;
    }

    public function setKeepAlive($b)
    {
        $this->_keepAlive = (boolean)$b;
        if (!$this->_keepAlive && $this->_sock) {
            fclose($this->_sock);
        }
    }

    public function getKeepAlive()
    {
        return $this->_keepAlive;
    }

    private function connect()
    {
        if (!$this->_sock) {
            $this->_sock = fsockopen($this->_host, $this->_port, $errno, $errstr, 5);
            if (!$this->_sock) {
                throw new \Exception('Unable to connect to FastCGI application');
            }
        }
    }

    private function buildPacket($type, $content, $requestId = 1)
    {
        $clen = strlen($content);
        return chr(self::VERSION_1) /* version */
        . chr($type) /* type */
        . chr(($requestId >> 8) & 0xFF) /* requestIdB1 */
        . chr($requestId & 0xFF) /* requestIdB0 */
        . chr(($clen >> 8) & 0xFF) /* contentLengthB1 */
        . chr($clen & 0xFF) /* contentLengthB0 */
        . chr(0) /* paddingLength */
        . chr(0) /* reserved */
        . $content; /* content */
    }

    private function buildNvpair($name, $value)
    {
        $nlen = strlen($name);
        $vlen = strlen($value);
        if ($nlen < 128) {
            $nvpair = chr($nlen);
        } else {
            $nvpair = chr(($nlen >> 24) | 0x80) . chr(($nlen >> 16) & 0xFF) . chr(($nlen >> 8) & 0xFF) . chr($nlen & 0xFF);
        }
        if ($vlen < 128) {
            $nvpair .= chr($vlen);
        } else {
            $nvpair .= chr(($vlen >> 24) | 0x80) . chr(($vlen >> 16) & 0xFF) . chr(($vlen >> 8) & 0xFF) . chr($vlen & 0xFF);
        }
        return $nvpair . $name . $value;
    }

    private function readNvpair($data, $length = null)
    {
        $array = array();

        if ($length === null) {
            $length = strlen($data);
        }

        $p = 0;

        while ($p != $length) {

            $nlen = ord($data{$p++});
            if ($nlen >= 128) {
                $nlen = ($nlen & 0x7F << 24);
                $nlen |= (ord($data{$p++}) << 16);
                $nlen |= (ord($data{$p++}) << 8);
                $nlen |= (ord($data{$p++}));
            }
            $vlen = ord($data{$p++});
            if ($vlen >= 128) {
                $vlen = ($nlen & 0x7F << 24);
                $vlen |= (ord($data{$p++}) << 16);
                $vlen |= (ord($data{$p++}) << 8);
                $vlen |= (ord($data{$p++}));
            }
            $array[substr($data, $p, $nlen)] = substr($data, $p + $nlen, $vlen);
            $p += ($nlen + $vlen);
        }

        return $array;
    }

    private function decodePacketHeader($data)
    {
        $ret = array();
        $ret['version'] = ord($data{0});
        $ret['type'] = ord($data{1});
        $ret['requestId'] = (ord($data{2}) << 8) + ord($data{3});
        $ret['contentLength'] = (ord($data{4}) << 8) + ord($data{5});
        $ret['paddingLength'] = ord($data{6});
        $ret['reserved'] = ord($data{7});
        return $ret;
    }

    private function readPacket()
    {
        if ($packet = fread($this->_sock, self::HEADER_LEN)) {
            $resp = $this->decodePacketHeader($packet);
            $resp['content'] = '';
            if ($resp['contentLength']) {
                $len = $resp['contentLength'];
                while ($len && $buf = fread($this->_sock, $len)) {
                    $len -= strlen($buf);
                    $resp['content'] .= $buf;
                }
            }
            if ($resp['paddingLength']) {
                $buf = fread($this->_sock, $resp['paddingLength']);
                $resp['content'] .= $buf;
            }
            return $resp;
        } else {
            return false;
        }
    }

    public function getValues(array $requestedInfo)
    {
        $this->connect();

        $request = '';
        foreach ($requestedInfo as $info) {
            $request .= $this->buildNvpair($info, '');
        }
        fwrite($this->_sock, $this->buildPacket(self::GET_VALUES, $request, 0));

        $resp = $this->readPacket();
        if ($resp['type'] == self::GET_VALUES_RESULT) {
            return $this->readNvpair($resp['content'], $resp['length']);
        } else {
            throw new \Exception('Unexpected response type, expecting GET_VALUES_RESULT');
        }
    }

    public function request(array $url, $stdin = false)
    {
        $params = array(
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_FILENAME' => isset($url['SCRIPT_NAME']) ? $url['SCRIPT_NAME'] : ZPHP::getRootPath() . DS . 'webroot' . DS . 'main.php',
            'SCRIPT_NAME' => isset($url['SCRIPT_NAME']) ? $url['SCRIPT_NAME'] : DS . 'main.php',
            'DOCUMENT_URI' => isset($url['DOCUMENT_URI']) ? $url['DOCUMENT_URI'] : DS . 'main.php',
            'HTTP_HOST' => isset($url['HTTP_HOST']) ? $url['HTTP_HOSTI'] : 'default',
            'QUERY_STRING' => $url['query'],
            'REQUEST_URI' => DS . $url['query'],
            'SERVER_SOFTWARE' => 'zphp',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => '9985',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => '80',
            'SERVER_NAME' => php_uname('n'),
            'CONTENT_TYPE' => '',
            'CONTENT_LENGTH' => 0,
            'REQUEST_TIME' => time()
        );
        $response = '';
        $this->connect();

        $request = $this->buildPacket(self::BEGIN_REQUEST, chr(0) . chr(self::RESPONDER) . chr((int)$this->_keepAlive) . str_repeat(chr(0), 5));

        $paramsRequest = '';
        foreach ($params as $key => $value) {
            $paramsRequest .= $this->buildNvpair($key, $value);
        }
        if ($paramsRequest) {
            $request .= $this->buildPacket(self::PARAMS, $paramsRequest);
        }
        $request .= $this->buildPacket(self::PARAMS, '');

        if ($stdin) {
            $request .= $this->buildPacket(self::STDIN, $stdin);
        }
        $request .= $this->buildPacket(self::STDIN, '');

        fwrite($this->_sock, $request);

        do {
            $resp = $this->readPacket();
            if ($resp['type'] == self::STDOUT || $resp['type'] == self::STDERR) {
                $response .= $resp['content'];
            }
        } while ($resp && $resp['type'] != self::END_REQUEST);

        if (!is_array($resp)) {
            throw new \Exception('Bad request');
        }
        switch (ord($resp['content']{4})) {
            case self::CANT_MPX_CONN:
                throw new \Exception('This app can\'t multiplex [CANT_MPX_CONN]');
                break;
            case self::OVERLOADED:
                throw new \Exception('New request rejected; too busy [OVERLOADED]');
                break;
            case self::UNKNOWN_ROLE:
                throw new \Exception('Role value not known [UNKNOWN_ROLE]');
                break;
            case self::REQUEST_COMPLETE:
                list($header, $content) = explode("\r\n\r\n", $response, 2);
                return array(
                    'header' => $header,
                    'content' => $content
                );
        }
    }
}