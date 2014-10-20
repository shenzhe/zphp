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
    private $max_frame_size = 2097152;
    public  $serv;
    public $conn;

    abstract public function wsOnOpen($fd, $reponse);
    abstract public function wsOnMessage($fd, $ws);
    abstract public function wsOnClose($fd);

    public function onStart()
    {
        $this->log('server start, swoole version: ' . SWOOLE_VERSION);

        \swoole_set_process_name(ZConfig::get('project_name', 'websocket').":master,tcp://".ZConfig::getField('socket', 'host').":".ZConfig::getField('socket', 'port'));
    }

    public function onConnect()
    {

    }

    public function getConnection()
    {
        if(empty($this->conn)) {
            $this->conn = ZConn::getInstance();
        }
    }

    public function getConnInfo($fd)
    {
        $info =  $this->conn->getBuff($fd, 'info');
        if(empty($info)) {
            $info = array();
        }
        return $info;
    }

    public function addConnInfo($fd, array $data)
    {
        $info = $this->getConnInfo($fd);
        foreach($data as $key=>$val) {
            $info[$key] = $val;
        }
        $this->conn->setBuff($fd, $info, 'info');
    }

    public function delConnInfo($fd)
    {
        if($this->conn) {
            $this->conn->delBuff($fd, 'info');
        }
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

        $connInfo = $this->getConnInfo($fd);

        if(empty($connInfo)) {  //未连接

            $parser = new HttpParser();
            $buffer = $this->conn->getBuff($fd);
            $this->log("buffer cache.". strlen($buffer));
            if(empty($buffer)) {
                $buffer = "";
            }
            $nparsed = $this->conn->getBuff($fd, 'nparsed'); 
            if(empty($nparsed)) {
                $nparsed = 0;
            }
            $buffer .= $data;
            $nparsed = $parser->execute($buffer, $nparsed);
            if($parser->hasError()) {
                $this->log("parser error ");
                $serv->close($fd);
                $this->conn->delBuff($fd);
            } elseif ($parser->isFinished()) {
                $this->conn->delBuff($fd);
                $response = $this->doHandshake($parser->getEnvironment());
                if($response) {
                    $this->addConnInfo($fd, array('time'=>time()));
                    $sendData  = join("\r\n", $response)."\r\n";
                    $this->log($sendData);
                    $this->wsOnOpen($fd, $sendData);
                } else {
                    $this->log("reponse error".json_encode($response));
                    $serv->close($fd);
                }
            } else {
                $this->log("parser no finish");
                $this->conn->setBuff($fd, $buffer);
                $this->conn->setBuff($fd, intval($nparsed), 'nparsed');
            }
            return;
        } else {
            do
            {
                //新的数据帧
                if(!isset($this->_ws_list[$fd]))
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
                        // if(strlen($data) > 0)
                        // {
                        //     continue;
                        // }
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
                    //数据已完整，进行处理
                    if ($message_len >= $ws['length'])
                    {
                        $ws['buffer'] = substr($ws['buffer'], 0, $ws['length']);
                        $ws['message'] = $this->parseMessage($ws);
                        $this->opcodeSwitch($fd, $ws);
                        $data = substr($ws['buffer'], $ws['length']);
                    }
                    //数据不足，跳出循环，继续等待数据
                    else
                    {
                        break;
                    }
                    // if($ws['length'] == $message_len)
                    // {
                    //     //需要使用MaskN来解析
                    //     $ws['message'] = $this->parseMessage($ws);
                    //     $this->opcodeSwitch($fd, $ws);
                    // }
                    // //数据过多，可能带有另外一帧的数据
                    // else if($ws['length'] < $message_len)
                    // {
                    //     //将buffer保存起来
                    //     $buffer = $ws['buffer'];
                    //     //分离本帧的数据
                    //     $ws['buffer'] = substr($buffer, 0, $ws['length']);
                    //     //这一帧的数据已完结
                    //     $ws['message'] = $this->parseMessage($ws);
                    //     //$data是下一帧的数据了
                    //     $data = substr($buffer, $ws['length']);
                    //     $this->opcodeSwitch($fd, $ws);
                    //     //继续解析帧
                    //     continue;
                    // }
                    // //等待数据
                }
                break;
            } while(strlen($data) > 0 and $this->getConnInfo($fd));
        }
    }

    private function parseFrame(&$data)
    {
        //websocket
        $ws  = array();
        $ws['finish'] = false;
        $data_offset = 0;

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
            //$this->close(self::CLOSE_PROTOCOL_ERROR);
            return false;
        }
        if(0 === $length)
        {
            $ws['message'] = '';
            $data = substr($data, $data_offset + 4);
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
            if($length > $this->max_frame_size)
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

        //把头去掉
        $data = substr($data, $data_offset);
        //完整的一个数据帧
        if (strlen($data) >= $length) {
            $ws['finish'] = true;
            $ws['buffer'] =  substr($data, 0, $length);
            $ws['message'] = $this->parseMessage($ws);
            //截取数据
            $data = substr($data, $length);
            return $ws;
        } else { //需要继续等待数据 
            $ws['finish'] = false;
            $ws['buffer'] = $buffer;
            $buffer = "";
            return $ws;
        }

    }

    protected function parseMessage($ws)
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
    public function newFrame ($message,  $opcode = self::OPCODE_TEXT_FRAME, $end = true )
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
            return false;
        }
        else
        {
            $out = $this->newFrame($message, $opcode, $end);
            //$this->log("send {$fd} {$out}");
            return $this->serv->send($fd, $out);
        }
    }

    public function opcodeSwitch($fd, $ws)
    {
        switch($ws['opcode'])
        {
            case self::OPCODE_BINARY_FRAME:
            case self::OPCODE_TEXT_FRAME:
                //if(0x1 === $ws['fin'])
                {
                    $this->wsOnMessage($fd, $ws);
                    //数据已处理完
                    //unset($this->_ws_list[$fd]);
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
                $this->addConnInfo($fd, array('time'=>time()));
                $this->send($fd, $message, self::OPCODE_PONG, true);
                break;
            case self::OPCODE_PONG:
                if(0 === $ws['fin'])
                {
                    $this->close($fd, self::CLOSE_PROTOCOL_ERROR);
                }
                //unset($this->_ws_list[$fd]);
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
        unset($this->_ws_list[$fd]);
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
        $this->delConnInfo($fd);
        $this->wsOnClose($fd);
        unset($this->_ws_list[$fd]);
    }

    public function onShutdown()
    {

    }


    public function onWorkerStart()
    {
        $params = func_get_args();
        $this->serv = $params[0];
        $this->getConnection();
        $workerNum = ZConfig::getField('socket', 'worker_num');
        if($params[1] >= $workerNum) {
            \swoole_set_process_name(ZConfig::get('project_name', 'websocket').":task, id:".($params[1] - $workerNum));
        } else {
            \swoole_set_process_name(ZConfig::get('project_name', 'websocket').":worker, id:".$params[1]);
        }
    }

    public function onWorkerStop()
    {
        /*
        $params = func_get_args();
        $worker_id = $params[1];
        echo "WorkerStop[$worker_id]|pid=" . posix_getpid() . ".\n";
        */
    }

    public function onManagerStart()
    {
        \swoole_set_process_name(ZConfig::get('project_name', 'websocket').":manager");
    }
    
    
    public function onTask()
    {
        
    }
    
    public function onFinish()
    {
        
    }

    public function close($fd, $code = self::CLOSE_NORMAL, $reason = '')
    {
        $this->log("close {$code}". json_encode(debug_backtrace()));
        $this->send($fd, pack('n', $code).$reason, self::OPCODE_CONNECTION_CLOSE);
        $this->serv->close($fd);
    }

    public function log($msg)
    {
        echo $msg."\n";
    }
}

