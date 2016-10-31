<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;

use ZPHP\Core;
use ZPHP\Server\IServer;
use ZPHP\Protocol;

class Rpc implements IServer
{
    public function run()
    {
        $rpc = new \Yar_Server(new self());
        $rpc->handle();
    }

    public function api($params)
    {
        Protocol\Request::setServer(Protocol\Factory::getInstance('Rpc'));
        Protocol\Request::parse($params);
        return Core\Route::route();
    }

}