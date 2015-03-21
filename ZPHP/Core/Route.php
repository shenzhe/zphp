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
                $view = $class->$method();
            } catch (\Exception $e) {
                $exception = $e;
            }
        }
        $class->_after();
        if ($exception !== null) {
            if(Config::get('is_long_service', 0)) {
                return call_user_func(Config::getField('project', 'exception_handler', 'ZPHP\ZPHP::exceptionHandler'), $exception);
            }
            throw $exception;
            return;
        }
        if (null === $view) {
            return;
        }
        return $server->display($view);
    }
}
