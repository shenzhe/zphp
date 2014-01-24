<?php
namespace ZPHP\Common;

use ZPHP\Core\Config as ZConfig;

/**
 * Route
 *
 * @package ZPHP\Common
 *
 */
class Route
{
    public static function match($route, $pathinfo)
    {
        if(empty($route) || empty($pathinfo)) {
            return false;
        }

        if(isset($route['static'][$pathinfo])) {
            return $route['static'][$pathinfo];
        }
        foreach($route['dynamic'] as $regex=>$rule) {
            if(!preg_match($regex, $pathinfo, $matches)) {
                continue;
            }
            if(!empty($matches)) {
                unset($matches[0]);
                $rule[3] = array_combine($rule[3], $matches);
                return $rule;
            }
        }
        return false;
    }

    public static function makeUrl($action, $method, $params=array())
    {
        $appUrl = ZConfig::getField('project', 'app_host', "");
        $actionName = ZConfig::getField('project', 'action_name', 'a');
        $methodName = ZConfig::getField('project', 'method_name', 'm');
        if(empty($appUrl)) {
            $appUrl = '/';
        } else {
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
                $appUrl = 'https://'.$appUrl;
            } else {
                $appUrl = 'http://'.$appUrl;
            }
        }
        $routes = ZConfig::get('route', false);
        if(!empty($routes)) {
            foreach($routes as $type=>$rules) {
                foreach($rules as $path=>$rule) {
                    if($rule[0] == $action && $rule[1] == $method) {
                        if('static' == $type) {
                            if(!empty($params)) {
                                return $appUrl.$path.'?'.http_build_query($params);
                            }
                            return $appUrl.$path;
                        } else {
                            $realPath = $rule[3];
                            if(!empty($rule[2])) {
                                foreach($rule[2] as $key) {
                                    if(isset($params[$key])) {
                                        $realPath = str_replace("%{$key}%", $params[$key], $realPath);
                                        unset($params[$key]);
                                    }
                                }
                            }
                            if(!empty($params)){
                                return $appUrl.$path;
                            }
                            return $appUrl.$path.'?'.http_build_query($params);
                        }
                    }
                }
            }
        }
        return $appUrl."?{$actionName}={$action}&{$methodName}={$method}&".http_build_query($params);
    }
}
