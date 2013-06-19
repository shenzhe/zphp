<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */
namespace ZPHP\Queue;

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

    public function addQueue($key, $data)
    {
        return $this->redis->rPush($key, $data);
    }

    public function getQueue($key)
    {
        return $this->redis->lPop($key);
    }
}