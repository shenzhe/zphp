<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Storage\Adapter;
use ZPHP\Manager,
    ZPHP\Storage\IStorage;

class RL implements IStorage
{
    private $redis;
    private $sRedis = null;
    private $suffix = "";
    private $pconnect;

    public function __construct($config)
    {
        if (empty($this->redis)) {
            $this->redis = Manager\Redis::getInstance($config);
            $this->pconnect = $config['pconnect'];
        }
    }

    public function setSlave($config)
    {
        if (empty($this->sRedis)) {
            $this->sRedis = Manager\Redis::getInstance($config);
        }
    }

    public function setKeySuffix($suffix)
    {
        $this->suffix = $suffix;
    }

    private function uKey($userId)
    {
        return $userId . '_' . $this->suffix;
    }


    public function getMutilMD($userId, $keys, $slaveConfig = "")
    {
        $uKey = $this->uKey($userId);
        return $this->redis->rlHMGet($uKey, $keys);
    }

    public function getMD($userId, $key, $slaveConfig = "")
    {
        $uKey = $this->uKey($userId);
        return $this->redis->rlHGet($uKey, $key);
    }

    public function getSD($userId, $key, $slaveConfig = "")
    {
        $uKey = $this->uKey($userId);
        return $this->redis->dsHGet($uKey, $key);
    }

    public function setSD($userId, $key, $data)
    {
        $uKey = $this->uKey($userId);
        return $this->redis->dsHSet($uKey, $key, $data);
    }

    public function setMD($userId, $key, $data, $cas = false)
    {
        if ($cas) {
            return $this->setMDCAS($userId, $key, $data);
        }
        $uKey = $this->uKey($userId);
        return $this->redis->rlHSet($uKey, $key, $data);
    }

    public function addMD($userId, $key, $data)
    {
        $uKey = $this->uKey($userId);
        if ($this->redis->dsHGet($uKey, $key)) {
            throw new \Exception("{$key} exist");
        }
        if ($this->redis->dsHSet($uKey, $key, $data)) {
            return $this->redis->hSetNx($uKey, $key, $data);
        }
        return false;
    }

    public function setMDCAS($userId, $key, $data)
    {
        $uKey = $this->uKey($userId);
        $this->redis->watch($uKey);
        $result = $this->redis->multi()->hSet($uKey, $key, $data)->exec();
        if (false === $result) {
            throw new \Exception('cas error');
        }
        if ($this->redis->dsHSet($uKey, $key, $data)) {
            return true;
        }

        throw new \Exception('dsSet error');
    }

    public function del($userId, $key)
    {
        $uKey = $this->uKey($userId);
        return $this->redis->rlHDel($uKey, $key);
    }

    public function delSD($userId, $key, $slavename = '')
    {
        $uKey = $this->uKey($userId);
        return $this->redis->dsHDel($uKey, $key);
    }

    public function setMultiMD($userId, $keys)
    {
        $uKey = $this->uKey($userId);
        return $this->redis->rlHMSet($uKey, $keys);
    }

    public function close()
    {
        if ($this->pconnect) {
            return true;
        }

        $this->redis->close();

        if (!empty($this->sRedis)) {
            $this->sRedis->close();
        }

        return true;
    }

    public function getMulti($cmds)
    {
        $this->redis->multi(\Redis::PIPELINE);
        $uids = array();
        foreach ($cmds as $userId => $key) {
            $uids[] = $userId;
            $uKey = $this->uKey($userId);
            $this->redis->rlHGet($uKey, $key);
        }

        return $this->redis->exec();
    }
}
