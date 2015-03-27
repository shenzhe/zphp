<?php

namespace ZPHP\Conn\Adapter;
use ZPHP\Core\Config as ZConfig,
    ZPHP\Conn\IConn,
    ZPHP\Cache\Factory as ZCache;

/**
 *  yac共享内存
 */
class Yac implements IConn
{

    private $yac;

    public function __construct($config)
    {
        if(empty($this->yac)) {
            $this->yac = ZCache::getInstance($config['adapter'], $config);
            if(!$this->yac->enable()) {
                throw new \Exception("Yac no enable");
                
            }
        }
    }


    public function addFd($fd, $uid = 0)
    {
        return $this->yac->set($this->getKey($fd, 'fu'), $uid);
    }


    public function getUid($fd)
    {
        return $this->yac->get($this->getKey($fd, 'fu'));
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

        $this->yac->set($this->getKey($uid), \json_encode($data));
        $this->upChannel($uid, $fd);
    }

    public function addChannel($uid, $channel)
    {
        $uinfo = $this->get($uid);
        $uinfo['types'][$channel] = 1;
        if ($this->yac->upChannel($uid, $uinfo['fd'], $channel)) {
            $this->yac->set($this->getKey($uid), json_encode($uinfo));
        }
    }

    public function delChannel($uid, $channel='ALL')
    {
        $channelInfo = $this->getChannel($channel);
        if(!empty($channelInfo[$uid])) {
            unset($channelInfo[$uid]);
            $this->yac->set($this->getKey($channel), json_encode($channelInfo));
            $uinfo = $this->get($uid);
            if(!empty($uinfo['types'][$channel])) {
                unset($uinfo['types'][$channel]);
                $this->yac->set($this->getKey($uid), json_encode($uinfo));
            }
        }

        return true;
    }

    private function upChannel($uid, $fd, $channel = 'ALL')
    {   
        $channelInfo = $this->getChannel($channel);
        $channelInfo[$uid] = $fd;
        $this->yac->set($this->getKey($channel), json_encode($channelInfo));
        return true;
    }

    public function getChannel($channel = 'ALL')
    {
        return json_decode($this->yac->get($this->getKey($channel)), true);
    }

    public function get($uid)
    {
        $data = $this->yac->get($this->getKey($uid));
        if (empty($data)) {
            return array();
        }

        return json_decode($data, true);
    }

    public function uphb($uid)
    {
        $uinfo = $this->get($uid);
        if (empty($uinfo)) {
            return false;
        }
        $uinfo['time'] = time();
        return $this->yac->set($this->getKey($uid), json_encode($uinfo));
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
        if (null === $uid) {
            $uid = $this->getUid($fd);
        }
        if ($old) {
            $this->yac->delete($this->getKey($fd, 'fu'));
        }
        $this->yac->delete($this->getKey($fd, 'buff'));
        if (empty($uid)) {
            return;
        }
        $uinfo = $this->get($uid);
        if (!empty($uinfo)) {
            $this->yac->delete($this->getKey($uid));
            foreach ($uinfo['types'] as $type => $val) {
                $this->delChannel($uid, $type);
            }
        }
        return true;
    }

    public function getBuff($fd, $prev='buff')
    {
        return $this->yac->get($this->getKey($fd, $prev));
    }

    public function setBuff($fd, $data, $prev='buff')
    {
        return $this->yac->set($this->getKey($fd, $prev), $data);
    }

    public function delBuff($fd, $prev='buff')
    {
        return $this->yac->delete($this->getKey($fd, $prev));
    }

    private function getKey($uid, $prefix = 'uf')
    {
        return "{$prefix}_{$uid}_" . ZConfig::getField('connection', 'prefix');
    }

    public function clear()
    {
        $this->yac->clear();
    }
}