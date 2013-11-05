<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Socket\Adapter;
use ZPHP\Socket\IServer;

class Swoole implements IServer
{
    private $client;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->serv = \swoole_server_create($config['host'], $config['port'], $config['work_mode']);
        \swoole_server_set($this->serv, array(
            'timeout' => empty($config['timeout']) ? 2.5 : $config['timeout'], //select and epoll_wait timeout.
            'poll_thread_num' => empty($config['poll_thread_num']) ? 2 : $config['poll_thread_num'], //reactor thread num
            'writer_num' => empty($config['writer_num']) ? 2 : $config['writer_num'], //writer thread num
            'worker_num' => $config['worker_num'], //worker process num
            'backlog' => empty($config['backlog']) ? 128 : $config['backlog'], //listen backlog));
            'max_request' => empty($config['max_request']) ? 1000 : $config['max_request'],
            'max_conn' => empty($config['max_conn']) ? 100000 : $config['max_conn'],
            'dispatch_mode' => empty($config['dispatch_mode']) ? 2 : $config['dispatch_mode'],
            'log_file' => empty($config['log_file']) ? '/tmp/swoole.log' : $config['log_file'],
            'daemonize' => $config['daemonize'],
        ));
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    public function run()
    {
        \swoole_server_handler($this->serv, 'onStart', array($this->client, 'onStart'));
        \swoole_server_handler($this->serv, 'onConnect', array($this->client, 'onConnect'));
        \swoole_server_handler($this->serv, 'onReceive', array($this->client, 'onReceive'));
        \swoole_server_handler($this->serv, 'onClose', array($this->client, 'onClose'));
        \swoole_server_handler($this->serv, 'onShutdown', array($this->client, 'onShutdown'));
        if(method_exists($this->client, 'onTimer')) {
            \swoole_server_handler($this->serv, 'onTimer', array($this->client, 'onTimer'));
        }
        if(method_exists($this->client, 'onWorkerStart')) {
            \swoole_server_handler($this->serv, 'onWorkerStart', array($this->client, 'onWorkerStart'));
        }
        if(method_exists($this->client, 'onWorkerStop')) {
            \swoole_server_handler($this->serv, 'onWorkerStop', array($this->client, 'onWorkerStop'));
        }
        if (!empty($this->config['times'])) {
            foreach ($this->config['times'] as $time) {
                \swoole_server_addtimer($this->serv, $time);
            }
        }
        \swoole_server_start($this->serv);
    }
}