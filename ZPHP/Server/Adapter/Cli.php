<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;

use ZPHP\Core;
use ZPHP\Server\IServer;
use ZPHP\Protocol;

class Cli implements IServer
{
    public function run()
    {
        $server = Protocol\Factory::getInstance('Cli');
        Protocol\Request::setServer($server);
        Protocol\Request::parse($_SERVER['argv']);
        Protocol\Request::setHttpServer(0);
        return Core\Route::route();
    }

}
