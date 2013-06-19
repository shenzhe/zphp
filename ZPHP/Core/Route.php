<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 * route处理类
 */
namespace ZPHP\Core;
use ZPHP\Controller\IController,
    ZPHP\Core\Factory,
    ZPHP\View\IView;

class Route
{
    public static function route($server)
    {
        $class = Factory::getInstance($server->getAction());
        if ($class instanceof IController) {
            $class->setServer($server);
            $before = $class->_before();
        }
        $view = $exception = null;
        if ($before) {
            try {
                $method = $server->getMethod();
                if (method_exists($class, $method)) {
                    $view = $class->$method();
                } else {
                    throw new \Exception("no method {$method}");
                }
            } catch (\Exception $e) {
                $exception = $e;
            }
        }
        if ($class instanceof IController) {
            $class->_after();
        }
        if ($exception !== null) {
            throw $exception;
        }
        if ($view instanceof IView) {
            if (\method_exists($view, 'display')) {
                $view->display();
            } else {
                $server->display($view->output());
            }
        }
    }
}
