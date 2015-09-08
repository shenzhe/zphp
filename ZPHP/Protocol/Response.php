<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol;
use ZPHP\View\Factory as ZView;

class Response
{
    private static $_server;
    private static $_response = null;

    public static function init($server)
    {
        self::$_server = $server;
    }

    public static function getServer()
    {
        return self::$_server;
    }

    public static function display($model)
    {
        if(is_array($model) && !empty($model['_view_mode'])) {
            $viewMode = $model['_view_mode'];
            unset($model['_view_mode']);
        } else {
            $viewMode = Request::getViewMode();
            if(empty($viewMode)) {
                if (Request::isAjax()) {
                    $viewMode = 'Json';
                } else {
                    $viewMode = 'Php';
                }
            }
        }

        $view = ZView::getInstance($viewMode);
        if ('Php' === $viewMode) {
            $_tpl_file = Request::getTplFile();
            if(is_array($model) && !empty($model['_tpl_file'])) {
                $_tpl_file = $model['_tpl_file'];
                unset($model['_tpl_file']);
            }

            if(empty($_tpl_file)) {
                throw new \Exception("tpl file empty");
            }
            $view->setTpl($_tpl_file);
        }
        $view->setModel($model);
        return $view->display();
    }

    public static function header($key, $val)
    {
        if(self::$_response) {
            self::$response->header($key, $val);
            return;
        }

        \header("{$key}: {$val}");
    }

    public static function status($code)
    {
        if(self::$_response) {
            self::$response->status($code);
            return;
        }

        \http_response_code($code);

    }

    public static function setcookie($key,  $value = '', $expire = 0 , $path = '/', $domain  = '', $secure = false , $httponly = false)
    {
        if(self::$response) {
            self::$response->cookie($key,  $value, $expire, $path, $domain, $secure, $httponly);
            return;
        }
        \setcookie($key,  $value, $expire, $path, $domain, $secure, $httponly);

    }

    public static function setrawcookie($key,  $value = '', $expire = 0 , $path = '/', $domain  = '', $secure = false , $httponly = false)
    {
        if(self::$response) {
            self::$response->rawcookie($key,  $value, $expire, $path, $domain, $secure, $httponly);
        }
        \setrawcookie($key,  $value, $expire, $path, $domain, $secure, $httponly);
    }

}
