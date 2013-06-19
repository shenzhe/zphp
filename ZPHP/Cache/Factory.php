<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 */
namespace ZPHP\Cache;

class Factory
{
    public static function getInstance($adapter='Json', $config=null)
    {
        $className = __NAMESPACE__."\\Adapter\\{$adapter}";
        return CFactory::getInstance($className, $config);
    }
}
