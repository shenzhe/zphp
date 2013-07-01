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
            'timeout' => 2.5, //select and epoll_wait timeout.
            'poll_thread_num' => 2, //reactor thread num
            'writer_num' => 2, //writer thread num
            'worker_num' => $config['worker_num'], //worker process num
            'backlog' => 128, //listen backlog));
            'max_request' => empty($config['max_request']) ? 1000 : $config['max_request']
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
        \swoole_server_handler($this->serv, 'onTimer', array($this->client, 'onTimer'));
        if (!empty($this->config['times'])) {
            foreach ($this->config['times'] as $time) {
                \swoole_server_addtimer($this->serv, $time);
            }
        }
        \swoole_server_start($this->serv);
    }
}