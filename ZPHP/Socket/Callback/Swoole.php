<?php


namespace ZPHP\Socket\Callback;

use ZPHP\Socket\ICallback;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Core;
use ZPHP\Protocol;


abstract class Swoole implements ICallback
{

    protected $protocol;

    protected $serv;

    /**
     * @param $server
     * @throws \Exception
     * @desc 服务启动，设置进程名及写主进程id
     */
    public function onStart()
    {
        $server = func_get_args()[0];
        swoole_set_process_name(ZConfig::get('project_name') .
            ' server running ' .
            ZConfig::getField('socket', 'server_type', 'tcp') . '://' . ZConfig::getField('socket', 'host') . ':' . ZConfig::getField('socket', 'port')
            . "  master:" . $server->master_pid);
        if (!empty(ZConfig::getField('project', 'pid_path'))) {
            file_put_contents(ZConfig::getField('project', 'pid_path') . DS . ZConfig::get('project_name') . '_master.pid', $server->master_pid);
        }
    }

    /**
     * @param $server
     * @throws \Exception
     */
    public function onShutDown()
    {
        $server = func_get_args()[0];
        if (!empty(ZConfig::getField('project', 'pid_path'))) {
            $filename = ZConfig::getField('project', 'pid_path') . DS . ZConfig::get('project_name') . '_master.pid';
            if (is_file($filename)) {
                unlink($filename);
            }
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
            ' server manager:' . $server->manager_pid);
        if (!empty(ZConfig::getField('project', 'pid_path'))) {
            file_put_contents(ZConfig::getField('project', 'pid_path') . DS . ZConfig::get('project_name') . '_manager.pid', $server->manager_pid);
        }
    }

    /**
     * @param $server
     * @throws \Exception
     * @desc 服务启动，设置进程名
     */
    public function onManagerStop($server)
    {
        if (!empty(ZConfig::getField('project', 'pid_path'))) {
            $filename = ZConfig::getField('project', 'pid_path') . DS . ZConfig::get('project_name') . '_manager.pid';
            if (is_file($filename)) {
                unlink($filename);
            }
        }
    }

    public function onWorkerStart($server, $workerId)
    {
        $workNum = ZConfig::getField('socket', 'worker_num');
        if ($workerId >= $workNum) {
            swoole_set_process_name(ZConfig::get('project_name') . " server tasker  num: ".($server->worker_id - $workNum)." pid " . $server->worker_pid);
        } else {
            swoole_set_process_name(ZConfig::get('project_name') . " server worker  num: {$server->worker_id} pid " . $server->worker_pid);
        }

        if(function_exists('opcache_reset')) {
            opcache_reset();
        }
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
