<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Cache\Adapter;

use ZPHP\Cache\Factory;
use ZPHP\Cache\ICache;
use ZPHP\Core\Config;
use ZPHP\Protocol\Request;


/**
 * Class Task
 * @package ZPHP\Cache\Adapter
 * @desc swoole的进程内缓存
 */
class Task implements ICache
{
    public $tid = 0;
    const _PRE = '_TASK_CACHE_';

    public function __construct($config = null)
    {

    }

    public function enable()
    {
        if (!Request::isLongServer()) { //不是常驻服务
            return false;
        }
        if (Config::getField('socket', 'task_worker_num') < 1) {
            return false;
        }
        return true;
    }

    public function selectDb($db)
    {
        return $this->tid = $db;
    }

    private function packData($data)
    {
        return \ZPHP\Manager\Task::$map['cache'] . json_encode($data);
    }

    public function add($key, $value, $timeOut = 0)
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'add',
            'key' => $value,
            'ttl' => $timeOut,
        ]), $this->tid);
        return;
    }

    public function set($key, $value, $timeOut = 0)
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'set',
            'key' => $key,
            'value' => $value,
            'ttl' => $timeOut,
        ]), $this->tid);
    }

    public function get($key)
    {
        $server = Request::getSocket();
        return $server->taskwait($this->packData([
            'type' => 'get',
            'key' => $key
        ]), 0.01, $this->tid);
    }

    public function delete($key)
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'delete',
            'key' => $key
        ]), $this->tid);
    }

    public function increment($key, $step = 1)
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'increment',
            'key' => $key
        ]), $this->tid);
    }

    public function decrement($key, $step = 1)
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'decrement',
            'key' => $key
        ]), $this->tid);
    }

    public function clear()
    {
        $server = Request::getSocket();
        $server->task($this->packData([
            'type' => 'clear'
        ]), $this->tid);
    }

    public function all()
    {
        $server = Request::getSocket();
        return $server->taskwait($this->packData([
            'type' => 'all'
        ]), 0.01, $this->tid);
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