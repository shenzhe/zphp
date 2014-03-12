<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 * route处理类
 */
namespace ZPHP\Core;
use ZPHP\Controller\IController,
    ZPHP\Core\Factory,
    ZPHP\Core\Config,
    ZPHP\ZPHP;

class Route
{
    public static function route($server)
    {
        $action = Config::get('ctrl_path', 'ctrl') . '\\' . $server->getCtrl();
        $class = Factory::getInstance($action);
        if (!($class instanceof IController)) {
            throw new \Exception("ctrl error");
        }
        $class->setServer($server);
        $view = $exception = null;
        
        try {
            $before = $class->_before();
        } catch (\Exception $e) {
            $exception = $e;
            $before = false;
        }        

        if ($before) {
            try {
                $method = $server->getMethod();
                if (\method_exists($class, $method)) {
                    $view = $class->$method();
                } else {
                    throw new \Exception("no method {$method}");
                }
            } catch (\Exception $e) {
                $exception = $e;
            }
        }
        $class->_after();
        if ($exception !== null) {
            if('Socket' == Config::get('server_mode', 'Http')) {
                call_user_func(Config::getField('project', 'exception_handler', 'ZPHP\ZPHP::exceptionHandler'), $exception);
                return;
            }
            throw $exception;
        }
        if (null === $view) {
            return;
        }
        return $server->display($view);
    }
}
