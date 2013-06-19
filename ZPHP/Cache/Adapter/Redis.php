<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Cache\Adapter;
use ZPHP\Cache\ICache,
    ZPHP\Manager;
class Redis implements ICache
{
    private static $redis;

    public function __construct($config) {
        if (empty(self::$redis)) {
            self::$redis = Manager\Redis::getInstance($config);
        }
    }

    public function enable() {
        return true;
    }

    public function selectDb($db) {
        self::$redis->select($db);
    }

    public function add($key, $value, $expiration = 0) {
        return self::$redis->setNex($key, $expiration, $value);
    }

    public function set($key, $value, $expiration = 0) {
        if($expiration) {
            return self::$redis->setex($key, $expiration, $value);
        } else {
            return self::$redis->set($key, $value);
        }
    }

    public function addToCache($key, $value, $expiration = 0) {
        return $this->set($key, $value, $expiration);
    }

    public function get($key) {
        return self::$redis->get($key);
    }

    public function getCache($key) {
        return $this->get($key);
    }

    public function delete($key) {
        return self::$redis->delete($key);
    }

    public function increment($key, $offset = 1) {
        return self::$redis->incrBy($key, $offset);
    }

    public function decrement($key, $offset = 1) {
        return self::$redis->decBy($key, $offset);
    }

    public function clear() {
        return self::$redis->flushDB();
    }
}