<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol;

use ZPHP\Core\Config;
use ZPHP\View\Factory as ZView;

class Response
{
    private static $_response = null;

    private static $_headers = array();

    private static $_respone_time = null;

    const RESPONSE_TIME_KEY = 'X-Run-Time';

    public static function setResponse($response)
    {
        self::$_response = $response;
    }

    public static function getResponse()
    {
        return self::$_response;
    }

    public static function display($model)
    {
        if (null === $model || false === $model) {
            return $model;
        }
        if (is_array($model) && !empty($model['_view_mode'])) {
            $viewMode = $model['_view_mode'];
            unset($model['_view_mode']);
        } else {
            $viewMode = Request::getViewMode();
            if (empty($viewMode)) {
                $viewMode = Config::getField('project', 'view_mode', '');
                if (empty($viewMode)) {
                    if (Request::isAjax() || Request::isLongServer()) {
                        $viewMode = 'Json';
                    } else {
                        $viewMode = 'Php';
                    }
                }
            }
        }

        $view = ZView::getInstance($viewMode);
        if ('Php' === $viewMode) {
            $_tpl_file = Request::getTplFile();
            if (is_array($model) && !empty($model['_tpl_file'])) {
                $_tpl_file = $model['_tpl_file'];
                unset($model['_tpl_file']);
            }

            if (empty($_tpl_file)) {
                throw new \Exception("tpl file empty");
            }
            $view->setTpl($_tpl_file);
        }
        $view->setModel($model);
        self::$_respone_time = microtime(true);
        $key = Config::getField('project', 'response_time_key', self::RESPONSE_TIME_KEY);
        self::addHeader($key, self::$_respone_time - Request::getRequestTime());
        return $view->display();
    }

    public static function header($key, $val)
    {
        if (self::$_response) {
            self::$_response->header($key, $val);
            return;
        }

        \header("{$key}: {$val}");
    }

    public static function addHeader($key, $val)
    {
        self::$_headers[$key] = $val;
    }

    public static function getHeaders()
    {
        return self::$_headers;
    }

    public static function sendHttpHeader()
    {
        if (!empty(self::$_headers) && Request::isHttp()) {
            foreach (self::$_headers as $key => $val) {
                self::header($key, $val);
            }
        }
    }

    public static function status($code)
    {
        if (self::$_response) {
            self::$_response->status($code);
            return;
        }

        \http_response_code($code);

    }

    public static function setcookie($key, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        if (self::$_response) {
            self::$_response->cookie($key, $value, $expire, $path, $domain, $secure, $httponly);
            return;
        }
        \setcookie($key, $value, $expire, $path, $domain, $secure, $httponly);

    }

    public static function setrawcookie($key, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        if (self::$_response) {
            self::$_response->rawcookie($key, $value, $expire, $path, $domain, $secure, $httponly);
        }
        \setrawcookie($key, $value, $expire, $path, $domain, $secure, $httponly);
    }

    public static function getReponseTime()
    {
        return self::$_respone_time;
    }

}
