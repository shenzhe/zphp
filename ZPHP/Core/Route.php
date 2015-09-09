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
use ZPHP\Protocol\Request;
use ZPHP\Protocol\Response;

class Route
{
    public static function route()
    {
        $action = Config::get('ctrl_path', 'ctrl') . '\\' . Request::getCtrl();
        $view = null;

        try {
            $class = Factory::getInstance($action);
            if (!($class instanceof IController)) {
                throw new \Exception("ctrl error");
            } else {
                $class->_before();
                $method = Request::getMethod();
                if(!method_exists($class, $method)) {
                    throw new \Exception("method error");
                }
                $view = $class->$method();
                $class->_after();
                if (null === $view) {
                    return null;
                }
                return Response::display($view);
            }
        }catch (\Exception $e) {
            if(Request::isLongServer()) {
                return \call_user_func(Config::getField('project', 'exception_handler', 'ZPHP\ZPHP::exceptionHandler'), $e);
            }
            throw $e;
        }
    }
}
