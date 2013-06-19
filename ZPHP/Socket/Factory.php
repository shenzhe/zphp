<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 *  view接口
 */
namespace ZPHP\Socket;
use ZPHP\Core\Factory as CFactory;

class Factory
{
    public static function getInstance($adapter='Swoole', $config)
    {
        $className = __NAMESPACE__."\\Adapter\\{$adapter}";
        return CFactory::getInstance($className, $config);
    }
}