<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;
use ZPHP\Core,
    ZPHP\Protocol;

class Http
{
    public function run()
    {
        $server = Protocol\Factory::getInstance('Http');
        $server->parse($_REQUEST);
        return Core\Route::route($server);
    }

}