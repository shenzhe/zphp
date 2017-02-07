<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 *
 */
namespace ZPHP\Socket;

use ZPHP\Core\Factory as CFactory;
use ZPHP\Core\Config as ZConfig;

class Factory
{
    private static $_map = [
        'Hprose' => 1,
        'Php' => 1,
        'React' => 1,
        'Swoole' => 1,
    ];

    public static function getInstance($adapter = 'Swoole', $config = null)
    {
        if (empty($config)) {
            $config = ZConfig::get('socket');
            if (!empty($config['adapter'])) {
                $adapter = $config['adapter'];
            }
        }
        $adapter = ucfirst(strtolower($adapter));
        if (isset(self::$_map[$adapter])) {
            $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        } else {
            $className = $adapter;
        }
        return CFactory::getInstance($className, $config);
    }
}