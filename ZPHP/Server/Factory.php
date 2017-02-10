<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */
namespace ZPHP\Server;

use ZPHP\Core\Factory as CFactory;

class Factory
{
    private static $_map = [
        'Ant' => 1,
        'Cli' => 1,
        'Hprose' => 1,
        'Http' => 1,
        'Rpc' => 1,
        'Socket' => 1,
    ];

    public static function getInstance($adapter = 'Http')
    {
        $adapter = ucfirst(strtolower($adapter));
        if (isset(self::$_map[$adapter])) {
            $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        } else {
            $className = $adapter;
        }
        return CFactory::getInstance($className);
    }
}