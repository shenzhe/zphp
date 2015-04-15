<?php

namespace ZPHP\Conn\Adapter;
use ZPHP\Core\Config as ZConfig,
    ZPHP\Conn\IConn,
    ZPHP\Manager\Redis as ZRedis;

/**
 *  redis 容器
 */
class Redis implements IConn
{

    private $redis;

    public function __construct($config)
    {
        if(empty($this->redis)) {
            $this->redis = ZRedis::getInstance($config);
            $db = ZConfig::getField('connection', 'db', 0);
            if(!empty($db)) {
                $this->redis->select($db);
            }
        }
    }


    public function addFd($fd, $uid = 0)
    {
        return $this->redis->set($this->getKey($fd, 'fu'), $uid);
    }


    public function getUid($fd)
    {
        return $this->redis->get($this->getKey($fd, 'fu'));
    }

    public function add($uid, $fd)
    {
        $uinfo = $this->get($uid);
        if (!empty($uinfo)) {
            $ofd = $uinfo['fd'];
            $oid = $this->getUid($fd);
            if($ofd == $uid) {
                $this->delete($ofd, $uid);
            } else {
                $uinfo = [];
            }
        }
        $data = array(
            'fd' => $fd,
            'time' => time(),
            'types' => array('ALL' => 1)
        );

        $this->redis->set($this->getKey($uid), \json_encode($data));
        $this->redis->hSet($this->getKey('ALL'), $uid, $fd);
        return $uinfo;
    }

    public function addChannel($uid, $channel)
    {
        $uinfo = $this->get($uid);
        if(empty($uinfo)) return;
        $uinfo['types'][$channel] = 1;
        if ($this->redis->hSet($this->getKey($channel), $uid, $uinfo['fd'])) {
            $this->redis->set($this->getKey($uid), json_encode($uinfo));
        }
    }

    public function delChannel($uid, $channel)
    {
        if($this->redis->hDel($this->getKey($channel), $uid)){
            $uinfo = $this->get($uid);
            if(!empty($uinfo['types'][$channel])) {
                unset($uinfo['types'][$channel]);
                $this->redis->set($this->getKey($uid), json_encode($uinfo));
            }
        }
        return true;
    }

    public function getChannel($channel = 'ALL')
    {
        return $this->redis->hGetAll($this->getKey($channel));
    }

    public function get($uid)
    {
        $data = $this->redis->get($this->getKey($uid));
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
        return $this->redis->set($this->getKey($uid), json_encode($uinfo));
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
            $this->redis->delete($this->getKey($fd, 'fu'));
        }
        if (empty($uid)) {
            return;
        }
        $uinfo = $this->get($uid);
        if (!empty($uinfo)) {
            $this->redis->delete($this->getKey($uid));
            foreach ($uinfo['types'] as $type => $val) {
                $this->redis->hDel($this->getKey($type), $uid);
            }
        }
    }

    public function getBuff($fd, $prev='buff')
    {
        return $this->redis->get($this->getKey($fd, $prev));
    }

    public function setBuff($fd, $data, $prev='buff')
    {
        return $this->redis->set($this->getKey($fd, $prev), $data);
    }

    public function delBuff($fd, $prev='buff')
    {
        return $this->redis->delete($this->getKey($fd, $prev));
    }

    private function getKey($uid, $prefix = 'uf')
    {
        return "{$prefix}_{$uid}_" . ZConfig::getField('connection', 'prefix');
    }

    public function clear()
    {
        $this->redis->flushDB();
    }
}