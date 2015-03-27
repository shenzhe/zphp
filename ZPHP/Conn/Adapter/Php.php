<?php

namespace ZPHP\Conn\Adapter;
use ZPHP\Core\Config as ZConfig,
    ZPHP\Conn\IConn,
    ZPHP\Cache\Factory as ZCache;

/**
 *  php内置数组
 */
class Php implements IConn
{

    private $_cache = array();

    public function __construct($config)
    {
        
    }


    public function addFd($fd, $uid = 0)
    {
        $key = $this->getKey($fd, 'fu');
        return $this->_cache[$key] = $uid;
    }


    public function getUid($fd)
    {
        $key = $this->getKey($fd, 'fu');
        return $this->getByKey($key);
    }

    public function add($uid, $fd)
    {
        $uinfo = $this->get($uid);
        if (!empty($uinfo)) {
            $this->delete($uinfo['fd'], $uid);
        }
        $data = array(
            'fd' => $fd,
            'time' => time(),
            'types' => array('ALL' => 1)
        );

        $key = $this->getKey($uid);
        $this->_cache[$key] = $data;
        $this->upChannel($uid, $fd);
    }

    public function addChannel($uid, $channel)
    {
        $uinfo = $this->get($uid);
        $uinfo['types'][$channel] = 1;
        if ($this->upChannel($uid, $uinfo['fd'], $channel)) {
            $key = $this->getKey($uid);
            $this->_cache[$key] = $uinfo;
        }
    }

    private function upChannel($uid, $fd, $channel = 'ALL')
    {   
        $channelInfo = $this->getChannel($channel);
        $channelInfo[$uid] = $fd;
        $key = $this->getKey($channel);
        $this->_cache[$key] = $channelInfo;
        return true;
    }

    public function delChannel($uid, $channel)
    {
        $channelInfo = $this->getChannel($channel);
        if(isset($channelInfo[$uid])) {
            unset($channelInfo[$uid]);
            $key = $this->getKey($channel);
            $this->_cache[$key] = $channelInfo;
            $uinfo = $this->get($uid);
            if(isset($uinfo['types'][$channel])) {
                unset($uinfo['types'][$channel]);
                $key = $this->getKey($uid);
                $this->_cache[$key] = $uinfo;
            }
        }
        return true;
    }

    public function getChannel($channel = 'ALL')
    {
        $key = $this->getKey($channel);
        return $this->getByKey($key);
    }

    public function get($uid)
    {
        $key = $this->getKey($uid);
        return $this->getByKey($key);
    }

    public function uphb($uid)
    {
        $uinfo = $this->get($uid);
        if (empty($uinfo)) {
            return false;
        }
        $uinfo['time'] = time();
        $key = $this->getKey($uid);
        $this->_cache[$key] = $uinfo;
        return true;
    }

    public function heartbeat($uid, $ntime = 60)
    {
        $uinfo = $this->get($uid);
        if (empty($uinfo)) {
            return false;
        }
        $time = time();
        if ($time - $uinfo['time'] > $ntime) {
            $this->delete($uinfo['fd'], $uid);
            return false;
        }
        return true;
    }

    public function delete($fd, $uid = null, $old = true)
    {
        if ($old) {
            $okey = $this->getKey($fd, 'fu');
            if(isset($this->_cache[$okey])) {
                unset($this->_cache[$okey]);
            }
        }
        $this->delBuff($fd);
        if (empty($uid)) {
            return;
        }
        $uinfo = $this->get($uid);
        if (!empty($uinfo)) {
            $key = $this->getKey($uid);
            if(isset($this->_cache[$key])) {
                unset($this->_cache[$key]);
            }
            foreach ($uinfo['types'] as $type => $val) {
                $key = $this->getKey($type);
                if(!empty($this->_cache[$key][$uid])) {
                    unset($this->_cache[$key][$uid]);   
                }
            }
        }
    }

    public function getBuff($fd, $prev='buff')
    {
        $key = $this->getKey($fd, $prev);
        return $this->getByKey($key);
    }

    public function setBuff($fd, $data, $prev='buff')
    {
        $key = $this->getKey($fd, $prev);
        $this->_cache[$key] = $data;
        return true;
    }

    public function delBuff($fd, $prev='buff')
    {
        $key = $this->getKey($fd, $prev);
        if(isset($this->_cache[$key])) {
            unset($this->_cache[$key]);
        }
        return true;
    }

    private function getKey($uid, $prefix = 'uf')
    {
        return "{$prefix}_{$uid}_" . ZConfig::getField('connection', 'prefix');
    }

    public function clear()
    {
        $this->_cache = array();
    }

    private function getByKey($key)
    {
        if(isset($this->_cache[$key])) {
            return $this->_cache[$key];
        }

        return null;
    }
}