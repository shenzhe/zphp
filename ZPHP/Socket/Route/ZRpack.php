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
        $results = array();
        if(false === $server->parse($data)) {
            return $results;
        }
        \ob_start();
        Core\Route::route($server);
        $result = \ob_get_contents();
        \ob_end_clean();
        if(!empty($result)) {
            $results[] = json_decode($result, true);
        } else {
            $results[] = null;
        }
        while ($server->parse("")) {
            \ob_start();
            Core\Route::route($server);
            $result = \ob_get_contents();
            \ob_end_clean();
            if(!empty($result)) {
                $results[] = json_decode($result, true);
            } else {
                $results[] = null;
            }
        }
        return $results;
    }
}
