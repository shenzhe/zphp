<?php
/**
 * User: shenzhe
 * Date: 2014/2/7
 *
 * 内置route
 */


namespace ZPHP\Socket\Route;
use ZPHP\Protocol;
use ZPHP\Core;

class ZRpack
{
    public function run($data, $fd)
    {
        $server = Protocol\Factory::getInstance('ZRpack');
        $server->setFd($fd);
        $result = array();
        if(false === $server->parse($data)) {
            return $result;
        }
        $result[] = Core\Route::route($server);
        while ($server->parse("")) {
            $result[] = Core\Route::route($server);
        }
        return $result;
    }
}
