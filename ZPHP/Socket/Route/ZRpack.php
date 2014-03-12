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
        $result = '';
        if(false === $server->parse($data)) {
            return $result;
        }
        \ob_start();
        Core\Route::route($server);
        $result = \ob_get_contents();
        \ob_end_clean();

        while ($server->parse("")) {
            \ob_start();
            Core\Route::route($server);
            $result .= \ob_get_contents();
            \ob_end_clean();
            
        }

        return $result;
    }
}
