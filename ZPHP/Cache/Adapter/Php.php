<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Cache\Adapter;
use ZPHP\Cache\ICache;

class Php implements ICache
{
    private $_cache = array();

    public function __construct($config = null)
    {
        
    }

    public function enable()
    {
        return true;
    }

    public function selectDb($db)
    {
        return true;
    }

    public function add($key, $value, $timeOut = 0)
    {
        if (isset($this->_cache[$key])) {
            throw new \Exception("{$key} exitst");
        }
        $timeOut = $timeOut ? (time() + $timeOut) : 0;
        return $this->_cache[$key] = array(
            $value, $timeOut
        );
    }

    public function set($key, $value, $timeOut = 0)
    {
        $timeOut = $timeOut ? (time() + $timeOut) : 0;
        return $this->_cache[$key] = array(
            $value, $timeOut
        );
    }

    public function get($key)
    {
        if(empty($this->_cache[$key])) {
            return null;
        }

        if(!empty($this->_cache[$key][1]) && $this->_cache[$key][1] <= time()) { //过期了
            unset($this->_cache[$key]); 
            return null;
        }
        return $this->_cache[$key][0];
    }

    public function delete($key)
    {
        unset($this->_cache[$key]);
        return true;
    }

    public function increment($key, $step = 1)
    {
        if(!empty($this->_cache[$key][0])) {
            if (!\is_numeric($this->_cache[$key][0])) {
                throw new \Exception("value no numeric");
            }
            $this->_cache[$key][0] += $step;
        } else {

            $this->_cache[$key] = array(
                $step, 0
            );
        }
        return $this->_cache[$key][0];
    }

    public function decrement($key, $step = 1)
    {
        if(!empty($this->_cache[$key][0])) {
            if (!\is_numeric($this->_cache[$key][0])) {
                throw new \Exception("value no numeric");
            }
            $this->_cache[$key][0] -= $step;
        } else {

            $this->_cache[$key] = array(
                0-$step, 0
            );
        }
        return $this->_cache[$key][0];
    }

    public function clear()
    {
        return $this->_cache = array();
    }
}