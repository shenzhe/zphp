<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Session\Adapter;
use ZPHP\Manager;

class Redis
{
    private $redis;
    private $gcTime = 1800;
    private $config;

    public function __construct($config)
    {
        if (empty($this->redis)) {
            $this->redis = Manager\Redis::getInstance($config);
            if (!empty($config['cache_expire'])) {
                $this->gcTime = $config['cache_expire'] * 60;
            }
            $this->config = $config;
        }
    }

    public function open($path, $sid)
    {
        return !empty($this->redis);
    }

    public function close()
    {
        return true;
    }

    public function gc($time)
    {
        return true;
    }

    public function read($sid)
    {
        if(!empty($this->config['sid_prefix'])) {
            $sid = str_replace($this->config['sid_prefix'], '', $sid);
        }
        $data = $this->redis->get($sid);
        if (!empty($data)) {
            $this->redis->setTimeout($sid, $this->gcTime);
        }
        return $data;
    }

    public function write($sid, $data)
    {
        if(empty($data)) {
            return true;
        }
        if(!empty($this->config['sid_prefix'])) {
            $sid = str_replace($this->config['sid_prefix'], '', $sid);
        }
        return $this->redis->setex($sid, $this->gcTime, $data);
    }

    public function destroy($sid)
    {
        if(!empty($this->config['sid_prefix'])) {
            $sid = str_replace($this->config['sid_prefix'], '', $sid);
        }
        return $this->redis->delete($sid);
    }
}
