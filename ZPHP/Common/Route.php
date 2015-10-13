<?php
namespace ZPHP\Common;

use ZPHP\Core\Config as ZConfig;
use ZPHP\Cache\Factory as ZCache;

/**
 * Route
 *
 * @package ZPHP\Common
 *
 */
class Route
{
    /**
     *  路由匹配
     *  param $route       //config里的route配置数组
     *  param $pathinfo    //默认取值$_SERVER['PATH_INFO'];
     *  return array("ctrl class", "method", array params);
     *  examples:
     *  config/route.php
     *  return array(
     *      'static'=>array(
     *           'reg'=>array(
     *              'main\\main',
     *              'reg'，
     *              array("callurl"=>'http://zphp.com'),    //默认参数，可选项
     *           ),
     *      )
     *      'dynamic'=>array(
     *           '/^\/product\/(\d+)$/iU''=>array(                                  //匹配 /product/123 将被匹配
     *              'main\\product',            //ctrl class
     *              'show',                     //ctrl method
     *              array('id'),                //匹配参数                          //名为id的参数将被赋值 123
     *              '/product/{id}'             //格式化
     *           ),
     *      )
     *
     *
     *  )
     *
     *  http://host/reg 将会匹配到 static 中 reg 的定义规则，将执行apps/ctrl/main/main.php中的reg方法，并有默认参数callurl值为http://zphp.com
     *  http://host/product/123 将会匹配到 dynamic 中 /^\/product\/(\d+)$/iU 的定义规则，
     *  将执行 apps/ctrl/main/product.php中的show方法，并把123解析为参数id的值
     */
    public static function match($route, $pathinfo)
    {
        if (empty($route) || empty($pathinfo)) {
            return false;
        }

        

        /*if(isset($route['ext'])) {
            $pathinfo = str_replace($route['ext'], '', $pathinfo);
        }*/
        $pathinfo = explode('.', $pathinfo);
        $pathinfo = $pathinfo[0];

        if (isset($route['static'][$pathinfo])) {
            return $route['static'][$pathinfo];
        }

        if (!empty($route['cache'])) {
            $config = ZConfig::getField('cache', 'locale', array());
            if (!empty($config)) {
                $cache = ZCache::getInstance($config['adapter'], $config);
                $result = $cache->get($pathinfo);
                if (!empty($result)) {
                    return json_decode($result, true);
                }
            }
        }

        foreach ($route['dynamic'] as $regex => $rule) {
            if (!preg_match($regex, $pathinfo, $matches)) {
                continue;
            }
            if (!empty($matches)) {
                unset($matches[0]);
                foreach ($matches as $index => $val) {
                    $rule[0] = str_replace("{{$index}}", $val, $rule[0], $count1);
                    $rule[1] = str_replace("{{$index}}", $val, $rule[1], $count2);
                    if (($count1 + $count2) > 0) {
                        unset($matches[$index]);
                    }
                }
                if (!empty($rule[2]) && !empty($matches)) {
                    $rule[2] = array_combine($rule[2], $matches);
                }
                if (isset($cache)) {
                    $cache->set($pathinfo, json_encode($rule));
                }
                return $rule;
            }
        }
        return false;
    }

    /**
     *  返回友好的url
     *  param $ctrl         //ctrl class
     *  param $method       //所要执行的method
     *  param $params       //额外参数
     *  return
     *  如果是静态路由，直接返回 路由的key值
     *  如果是动态路由，会根据匹配到配置的友好url进行格式化处理
     *  examples:
     *  config/route.php
     *  return array(
     *      'static'=>array(
     *           'reg'=>array(
     *              'main\\main', 'reg'
     *           ),
     *      )
     *      'dynamic'=>array(
     *           '/^\/product\/(\d+)$/iU''=>array(                                  //匹配 /product/123 将被匹配
     *              'main\\product',            //ctrl class
     *              'show',                     //ctrl method
     *              array('id'),                //匹配参数                          //名为id的参数将被赋值 123
     *              '/product/{id}'             //格式化
     *           ),
     *      )
     *
     *
     *  )
     *  如果配置了route:
     *  调用 \ZPHP\Common\Route::makeUrl('main\\main', 'reg'),  将生成url http://host/reg
     *  调用 \ZPHP\Common\Route::makeUrl('main\\product', 'show', array("id"=>123, "uid"=>321)),  将生成url http://host/product/123?uid=321
     */
    public static function makeUrl($ctrl, $method, $params = array())
    {
        $appUrl = ZConfig::getField('project', 'app_host', "");
        $ctrlName = ZConfig::getField('project', 'ctrl_name', 'a');
        $methodName = ZConfig::getField('project', 'method_name', 'm');
        if (empty($appUrl)) {
            $appUrl = '/';
        } else {
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
                $appUrl = 'https://' . $appUrl;
            } else {
                $appUrl = 'http://' . $appUrl;
            }
        }
        $routes = ZConfig::get('route', false);
        if (!empty($routes)) {
            if (isset($routes['cache'])) {
                if (!empty($route['cache'])) {
                    $config = ZConfig::getField('cache', 'locale', array());
                    if (!empty($config)) {
                        $cache = ZCache::getInstance($config['adapter'], $config);
                        $cacheKey = self::getKey($ctrl, $method, $params);
                        $result = $cache->get($cacheKey);
                        if (!empty($result)) {
                            return $result;
                        }
                    }
                }
                unset($routes['cache']);
            }
            $ext = '';
            if(!empty($routes['ext'])) {
                $ext = $routes['ext'];
                unset($routes['ext']);
            }
            $result = false;
            foreach ($routes as $type => $rules) {
                foreach ($rules as $path => $rule) {
                    if ($rule[0][0] == '{' || $rule[0] == str_replace('/', '\\', $ctrl)) {
                        if ($rule[1][0] != '{' && $rule[1] != $method) {
                            continue;
                        }
                        if ('static' == $type) {
                            if (empty($params)) {
                                if('' == $path || '/'==$path) {
                                    $result = $appUrl . $path;
                                } else {
                                    $result = $appUrl . $path. $ext;
                                }
                                
                            } else {
                                $result = $appUrl . $path . $ext . '?' . http_build_query($params);
                            }
                        } else {
                            $realPath = $rule[3];
                            $realPath = str_replace(array('{c}', '{m}'), array($ctrl, $method), $realPath);
                            if (!empty($rule[2])) {
                                foreach ($rule[2] as $key) {
                                    if (isset($params[$key])) {
                                        $realPath = str_replace("{{$key}}", $params[$key], $realPath);
                                        unset($params[$key]);
                                    }
                                }
                            }
                            if (empty($params)) {
                                $result = $appUrl . $realPath. $ext;
                            } else {
                                $result = $appUrl . $realPath . $ext . '?' . http_build_query($params);
                            }
                        }
                        if ($result) {
                            if (isset($cacheKey)) {
                                $cache->set($cacheKey, $result);
                            }
                            return $result;
                        }
                    }
                }
            }
        }
        if (empty($params)) {
            return $appUrl . "?{$ctrlName}={$ctrl}&{$methodName}={$method}";
        }
        return $appUrl . "?{$ctrlName}={$ctrl}&{$methodName}={$method}&" . http_build_query($params);
    }

    private static function getKey()
    {
        return ZConfig::getField('project', 'project_name') . "_route_" . json_encode(func_get_args());
    }
}
