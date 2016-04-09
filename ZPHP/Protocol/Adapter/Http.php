<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;
use ZPHP\Core;
use ZPHP\View;
use ZPHP\Core\Config;
use ZPHP\Protocol\IProtocol;
use ZPHP\Common\Route as ZRoute;
use ZPHP\Protocol\Request;

class Http implements IProtocol
{
    /**
     * 直接 parse $_REQUEST
     * @param $_data
     * @return bool
     */
    public function parse($data)
    {
        $ctrlName = Config::getField('project', 'default_ctrl_name', 'main\\main');
        $methodName = Config::getField('project', 'default_method_name', 'main');
        $apn = Config::getField('project', 'ctrl_name', 'a');
        $mpn = Config::getField('project', 'method_name', 'm');
        if (isset($data[$apn])) {
            $ctrlName = \str_replace('/', '\\', $data[$apn]);
        }
        if (isset($data[$mpn])) {
            $methodName = $data[$mpn];
        }

        $pathInfo = Request::getPathInfo();
        if(!empty($pathInfo) && '/' !== $pathInfo) {
            $routeMap = ZRoute::match(Config::get('route', false), $pathInfo);
            if(is_array($routeMap)) {
                $ctrlName = \str_replace('/', '\\', $routeMap[0]);
                $methodName = $routeMap[1];
                if(!empty($routeMap[2]) && is_array($routeMap[2])) {
                    //参数优先
                    $data = $data + $routeMap[2];
                }
            }
        }
        Request::init($ctrlName, $methodName, $data, Config::getField('project', 'view_mode', 'Php'));
        return true;
    }
}
