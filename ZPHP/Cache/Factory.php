<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 */
namespace ZPHP\Cache;
use ZPHP\Core\Factory as ZFactory;

class Factory
{
    public static function getInstance($adapter = 'Redis', $config = null)
    {
        $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return ZFactory::getInstance($className, $config);
    }
}
