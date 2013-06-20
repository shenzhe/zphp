<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;
use ZPHP\Core,
    ZPHP\Protocol;

class Rpc
{
    public function run()
    {
        $rpc = new \Yar_Server(new self());
        $rpc->handle();
    }

    public function api($params)
    {
        $server = Protocol\Factory::getInstance('Rpc');
        $server->parse($params);
        Core\Route::route($server);
    }
}