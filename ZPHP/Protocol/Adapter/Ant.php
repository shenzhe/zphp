<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;

use ZPHP\Core\Config;
use ZPHP\Protocol\IProtocol;
use ZPHP\Protocol\Request;
use ZPHP\Common\Route as ZRoute;

class Ant implements IProtocol
{
    public function parse($_data)
    {
        $ctrlName = Config::getField('project', 'default_ctrl_name', 'main');
        $methodName = Config::getField('project', 'default_method_name', 'main');
        if (Request::isHttp()) {
            $data = $_data;
//            Request::addHeaders(Request::getRequest()->header, true);
            $pathInfo = Request::getPathInfo();
            if (!empty($pathInfo) && '/' !== $pathInfo) {
                $routeMap = ZRoute::match(Config::get('route', false), $pathInfo);
                if (is_array($routeMap)) {
                    $ctrlName = \str_replace('/', '\\', $routeMap[0]);
                    $methodName = $routeMap[1];
                    if (!empty($routeMap[2]) && is_array($routeMap[2])) {
                        //参数优先
                        $data = $data + $routeMap[2];
                    }
                }
            }
            $header = Request::getRequest()->header;
            if (!is_array($header)) {
                $header = [];
            }
            Request::addHeaders($header, true);
        } else {
            if (class_exists('swoole_serialize')) {
                $message = \swoole_serialize::unpack($_data);
            } else {
                $message = json_decode($_data, true);
            }
            if (is_array($message[0])) {
                Request::addHeaders($message[0], true);
            } else {
                Request::addHeaders([], true);
            }
            $data = is_array($message[1]) ? $message[1] : [];
        }
        $apn = Config::getField('project', 'ctrl_name', 'a');
        $mpn = Config::getField('project', 'method_name', 'm');
        if (isset($data[$apn])) {
            $ctrlName = \str_replace('/', '\\', $data[$apn]);
        }
        if (isset($data[$mpn])) {
            $methodName = $data[$mpn];
        }

        Request::init($ctrlName, $methodName, $data, Config::getField('project', 'view_mode', 'Json'));
        return true;
    }
}
