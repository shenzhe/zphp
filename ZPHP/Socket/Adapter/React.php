<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Socket\Adapter;
use ZPHP\Socket\IServer;
use React\EventLoop\Factory as eventLoop,
    React\Socket\Server as server;

class React implements IServer
{
    private $client;
    private $config;
    private $serv;
    private $loop;

    public function __construct($config)
    {
        $loop = eventLoop::create();
        $this->loop = $loop;
        $this->serv = new server($loop);
        $this->config = $config;
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    public function run()
    {
        $client = $this->client;
        $client->onStart($this->serv);
        $this->serv->on('connection', function ($conn) use ($client) {
            $client->onConnect($conn);
            $conn->on('data', function ($datas) use ($conn, $client) {
                $client->onReceive($conn, $datas);
            });

            $conn->on('end', function () use ($conn, $client) {
                $conn->end();
            });

            $conn->on('close', function () use ($client) {
                $client->onClose();
            });
        });
        $this->serv->listen($this->config['port'], $this->config['host']);
        $this->loop->run();
    }
}