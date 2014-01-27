<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 所需要库地址： https://github.com/reactphp/react
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
        if (3 === $this->config['work_mode']) {
            for ($i = 0; $i < $this->config['worker_num']; $i++) {
               $this->fork();
            }
        }

        $client = $this->client;
        $client->onStart($this);
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

    public function fork()
    {
        if (($pid1 = pcntl_fork()) === 0) { //子进程
            $pid = posix_getpid();
            $this->pids[$pid] = 0;
            $this->client->onWorkerStart();
            exit();
        }
    }

    public function addRequest($pid)
    {
        $this->pids[$pid]++;
    }

    public function check()
    {
        if(empty($this->config['max_request'])) {
            return ;
        }
        foreach($this->pids as $pid=>$num) {
            if($num >= $this->config['max_request']) {
                unset($this->pids[$pid]);
                posix_kill($pid, SIGTERM);
                $this->fork();
            }
        }
    }
}
