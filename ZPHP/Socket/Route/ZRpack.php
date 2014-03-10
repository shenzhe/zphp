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
    public function run($data)
    {
        $server = Protocol\Factory::getInstance('ZRpack');
        if(!$server->parse($data)) {
            return array();
        }
        $results = array();
        \ob_start();
        Core\Route::route($server);
        $result = \ob_get_contents();
        \ob_end_clean();
        $results[] = $result;
        while ($server->parse($server->getFdBuffer())) {
            \ob_start();
            Core\Route::route($server);
            $result = \ob_get_contents();
            \ob_end_clean();
            $results[] = $result;
        }

        return $results;
    }
}
