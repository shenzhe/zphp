<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;
use ZPHP\Core,
    ZPHP\Server\IServer,
    ZPHP\Protocol;

class Rpc implements IServer
{
    private $protocol;
    public function run()
    {
        $rpc = new \Yar_Server(new self());
        $rpc->handle();
    }

    public function api($params)
    {
        if(!$this->protocol) {
            $this->protocol = Protocol\Factory::getInstance('Rpc');
        }
        $this->protocol->parse($params);
        return Core\Route::route($this->protocol);
    }

    public function getProtocol()
    {
        return $this->protocol;
    }
}