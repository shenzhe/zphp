<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;
use ZPHP\Core,
    ZPHP\Protocol;

class Cli
{
    public function run()
    {
        $server = Protocol\Factory::getInstance('Cli');
        $server->parse($_SERVER['argv']);
        Core\Route::route($server);
    }

}