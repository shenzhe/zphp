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

}
