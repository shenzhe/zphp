<?php

namespace socket;

use ZPHP\Socket\ICallback;
use ZPHP\Socket\IClient;
use ZPHP\Protocol;
use ZPHP\Core;
use ZPHP\Core\Config as ZConfig;

class Php implements ICallback
{


    public function onStart()
    {
        echo 'server start';
    }

    public function onConnect()
    {
        $params = func_get_args();
        $fd = $params[0];
        echo "Client {$fd}：Connect\n";
    }

    public function onReceive()
    {
        $params = func_get_args();
        $client_id = $params[0];
        $data = trim($params[1]); 
        echo "get data {$data} from $client_id\n";
        if (empty($data)) {
            return;
        }
        $server = Protocol\Factory::getInstance(Core\Config::getField('socket', 'protocol'));
        $result = $server->parse($data);
        if (!empty($result['a'])) {        	
            $server->setFd($client_id);
            $server = $this->route($server);
            $data = $server->getData();
        } else {
        	$data = 'Server:' . $data;
        }
        return $this->server->send($client_id, $data);
    }

    public function onClose()
    {
        $params = func_get_args();
        $fd = $params[0];
        echo "Client {$fd}: close";
    }

    public function onShutdown()
    {
        echo "server close";
    }

    public function onTimer()
    {

    }

    private function route($server)
    {
        try {
            Core\Route::route($server);
        } catch (\Exception $e) {
            $server->display($e->getMessage());
        }
        return $server;
    }

}

