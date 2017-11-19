<?php

namespace ZPHP\Conn\Adapter;

use ZPHP\Core\Config as ZConfig;
use ZPHP\Conn\IConn;
use ZPHP\Protocol\Request;

/**
 *  php内置数组
 */
class Task implements IConn
{

    private $tid = 0;

    public function __construct($config=null)
    {

    }

    private function packData($data)
    {
        return \ZPHP\Manager\Task::$map['conn'] . json_encode($data);
    }

    public function addFd($fd, $uid = 0)
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'addFd',
            'fd' => $fd,
            'uid' => $uid,
        ]), $this->tid);
        return;
    }


    public function getUid($fd)
    {
        /**
         * @var $server \swoole_server
         */
        $server = Request::getSocket();
        return $server->taskwait($this->packData([
            'type' => 'getUid',
            'fd' => $fd
        ]), 0.01, $this->tid);
    }

    public function add($uid, $fd)
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'add',
            'fd' => $fd,
            'uid' => $uid,
        ]), $this->tid);
    }

    public function addChannel($uid, $channel)
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'addChannel',
            'uid' => $uid,
            'channel' => $channel,
        ]), $this->tid);
    }

    public function delChannel($uid, $channel)
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'delChannel',
            'uid' => $uid,
            'channel' => $channel,
        ]), $this->tid);
    }

    public function getChannel($channel = 'ALL')
    {
        $server = Request::getSocket();
        return $server->taskwait($this->packData([
            'type' => 'getChannel',
            'channel' => $channel,
        ]), 0.01, $this->tid);
    }

    public function get($uid)
    {
        $server = Request::getSocket();
        return $server->taskwait($this->packData([
            'type' => 'get',
            'uid' => $uid,
        ]), 0.01, $this->tid);
    }

    public function uphb($uid)
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'uphb',
            'uid' => $uid,
        ]), $this->tid);
        return true;
    }

    public function heartbeat($uid, $ntime = 60)
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'heartbeat',
            'uid' => $uid,
            'ntime' => $ntime,
        ]), $this->tid);
    }

    public function delete($fd, $uid = null, $old = true)
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'delete',
            'fd' => $fd,
            'uid' => $uid,
            'old' => $old,
        ]), $this->tid);
    }

    public function getBuff($fd, $prev = 'buff')
    {
        $server = Request::getSocket();
        return $server->taskwait($this->packData([
            'type' => 'getBuff',
            'fd' => $fd,
        ]), 0.01, $this->tid);
    }

    public function setBuff($fd, $data, $prev = 'buff')
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'setBuff',
            'fd' => $fd,
        ]), $this->tid);
        return true;
    }

    public function delBuff($fd, $prev = 'buff')
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'delBuff',
            'fd' => $fd,
        ]), $this->tid);
        return true;
    }

    public function clear()
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'clear',
        ]), $this->tid);
    }

    public function flush()
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'flush',
            'workerId' => $this->tid,
        ]), $this->tid);
    }

    public function load()
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'load',
            'workerId' => $this->tid,
        ]), $this->tid);
    }
}