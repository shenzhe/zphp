<?php

/**
 *  依赖于 httpparser扩展
 *  git地址：https://github.com/matyhtf/php-webserver/tree/master/ext
 */

namespace ZPHP\Socket\Callback;

use ZPHP\Socket\ICallback;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Protocol;
use ZPHP\Core;
use \HttpParser;
use ZPHP\Conn\Factory as ZConn;


class HttpServer implements ICallback
{

    private $_cache;
    public function onStart()
    {
        echo 'server start, swoole version: ' . SWOOLE_VERSION . PHP_EOL;
    }

    public function onConnect()
    {
        
        $params = func_get_args();
        $fd = $params[1];
        //echo "{$fd} connected".PHP_EOL;
        
    }

    /**
     *  请求发起
     */
    public function onReceive()
    {
        $params = func_get_args();
        $_data = $params[3];
        $serv = $params[0];
        $fd = $params[1];
        $parser = new HttpParser();
        $buffer = $this->cache->getBuff($fd);
        $nparsed = (int) $this->cache->getBuff($fd, 'nparsed');
        $buffer .= $_data;
        $nparsed = $parser->execute($buffer, $nparsed);
        if($parser->hasError()) {
            $serv->close($fd, $params[2]);
            $this->_clearBuff($fd);
        } elseif ($parser->isFinished()) {
            $this->_clearBuff($fd);
            $result = $this->_route($this->_getData($parser->getEnvironment()));
            $this->sendOne($serv, $fd, $result);
        } else {
            $buffer = $this->cache->setBuff($fd, $buffer);
            $nparsed = (int) $this->cache->setBuff($fd, $nparsed, 'nparsed');
        }
    }

    private function _getData($data)
    {
        $_SERVER = $data;
        switch ($data['REQUEST_METHOD']) {
            case 'POST':
                parse_str($data['QUERY_STRING'].'&'.$data['REQUEST_BODY'], $param);
                return $param;
                break;
            case 'PUT':
                break;
            case 'DELETE':
                break;
            default:   //GET
                if(empty($data['QUERY_STRING'])) {
                    return array();
                }

                parse_str($data['QUERY_STRING'], $param);
                return $param;
                break;
        }
    }

    private function _clearBuff($fd)
    {
        $this->cache->delBuff($fd, 'nparsed');
        $this->cache->delBuff($fd);
        return true;
    }

    public function onClose()
    {
        $params = func_get_args();
        //$fd = $params[1];
        //echo "{$fd} closed".PHP_EOL;   
        $this->cache->delBuff($params[1]);
    }

    public function onShutdown()
    {
        //echo "server shut dowm\n";
        $this->cache->clear();
    }

    /**
     * @param $serv
     * @param $fd
     * @param $data
     * @return bool
     *  支持返回json数据
     */
    public function sendOne($serv, $fd, $data)
    {
        $response = join(
            "\r\n",
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html; charset=utf-8',
                'Connection: keep-alive',
                'Server:zserver 0.1',
                'Content-Length: '.strlen($data),
                'Date: '. gmdate("D, d M Y H:i:s T"),
                '',
                $data));
        $serv->send($fd, $response);
        //$serv->close($fd);
    }


    private function _route($data)
    {
        try {
            $server = Protocol\Factory::getInstance('Http');
            $server->parse($data);
            \ob_start();
            Core\Route::route($server);
            $result = \ob_get_contents();
            \ob_end_clean();
            return $result;
        } catch (\Exception $e) {
            //print_r($e);
            return null;
        }
    }


    public function onWorkerStart()
    {
        $params = func_get_args();
        $worker_id = $params[1];
        //echo "WorkerStart[$worker_id]|pid=" . posix_getpid() . ".\n";
        $config = ZConfig::getField('cache', 'locale');
        $this->cache = ZConn::getInstance($config['adapter'], $config);

    }

    public function onWorkerStop()
    {
        /*
        $params = func_get_args();
        $worker_id = $params[1];
        echo "WorkerStop[$worker_id]|pid=" . posix_getpid() . ".\n";
        */
    }
}