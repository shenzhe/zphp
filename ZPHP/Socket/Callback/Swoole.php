<?php


namespace ZPHP\Socket\Callback;

use ZPHP\Socket\ICallback;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Protocol;


abstract class Swoole implements ICallback
{

    protected $protocol;

    protected $serv;

    /**
     * @throws \Exception
     * @desc 服务启动，设置进程名及写主进程id
     */
    public function onStart()
    {
        /**
         * @var $server \swoole_server
         */
        $server = func_get_args()[0];
        $ip = ZConfig::getField('socket', 'host');
        $port = ZConfig::getField('socket', 'port');
        swoole_set_process_name(ZConfig::get('project_name') .
            ZConfig::get('server_mode') . ' server running ' .
            ZConfig::getField('socket', 'server_type', 'tcp') . '://' . $ip . ':' . $port
            . " time:" . date('Y-m-d H:i:s') . "  master:" . $server->master_pid);
        $pidPath = ZConfig::getField('project', 'pid_path');
        if (!empty($pidPath)) {
            file_put_contents($pidPath . DS . ZConfig::get('project_name') . '_master.pid', $server->master_pid);
        }
        if ('Ant' == ZConfig::get('server_mode')) {
            $callback = ZConfig::getField('soa', 'register_callback', 'socket\Handler\Soa::register');
        } else {
            $callback = ZConfig::getField('soa', 'register_callback');
        }
        if (!empty($callback)) {
            echo call_user_func($callback, $server);
        }
    }

    /**
     * @throws \Exception
     */
    public function onShutDown()
    {
        $server = func_get_args()[0];
        $pidPath = ZConfig::getField('project', 'pid_path');
        if (!empty($pidPath)) {
            $filename = $pidPath . DS . ZConfig::get('project_name') . '_master.pid';
            if (is_file($filename)) {
                unlink($filename);
            }
            $filename = $pidPath . DS . ZConfig::get('project_name') . '_manager.pid';
            if (is_file($filename)) {
                unlink($filename);
            }
        }
        if ('Ant' == ZConfig::get('server_mode')) {
            $callback = ZConfig::getField('soa', 'drop_callback', 'socket\Handler\Soa::drop');
        } else {
            $callback = ZConfig::getField('soa', 'drop_callback');
        }
        if (!empty($callback)) {
            echo call_user_func($callback, $server);
        }
    }

    /**
     * @param $server
     * @throws \Exception
     * @desc 服务启动，设置进程名
     */
    public function onManagerStart($server)
    {
        swoole_set_process_name(ZConfig::get('project_name') .
            ZConfig::get('server_mode') . ' server manager:' . $server->manager_pid);
        $pidPath = ZConfig::getField('project', 'pid_path');
        if (!empty($pidPath)) {
            file_put_contents($pidPath . DS . ZConfig::get('project_name') . '_manager.pid', $server->manager_pid);
        }
    }

    /**
     * @param $server
     * @throws \Exception
     * @desc 服务关闭，删除进程id文件
     */
    public function onManagerStop($server)
    {
        $pidPath = ZConfig::getField('project', 'pid_path');
        if (!empty($pidPath)) {
            $filename = $pidPath . DS . ZConfig::get('project_name') . '_manager.pid';
            if (is_file($filename)) {
                unlink($filename);
            }
        }
    }

    public function onWorkerStart($server, $workerId)
    {
        $workNum = ZConfig::getField('socket', 'worker_num');
        if ($workerId >= $workNum) {
            swoole_set_process_name(ZConfig::get('project_name') . " server tasker  num: " . ($server->worker_id - $workNum) . " pid " . $server->worker_pid);
        } else {
            swoole_set_process_name(ZConfig::get('project_name') . " server worker  num: {$server->worker_id} pid " . $server->worker_pid);
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        Protocol\Request::setSocket($server);
    }

    public function onWorkerStop($server, $workerId)
    {
    }

    public function onWorkerError($server, $workerId, $workerPid, $errorCode)
    {

    }


    public function onConnect()
    {

    }

    public function doReceive($server, $fd, $from_id, $data)
    {
        Protocol\Request::setFd($fd);
        $this->onReceive($server, $fd, $from_id, $data);
    }

    abstract public function onReceive();

    public function onPacket($server, $data, $clientInfo)
    {

    }

    public function onClose()
    {

    }


    public function onTask($server, $taskId, $fromId, $data)
    {

    }

    public function onFinish($server, $taskId, $data)
    {

    }

    public function onPipeMessage($server, $fromWorerId, $data)
    {

    }


}
