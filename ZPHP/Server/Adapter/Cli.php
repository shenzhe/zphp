<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;
use ZPHP\Core,
    ZPHP\Server\IServer,
    ZPHP\Protocol;

class Cli implements IServer
{
    private $protocol;

    public function run()
    {
        if(!$this->protocol) {
            $this->protocol = Protocol\Factory::getInstance('Cli');
        }
        $this->protocol->parse($_SERVER['argv']);
        return Core\Route::route($this->protocol);
    }

    public function getProtocol()
    {
        return $this->protocol;
    }

}
