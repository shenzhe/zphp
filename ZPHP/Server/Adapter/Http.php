<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;
use ZPHP\Core,
    ZPHP\Server\IServer,
    ZPHP\Protocol;

class Http implements IServer
{
    private $protocol;

    public function run()
    {
        if(!$this->protocol) {
            $this->protocol = Protocol\Factory::getInstance('Http');
        }
        $this->protocol->parse($_REQUEST);
        return Core\Route::route($this->protocol);
    }

    public function getProtocol()
    {
        return $this->protocol;
    }

}