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
        $this->serv = \swoole_server_create($config['host'], $config['port'], SWOOLE_PROCESS);
        \swoole_server_set($this->serv, $config['params']);
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