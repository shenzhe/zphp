<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Socket\Adapter;
use ZPHP\Socket\IServer,
    ZPHP\Core\Config as ZConfig,
    ZPHP\Protocol;
use React\EventLoop\Factory as eventLoop,
    React\Socket\Server as server;

class React implements IServer
{
    private $client;
    private $config;
    private $serv;
    private $loop;
    private $pids;

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
        $workMode = ZConfig::getFiled('socket', 'work_mode', 1);
        if (1 === $workMode) {
            $workNum = ZConfig::getFiled('socket', 'worker_num', 1);
            for ($i = 0; $i < $workNum; $i++) {
                if (($pid1 = pcntl_fork()) === 0) { //子进程
                    $pid = posix_getpid();
                    $this->pids[$pid] = 0;
                    $this->client->work();
                    exit();
                }
            }
        }

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

            $conn->on('close', function () use ($client, $conn) {
                $client->onClose($conn);
            });
        });
        $this->serv->listen($this->config['port'], $this->config['host']);
        $this->loop->run();

    }
}