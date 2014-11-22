<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 * 公用方法类
 */

namespace ZPHP\Common;

class Utils
{

    /**
     * 判断是否ajax方式
     * @return bool
     */
    public static function isAjax()
    {

        if (!empty($_REQUEST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
            return true;
        }
        return false;
    }

    public static function header($key, $val)
    {
        if(defined('USE_SWOOLE_HTTP_SERVER') && USE_SWOOLE_HTTP_SERVER) {
            \HttpServer::$response->header($key, $val);
        } else {
            \header("{$key}: {$val}");
        }
    }

    public static function status($code)
    {
        if(defined('USE_SWOOLE_HTTP_SERVER') && USE_SWOOLE_HTTP_SERVER) {
            \HttpServer::$response->status($code);
        } else {
            \http_response_code($code);
        }
    }

    public static function setcookie($key,  $value = '', $expire = 0 , $path = '/', $domain  = '', $secure = false , $httponly = false)
    {
        if(defined('USE_SWOOLE_HTTP_SERVER') && USE_SWOOLE_HTTP_SERVER) {
            \HttpServer::$response->cookie($key,  $value, $expire, $path, $domain, $secure, $httponly);
        } else {
            \setcookie($key,  $value, $expire, $path, $domain, $secure, $httponly);
        }
    }

    public static function setrawcookie($key,  $value = '', $expire = 0 , $path = '/', $domain  = '', $secure = false , $httponly = false)
    {
        if(defined('USE_SWOOLE_HTTP_SERVER') && USE_SWOOLE_HTTP_SERVER) {
            \HttpServer::$response->rawcookie($key,  $value, $expire, $path, $domain, $secure, $httponly);
        } else {
            \setrawcookie($key,  $value, $expire, $path, $domain, $secure, $httponly);
        }
    }

}
