<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */
namespace ZPHP\Protocol;

use ZPHP\Core\Factory as CFactory;

class Factory
{
    public static function getInstance($adapter = 'Http')
    {
        if (is_file(__DIR__ . DS . 'Adapter' . DS . $adapter . '.php')) {
            $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        } else {
            $className = $adapter;
        }
        return CFactory::getInstance($className);
    }
}