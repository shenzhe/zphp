<?php

namespace ZPHP\Conn\Adapter;
use ZPHP\Core\Config as ZConfig,
    ZPHP\Conn\IConn;

/**
 *  swoole table 容器
 */
class Swoole implements IConn
{

    private $table;

    public function __construct($config)
    {
        if(empty($this->table)) {
            $table = new swoole_table(1024);
            $table->column('data', swoole_table::TYPE_STRING, 64);
            $table->create();
            $this->table = $table;
        }
    }


    public function addFd($fd, $uid = 0)
    {
        return $this->table->set($this->getKey($fd, 'fu'), ['data'=>$uid]);
    }


    public function getUid($fd)
    {
        return $this->table->get($this->getKey($fd, 'fu'));
    }

    public function add($uid, $fd)
    {
        $uinfo = $this->get($uid);
        if (!empty($uinfo)) {
            $this->delete($uid);
        }
        $data = array(
            'fd' => $fd,
            'time' => time(),
            'types' => array('ALL' => 1)
        );

        $this->table->set($this->getKey($uid), \json_encode($data));
        $this->table->hSet($this->getKey('ALL'), $uid, $fd);
    }

    public function addChannel($uid, $channel)
    {
        $uinfo = $this->get($uid);
        $uinfo['types'][$channel] = 1;
        if ($this->table->hSet($this->getKey($channel), $uid, $uinfo['fd'])) {
            $this->table->set($this->getKey($uid), json_encode($uinfo));
        }
    }

    public function delChannel($uid, $channel)
    {
        if($this->table->hDel($this->getKey($channel), $uid)){
            $uinfo = $this->get($uid);
            if(isset($uinfo['types'][$channel])) {
                unset($uinfo['types'][$channel]);
                $this->table->set($this->getKey($uid), json_encode($uinfo));
            }
        }
        return true;
    }

    public function getChannel($channel = 'ALL')
    {
        return $this->table->hGetAll($this->getKey($channel));
    }

    public function get($uid)
    {
        $data = $this->table->get($this->getKey($uid));
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
        return $this->table->set($this->getKey($uid), json_encode($uinfo));
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
            $this->table->delete($this->getKey($fd, 'fu'));
        }
        $this->table->delete($this->getKey($fd, 'buff'));
        if (empty($uid)) {
            return;
        }
        $uinfo = $this->get($uid);
        if (!empty($uinfo)) {
            $this->table->delete($this->getKey($uid));
            foreach ($uinfo['types'] as $type => $val) {
                $this->table->hDel($this->getKey($type), $uid);
            }
        }
    }

    public function getBuff($fd, $prev='buff')
    {
        return $this->table->get($this->getKey($fd, $prev));
    }

    public function setBuff($fd, $data, $prev='buff')
    {
        return $this->table->set($this->getKey($fd, $prev), $data);
    }

    public function delBuff($fd, $prev='buff')
    {
        return $this->table->delete($this->getKey($fd, $prev));
    }

    private function getKey($uid, $prefix = 'uf')
    {
        return "{$prefix}_{$uid}_" . ZConfig::getField('connection', 'prefix');
    }

    public function clear()
    {
        $this->table->flushDB();
    }
}