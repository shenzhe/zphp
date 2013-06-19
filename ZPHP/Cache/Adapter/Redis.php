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
    private $redis;

    public function __construct($config)
    {
        if (empty($this->redis)) {
            $this->redis = Manager\Redis::getInstance($config);
        }
    }

    public function enable()
    {
        return true;
    }

    public function selectDb($db)
    {
        $this->redis->select($db);
    }

    public function add($key, $value, $expiration = 0)
    {
        return $this->redis->setNex($key, $expiration, $value);
    }

    public function set($key, $value, $expiration = 0)
    {
        if ($expiration) {
            return $this->redis->setex($key, $expiration, $value);
        } else {
            return $this->redis->set($key, $value);
        }
    }

    public function addToCache($key, $value, $expiration = 0)
    {
        return $this->set($key, $value, $expiration);
    }

    public function get($key)
    {
        return $this->redis->get($key);
    }

    public function getCache($key)
    {
        return $this->get($key);
    }

    public function delete($key)
    {
        return $this->redis->delete($key);
    }

    public function increment($key, $offset = 1)
    {
        return $this->redis->incrBy($key, $offset);
    }

    public function decrement($key, $offset = 1)
    {
        return $this->redis->decBy($key, $offset);
    }

    public function clear()
    {
        return $this->redis->flushDB();
    }
}