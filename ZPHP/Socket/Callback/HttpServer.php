<?php

namespace ZPHP\Socket\Callback;

use ZPHP\Socket\ICallback;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Protocol;
use ZPHP\Core;
use \HttpMessage;


class HttpServer implements ICallback
{
    public function onStart()
    {
        //echo 'server start, swoole version: ' . SWOOLE_VERSION . PHP_EOL;
    }

    public function onConnect()
    {
        /*
        $params = func_get_args();
        $fd = $params[1];
        echo "{$fd} connected".PHP_EOL;
        */
    }

    /**
     * 支持get方式：url示例： http://host:port/?a=ctrl&m=method&key1=>val1&key2=val2
     */
    public function onReceive()
    {
        $params = func_get_args();
        $_data = $params[3];
        $serv = $params[0];
        $fd = $params[1];
        $httpMessage = new HttpMessage($_data);
        $url = $httpMessage->getRequestUrl();
        if('/favicon.ico' == $url) {
            $this->sendOne($serv, $fd, '');
            return ;
        }
        $datas = trim($url, '/?');
        $params = array();
        if(!empty($datas)) {
            \parse_str($datas, $params);
        }
        $result = $this->_route($params);
        $this->sendOne($serv, $fd, $result);
    }

    public function onClose()
    {
        /*
        $params = func_get_args();
        $fd = $params[1];
        echo "{$fd} closed".PHP_EOL;
        */
    }

    public function onShutdown()
    {
        //echo "server shut dowm\n";
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
        $data = json_encode($data);
        $tmpData = "HTTP/1.1 200 OK\r\nServer: zphp server/0.1 alpha\r\nContent-Length: " .strlen($data)."\r\nConnection:keep-alive\r\nContent-Type: text/json\r\n\r\n" . $data;
        return \swoole_server_send($serv, $fd, $tmpData);
    }


    private function _route($data)
    {
        try {
            $server = Protocol\Factory::getInstance(ZConfig::getField('socket', 'protocol', 'Rpc'));
            $server->parse($data);
            $result =  Core\Route::route($server);
            return $result;
        } catch (\Exception $e) {
            print_r($e);
            return null;
        }
    }


    public function onWorkerStart()
    {
        $params = func_get_args();
        $worker_id = $params[1];
        echo "WorkerStart[$worker_id]|pid=" . posix_getpid() . ".\n";

    }

    public function onWorkerStop()
    {
        $params = func_get_args();
        $worker_id = $params[1];
        echo "WorkerStop[$worker_id]|pid=" . posix_getpid() . ".\n";
    }
}
