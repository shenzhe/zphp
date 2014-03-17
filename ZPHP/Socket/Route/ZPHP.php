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

class ZPHP
{
    public function run($data, $fd=null)
    {
        $server = Protocol\Factory::getInstance('Http');
        $server->setFd($fd);
        $server->parse($data);
        \ob_start();
        Core\Route::route($server);
        $result = \ob_get_contents();
        \ob_end_clean();
        return $result;
    }

}
