<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Manager;
class Memcached
{
    private static $instances;

    public static function getInstance($config)
    {
        $name = $config['name'];
        $pconnect = $config['pconnect'];
        if (empty(self::$instances[$name])) {
            if ($pconnect) {
                $memcached = new \Memcached($name);
            } else {
                $memcached = new \Memcached();
            }
            foreach ($config['servers'] as $server) {
                $memcached->addServer($server['host'], $server['port']);
            }
            self::$instances[$name] = $memcached;
        }
        return self::$instances[$name];
    }
}