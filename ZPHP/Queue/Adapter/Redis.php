<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */
namespace ZPHP\Queue\Adapter;

use ZPHP\Manager;

class Redis
{
    private $redis;

    public function __construct($config)
    {
        if (empty($this->redis)) {
            $this->redis = Manager\Redis::getInstance($config);
        }
    }

    public function add($key, $data)
    {
        return $this->redis->rPush($key, $data);
    }

    public function get($key)
    {
        return $this->redis->lPop($key);
    }
}