<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;

use ZPHP\Core\Config;
use ZPHP\Protocol\IProtocol;
use ZPHP\Common\Route as ZRoute;
use ZPHP\Protocol\Request;

class Rest implements IProtocol
{
    /**
     * 直接 parse $_REQUEST
     * @param $data
     * @return bool
     */
    public function parse($data)
    {
        $ctrlName = Config::getField('project', 'default_ctrl_name', 'main\\main');
        $apn = Config::getField('project', 'ctrl_name', 'a');
        if (isset($data[$apn])) {
            $ctrlName = \str_replace('/', '\\', $data[$apn]);
        }

        $pathInfo = Request::getPathInfo();
        if (!empty($pathInfo) && '/' !== $pathInfo) {
            $routeMap = ZRoute::match(Config::get('route', false), $pathInfo);
            if (is_array($routeMap)) {
                $ctrlName = \str_replace('/', '\\', $routeMap[0]);
                if (!empty($routeMap[2]) && is_array($routeMap[2])) {
                    //参数优先
                    $data = $data + $routeMap[2];
                }
            }
        }

        $method = Request::getHttpMethod();
        Request::init($ctrlName, $method, $data, Config::getField('project', 'view_mode', 'Php'));
        return true;
    }
}
