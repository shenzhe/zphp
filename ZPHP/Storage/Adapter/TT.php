<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Storage\Adapter;
use ZPHP\Manager,
    ZPHP\Storage\IStorage;

class TT implements IStorage
{
    private $tt;
    private $stt = null;
    private $suffix = "";

    public function __construct($name, $config)
    {
        if (!empty($this->tt)) {
            $this->tt = Manager\Memcached::getInstance($config);
        }
    }

    public function setSlave($config)
    {
        if (empty($this->stt)) {
            $this->stt = Manager\Memcached::getInstance($config);
        }
    }

    public function getMutilMD($userId, $keys, $slaveConfig = '')
    {
        $newKeys = array();
        foreach ($keys as $key) {
            $newKeys[] = $this->uKey($userId, $key);
        }
        $datas = $this->tt->getMulti($newKeys);
        foreach ($datas as $key => $val) {
            if (false === $val) {
                $val = $this->getSD($userId, $key, $slaveConfig);
                if (false !== $val) {
                    $datas[$key] = $val;
                }
            }
        }
        return $datas;
    }

    public function getMD($userId, $key, $slaveName = "")
    {
        $key = $this->uKey($userId, $key);
        $data = $this->tt->get($key);

        if (false === $data) {
            $code = $this->tt->getResultCode();
            if ($code == \Memcached::RES_NOTFOUND) {
                $this->setSlave($slaveName);
                $data = $this->stt->get($key);
                if (false === $data) {
                    $code = $this->stt->getResultCode();
                    if ($code == \Memcached::RES_NOTFOUND) {
                        return false;
                    } else {
                        throw new \Exception("null data: {$userId}, {$key}, {$code}");
                    }
                }
            } else {
                throw new \Exception("error data: {$userId}, {$key}, {$code}");
            }
        }
        return $data;
    }

    public function del($userId, $key)
    {
        $key = $this->uKey($userId, $key);
        return $this->tt->del($key);
    }

    public function getSD($userId, $key, $slaveName = "")
    {
        $key = $this->uKey($userId, $key);
        $this->setSlave($slaveName);
        $data = $this->stt->get($key);
        if (false === $data) {
            $code = $this->stt->getResultCode();
            if ($code == \Memcached::RES_NOTFOUND) {
                return false;
            } else {
                throw new \Exception("null data: {$userId}, {$key}, {$code}");
            }
        }

        return $data;
    }

    public function setSD($userId, $key, $data, $slaveName = "")
    {
        $key = $this->uKey($userId, $key);
        $this->setSlave($slaveName);
        return $this->stt->set($key, $data);
    }

    public function delSD($userId, $key, $slaveName = "")
    {
        $key = $this->uKey($userId, $key);
        $this->setSlave($slaveName);
        return $this->stt->delete($key);
    }

    public function setMD($userId, $key, $data)
    {
        $key = $this->uKey($userId, $key);
        return $this->tt->set($key, $data);
    }

    public function setMDCAS($userId, $key, $data)
    {
        $key = $this->uKey($userId, $key);
        return $this->tt->set($key, $data);
    }

    public function setMultiMD($userId, $keys)
    {
        foreach ($keys as $key => $value) {
            $newKey = $this->uKey($userId, $key);
            $keys[$newKey] = $value;
            unset($key);
        }
        return $this->tt->setMulti($keys);
    }


    public function setKeySuffix($suffix)
    {
        $this->suffix = $suffix;
    }

    private function uKey($userId, $key)
    {
        return $userId . "_" . $this->suffix . "__" . $key;
    }

    public function close()
    {
        return true;
    }

}
