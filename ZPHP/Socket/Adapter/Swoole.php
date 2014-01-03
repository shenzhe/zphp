<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 所需扩展地址：https://github.com/matyhtf/swoole
 */


namespace ZPHP\Socket\Adapter;
use ZPHP\Socket\IServer,
    ZPHP\Socket\ICallback;

class Swoole implements IServer
{
    private $client;
    private $config;
    private $serv;

    public function __construct(array $config)
    {
        if(!\extension_loaded('swoole')) {
            throw new \Exception("no swoole extension. get: https://github.com/matyhtf/swoole");
        }
        $this->config = $config;
        $this->serv = new \swoole_server($config['host'], $config['port'], $config['work_mode']);
        $this->serv->set(array(
            'timeout' => empty($config['timeout']) ? 2 : $config['timeout'], //select and epoll_wait timeout.
            'poll_thread_num' => empty($config['poll_thread_num']) ? 2 : $config['poll_thread_num'], //reactor thread num
            'writer_num' => empty($config['writer_num']) ? 2 : $config['writer_num'], //writer thread num
            'worker_num' => empty($config['worker_num']) ? 2 : $config['worker_num'], //worker process num
            'backlog' => empty($config['backlog']) ? 128 : $config['backlog'], //listen backlog));
            'max_request' => empty($config['max_request']) ? 1000 : $config['max_request'],
            'max_conn' => empty($config['max_conn']) ? 100000 : $config['max_conn'],
            'dispatch_mode' => empty($config['dispatch_mode']) ? 2 : $config['dispatch_mode'],
            'log_file' => empty($config['log_file']) ? '/tmp/swoole.log' : $config['log_file'],
            'daemonize' => empty($config['daemonize']) ? 0 : 1,
        ));
    }

    public function setClient($client)
    {
        if($clinet instanceof ICallback) {
            $this->client = $client;
        }
        
        throw new \Exception("client on instanceof ICallback");
    }

    public function run()
    {
        $this->serv->on('Start', array($this->client, 'onStart'));
        $this->serv->on('Connect', array($this->client, 'onConnect'));
        $this->serv->on('Receive', array($this->client, 'onReceive'));
        $this->serv->on('Close', array($this->client, 'onClose'));
        $this->serv->on('Shutdown', array($this->client, 'onShutdown'));
        if(method_exists($this->client, 'onTimer')) {
            $this->serv->on('Timer', array($this->client, 'onTimer'));
        }
        if(method_exists($this->client, 'onWorkerStart')) {
            $this->serv->on('WorkerStart', array($this->client, 'onWorkerStart'));
        }
        if(method_exists($this->client, 'onWorkerStop')) {
            $this->serv->on('WorkerStop', array($this->client, 'onWorkerStop'));
        }
        if(method_exists($this->client, 'onTask')) {
            $this->serv->on('Tash', array($this->client, 'onTask'));
        }
        if(method_exists($this->client, 'onFinish')) {
            $this->serv->on('Finish', array($this->client, 'onFinish'));
        }
        if (!empty($this->config['times'])) {
            foreach ($this->config['times'] as $time) {
                $this->serv->addtimer($time);
            }
        }
        $this->serv->start();
    }
}