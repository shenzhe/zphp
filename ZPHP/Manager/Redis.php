<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Manager;
class Redis
{
    private static $instances;
    public static function getInstance($config) {
        $name = $config['name'];
        $pconnect = $config['pconnect'];
        if (empty(self::$instances[$name])) {
            if (empty(self::$configs[$name])) {
                return null;
            }

            $config = self::$configs[$name];
            $redis = new \Redis();
            if ($pconnect) {
                $redis->pconnect($config['host'], $config['port'], $config['timeout'], $name);
            } else {
                $redis->connect($config['host'], $config['port'], $config['timeout']);
            }
            $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
            self::$instances[$name] = $redis;
        }
        return self::$instances[$name];
    }

    /**
     * 手动关闭链接
     * @param bool $pconnect
     * @param array $names
     * @return bool
     */
    public static function closeInstance($pconnect=false, array $names=[]) {
        if (empty(self::$instances) || $pconnect) {
            return true;
        }

        if(empty($names)) {
            foreach (self::$instances as $redis) {
                $redis->close();
            }
        } else {
            foreach($names as $name) {
                if(isset(self::$instances[$name])) {
                    self::$instances[$name]->close();
                }
            }
        }

        return true;
    }
}