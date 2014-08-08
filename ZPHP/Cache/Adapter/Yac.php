<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Cache\Adapter;
use ZPHP\Cache\ICache;

class Yac implements ICache
{
    private $yac = null;

    public function __construct($config = null)
    {
        if ($this->enable() && empty($this->yac)) {
            $this->yac = new \Yac();
        }
    }

    public function enable()
    {
        return \extension_loaded('yac');
    }

    public function selectDb($db)
    {
        return true;
    }

    public function add($key, $value, $timeOut = 0)
    {
        $data = $this->yac->get($key);
        if (!empty($data)) {
            throw new \Exception("{$key} exitst");
        }
        return $this->yac->set($key, $value, $timeOut);
    }

    public function set($key, $value, $timeOut = 0)
    {
        return $this->yac->set($key, $value, $timeOut);
    }

    public function get($key)
    {
        return $this->yac->get($key);
    }

    public function delete($key)
    {
        return $this->yac->delete($key);
    }

    public function increment($key, $step = 1)
    {
        $data = $this->yac->get($key);
        if (empty($data)) {
            $this->yac->set($key, $step);
        }
        if (!\is_numeric($data)) {
            throw new \Exception("value no numeric");
        }
        return $this->yac->set($key, ($data + $step));
    }

    public function decrement($key, $step = 1)
    {
        $data = $this->yac->get($key);
        if (!\is_numeric($data)) {
            throw new \Exception("value no numeric");
        }
        return $this->yac->set($key, ($data - $step));
    }

    public function clear()
    {
        return $this->yac->flush();
    }
}