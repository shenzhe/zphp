<?php

namespace socket;

use ZPHP\Socket\ICallback;
use ZPHP\Socket\IClient;
use ZPHP\Protocol;
use ZPHP\Core;
use ZPHP\Core\Config as ZConfig;
class Swoole implements ICallback
{


    public function onStart() {
        echo 'server start';
    }

    public function onConnect() {
        $params = func_get_args();
        $fd = $params[1];
        echo "Client {$fd}：Connect\n";
    }

    public function onReceive(){
        $params = func_get_args();
        $data = trim($params[3]);
        $serv = $params[0];
        $fd = $params[1];
        echo "get data {$data} from $fd\n";
        if(empty($data)) {
            return;
        }
        swoole_server_send($serv, $fd, "server:".$data);
    }

    public function onClose() {
        $params = func_get_args();
        $fd = $params[1];
        echo "Client {$fd}: close";
    }

    public function onShutdown() {
        echo "server close";
    }

}
