<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 * route处理类
 */
namespace ZPHP\Core;

use ZPHP\Controller\IController;
use ZPHP\Protocol\Request;
use ZPHP\Protocol\Response;
use ZPHP\Session\Swoole as SSESSION;

class Route
{
    public static function route()
    {
        $action = Config::get('ctrl_path', 'ctrl') . '\\' . Request::getCtrl();
        $class = Factory::getInstance($action);

        try {

            if (!($class instanceof IController)) {
                throw new \Exception("ctrl error");
            } else {
                $view = null;
                if ($class->_before()) {
                    $method = Request::getMethod();
                    if (!method_exists($class, $method)) {
                        throw new \Exception("method error");
                    }
                    $view = $class->$method();
                } else {
                    throw new \Exception($action . ':' . Request::getMethod() . ' _before() no return true');
                }
                $class->_after();
                if (Request::isLongServer()) {
                    SSESSION::save();
                }
                return Response::display($view);
            }
        } catch (\Exception $e) {
            if (Request::isLongServer()) {
                $result = \call_user_func(Config::getField('project', 'exception_handler', 'ZPHP\ZPHP::exceptionHandler'), $e);
                if ($class instanceof IController) {
                    $class->_after();
                }
                return $result;
            }
            if ($class instanceof IController) {
                $class->_after();
            }
            throw $e;
        }
    }
}
