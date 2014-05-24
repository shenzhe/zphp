<?php

/**
 *
 */

namespace ZPHP\Socket\Callback;

use ZPHP\Socket\ICallback;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Protocol;
use ZPHP\Core;
use \HttpParser;
use ZPHP\Conn\Factory as ZConn;
use ZPHP\Socket\Route;


abstract class WSServer implements ICallback
{
    const OPCODE_CONTINUATION_FRAME = 0x0;
    const OPCODE_TEXT_FRAME         = 0x1;
    const OPCODE_BINARY_FRAME       = 0x2;
    const OPCODE_CONNECTION_CLOSE   = 0x8;
    const OPCODE_PING               = 0x9;
    const OPCODE_PONG               = 0xa;

    const CLOSE_NORMAL              = 1000;
    const CLOSE_GOING_AWAY          = 1001;
    const CLOSE_PROTOCOL_ERROR      = 1002;
    const CLOSE_DATA_ERROR          = 1003;
    const CLOSE_STATUS_ERROR        = 1005;
    const CLOSE_ABNORMAL            = 1006;
    const CLOSE_MESSAGE_ERROR       = 1007;
    const CLOSE_POLICY_ERROR        = 1008;
    const CLOSE_MESSAGE_TOO_BIG     = 1009;
    const CLOSE_EXTENSION_MISSING   = 1010;
    const CLOSE_SERVER_ERROR        = 1011;
    const CLOSE_TLS                 = 1015;

    const WEBSOCKET_VERSION         = 13;
    /**
     * GUID.
     *
     * @const string
     */
    const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    public $_ws = array();
    private $_route;
    private $_buff = array();
    private $_ws_list = array();
    public $serv;

    abstract public function wsOnOpen($fd);
    abstract public function wsOnMessage($fd, $ws);
    abstract public function wsOnClose($fd);

    public function onStart()
    {
        $this->log('server start, swoole version: ' . SWOOLE_VERSION);
    }

    public function onConnect()
    {

    }

    /**
     *  
     */
    public function onReceive()
    {
        $params = func_get_args();
        $fd = $params[1];
        $data = $params[3];
        $serv = $params[0];

        if(!isset($this->_ws[$fd])) {  //未连接

            $parser = new HttpParser();
            $buffer = !empty($this->_buff[$fd]['buff']) ? $this->_buff[$fd]['buff'] : "";
            $nparsed = !empty($this->_buff[$fd]['nparsed']) ? $this->_buff[$fd]['nparsed'] : 0;
            $buffer .= $data;
            $nparsed = $parser->execute($buffer, $nparsed);
            if($parser->hasError()) {
                $serv->close($fd);
                $this->_clearBuff($fd);
            } elseif ($parser->isFinished()) {
                $this->_clearBuff($fd);
                $response = $this->doHandshake($parser->getEnvironment());
                if($response) {
                    $this->_ws[$fd]['time'] = time();
                    $sendData  = join("\r\n", $response)."\r\n";
                    $this->log($sendData); 
                    $serv->send($fd, $sendData);
                    $this->wsOnOpen($fd);
                } else {
                    $serv->close($fd);
                }
            } else {
                $this->_buff[$fd]['buff'] = $buffer;
                $this->_buff[$fd]['nparsed'] = intval($nparsed);
            }
            return;
        } else {
            do
            {
                //新的数据帧
                if(empty($this->_ws_list[$fd]))
                {
                    $ws = $this->parseFrame($data);
                    //var_dump($ws);
                    //解析失败了
                    if($ws === false)
                    {
                        $this->log("parse frame failed.", 'ERROR');
                        $this->close($fd, self::CLOSE_PROTOCOL_ERROR);
                    }
                    //数据包就绪
                    if(!empty($ws['finish']))
                    {
                        $this->opcodeSwitch($fd, $ws);
                        //还有数据
                        if(strlen($data) > 0)
                        {
                            continue;
                        }
                    }
                    //未就绪，先加入到ws_list中
                    else
                    {
                        $this->_ws_list[$fd] = $ws;
                    }
                }
                else
                {
                    //这里必须是引用，需要保存状态
                    $ws = &$this->_ws_list[$fd];
                    $ws['buffer'] .= $data;
                    $message_len =  strlen($ws['buffer']);
                    //$this->log("wait data.buffer_len=$message_len|require_len={$ws['length']}", 'INFO');
                    if($ws['length'] == $message_len)
                    {
                        //需要使用MaskN来解析
                        $ws['message'] = $this->parseMessage($ws);
                        $this->opcodeSwitch($fd, $ws);
                    }
                    //数据过多，可能带有另外一帧的数据
                    else if($ws['length'] < $message_len)
                    {
                        //将buffer保存起来
                        $buffer = $ws['buffer'];
                        //分离本帧的数据
                        $ws['buffer'] = substr($buffer, 0, $ws['length']);
                        //这一帧的数据已完结
                        $ws['message'] = $this->parseMessage($ws);
                        //$data是下一帧的数据了
                        $data = substr($buffer, $ws['length']);
                        $this->opcodeSwitch($fd, $ws);
                        //继续解析帧
                        continue;
                    }
                    //等待数据
                }
                break;
            } while(true);
        }
    }

    private function parseFrame(&$data)
    {
        //websocket
        $ws  = array();
        $data_offset = 0;
        $data_length = strlen($data);

        //fin:1 rsv1:1 rsv2:1 rsv3:1 opcode:4
        $handle        = ord($data[$data_offset]);
        $ws['fin']    = ($handle >> 7) & 0x1;
        $ws['rsv1']   = ($handle >> 6) & 0x1;
        $ws['rsv2']   = ($handle >> 5) & 0x1;
        $ws['rsv3']   = ($handle >> 4) & 0x1;
        $ws['opcode'] =  $handle       & 0xf;
        $data_offset++;

        //mask:1 length:7
        $handle        = ord($data[$data_offset]);
        $ws['mask']   = ($handle >> 7) & 0x1;
        //0-125
        $ws['length'] =  $handle       & 0x7f;
        $length        = &$ws['length'];
        $data_offset++;

        if(0x0 !== $ws['rsv1'] || 0x0 !== $ws['rsv2'] || 0x0 !== $ws['rsv3'])
        {
            $this->close(self::CLOSE_PROTOCOL_ERROR);
            return false;
        }
        if(0 === $length)
        {
            $ws['message'] = '';
            return $ws;
        }
        //126 short
        elseif(0x7e === $length)
        {
            //2
            $handle = unpack('nl', substr($data, $data_offset, 2));
            $data_offset += 2;
            $length = $handle['l'];
        }
        //127 int64
        elseif(0x7f === $length)
        {
            //8
            $handle = unpack('N*l', substr($data, $data_offset, 8));
            $data_offset += 8;
            $length = $handle['l2'];
            if($length > 0x7fffffffffffffff)
            {
                $this->log('Message is too long.');
                return false;
            }
        }

        if(0x0 !== $ws['mask'])
        {
            //int32
            $ws['mask'] = array_map('ord', str_split(substr($data, $data_offset, 4)));
            $data_offset += 4;
        }

        $frame_length = $data_offset + $length;
        //设置buffer区
        $ws['buffer'] = substr($data, $data_offset, $length);
        //帧长度等于$data长度，说明这份数据是单独的一帧
        if ($frame_length == $data_length)
        {
            $data = "";
        }
        //帧长度小于数据长度，可能还有下一帧
        else if($frame_length < $data_length)
        {
            $data = substr($data, $frame_length);
        }
        //需要继续等待数据
        else
        {
            $ws['finish'] = false;
            $data = "";
            return $ws;
        }
        $ws['finish'] = true;
        $ws['message'] = $this->parseMessage($ws);
        return $ws;
    }

    private function parseMessage(&$ws)
    {
        $buffer = $ws['buffer'];
        //没有mask
        if(0x0 !== $ws['mask'])
        {
            $maskC = 0;
            for($j = 0, $_length = $ws['length']; $j < $_length; ++$j)
            {
                $buffer[$j] = chr(ord($buffer[$j]) ^ $ws['mask'][$maskC]);
                $maskC       = ($maskC + 1) % 4;
            }
            $ws['message'] = $buffer;
        }
        return $buffer;
    }
    /**
     * Write a frame.
     *
     * @access  public
     * @param   string  $message    Message.
     * @param   int     $opcode     Opcode.
     * @param   bool    $end        Whether it is the last frame of the message.
     * @return  int
     */
    private function newFrame ($message,  $opcode = self::OPCODE_TEXT_FRAME, $end = true )
    {
        $fin    = true === $end ? 0x1 : 0x0;
        $rsv1   = 0x0;
        $rsv2   = 0x0;
        $rsv3   = 0x0;
        $mask   = 0x1;
        $length = strlen($message);
        $out    = chr(
            ($fin  << 7)
            | ($rsv1 << 6)
            | ($rsv2 << 5)
            | ($rsv3 << 4)
            | $opcode
        );

        if(0xffff < $length)
            $out .= chr(0x7f) . pack('NN', 0, $length);
        elseif(0x7d < $length)
            $out .= chr(0x7e) . pack('n', $length);
        else
            $out .= chr($length);

        $out .= $message;
        return $out;
    }

    /**
     * Send a message.
     *
     * @access  public
     * @param   string  $message    Message.
     * @param   int     $opcode     Opcode.
     * @param   bool    $end        Whether it is the last frame of the message.
     * @return  void
     */
    public function send($fd, $message, $opcode = self::OPCODE_TEXT_FRAME, $end = true)
    {
        if((self::OPCODE_TEXT_FRAME  === $opcode or self::OPCODE_CONTINUATION_FRAME === $opcode) and false === (bool) preg_match('//u', $message))
        {
            $this->log('Message [%s] is not in UTF-8, cannot send it.', 2, 32 > strlen($message) ? substr($message, 0, 32) . ' ' : $message);
        }
        else
        {
            $out = $this->newFrame($message, $opcode, $end);
            $this->log("send {$fd} {$out}");
            return $this->serv->send($fd, $out);
        }
    }

    private function opcodeSwitch($fd, $ws)
    {
        switch($ws['opcode'])
        {
            case self::OPCODE_BINARY_FRAME:
            case self::OPCODE_TEXT_FRAME:
                //if(0x1 === $ws['fin'])
                {
                    $this->wsOnMessage($fd, $ws);
                    //数据已处理完
                    unset($this->_ws_list[$fd]);
                }
//                else
//                {
//                    $this->ws_list[$fd] = &$ws;
//                }
                break;
            case self::OPCODE_PING:
                $message = &$ws['message'];
                if(0x0  === $ws['fin'] or 0x7d  <  $ws['length'])
                {
                    $this->close($fd, self::CLOSE_PROTOCOL_ERROR);
                    break;
                }
                $this->_ws[$fd]['time'] = time();
                $this->send($fd, $message, self::OPCODE_PONG, true);
                break;
            case self::OPCODE_PONG:
                if(0 === $ws['fin'])
                {
                    $this->close($fd, self::CLOSE_PROTOCOL_ERROR);
                }
                unset($this->_ws_list[$fd]);
                break;
            case self::OPCODE_CONNECTION_CLOSE:
                $length = &$ws['length'];
                if(1    === $length || 0x7d < $length)
                {
                    $this->close($fd, self::CLOSE_PROTOCOL_ERROR);
                    break;
                }
                $code   = self::CLOSE_NORMAL;
                $reason = null;
                if(0 < $length)
                {
                    $message = &$ws['message'];
                    $_code   = unpack('nc', substr($message, 0, 2));
                    $code    = &$_code['c'];

                    if(1000 > $code || (1004 <= $code && $code <= 1006) || (1012 <= $code && $code <= 1016) || 5000  <= $code)
                    {
                        $this->close($fd, self::CLOSE_PROTOCOL_ERROR);
                        break;
                    }

                    if(2 < $length)
                    {
                        $reason = substr($message, 2);
                        if(false === (bool) preg_match('//u', $reason)) {
                            $this->close($fd, self::CLOSE_MESSAGE_ERROR);

                            break;
                        }
                    }
                }
                $this->close($fd, self::CLOSE_NORMAL);
                break;
            default:
                $this->close($fd, self::CLOSE_PROTOCOL_ERROR);
        }
    }
    
    private function doHandshake($data)
    {
        if (!isset($data['HTTP_SEC_WEBSOCKET_KEY']))
        {
            $this->log('Bad protocol implementation: it is not RFC6455.');
            return false;
        }
        $key = $data['HTTP_SEC_WEBSOCKET_KEY'];
        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $key) || 16 !== strlen(base64_decode($key)))
        {
            $this->log('Header Sec-WebSocket-Key: $key is illegal.');
            return false;
        }

        return array(
            'HTTP/1.1 101 Switching Protocols',
            'Upgrade: websocket',
            'Connection: Upgrade',
            'Sec-WebSocket-Accept: '. base64_encode(sha1($key . static::GUID, true)),
            'Sec-WebSocket-Version:'. self::WEBSOCKET_VERSION,
            'Date: '.gmdate("D, d M Y H:i:s T"),
            'KeepAlive: off',
            'Content-Length: 0',
            'Server: ZWebSocket',
            ''
        );
    }


    private function _clearBuff($fd)
    {
        if (isset($this->_buff[$fd])) {
            unset($this->_buff[$fd]);
        }
        return true;
    }

    public function onClose()
    {
        $params = func_get_args();
        $fd = $params[1];   
        $this->_clearBuff($fd);
        unset($this->_ws[$fd]);
        unset($this->_ws_list[$fd]);
        $this->wsOnClose($fd);
    }

    public function onShutdown()
    {

    }


    public function onWorkerStart()
    {
        $params = func_get_args();
        $this->serv = $params[0];
    }

    public function onWorkerStop()
    {
        /*
        $params = func_get_args();
        $worker_id = $params[1];
        echo "WorkerStop[$worker_id]|pid=" . posix_getpid() . ".\n";
        */
    }
    
    public function onTask()
    {
        
    }
    
    public function onFinish()
    {
        
    }

    public function close($fd, $code = self::CLOSE_NORMAL, $reason = '')
    {
        $this->send($fd, pack('n', $code).$reason, self::OPCODE_CONNECTION_CLOSE);
        $this->serv->close($fd);
    }

    public function log($msg)
    {
        echo $msg."\n";
    }
}

