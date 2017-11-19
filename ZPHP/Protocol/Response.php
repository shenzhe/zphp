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
    /**
     * @var \swoole_http_response
     */
    private static $_response = null;

    private static $_headers = array();

    private static $_response_time = null;

    private static $_execute_time = 0;

    const RESPONSE_TIME_KEY = 'X-Run-Time';

    public static function setResponse($response)
    {
        self::$_response = $response;
        if ($response) {
            self::$_headers = array();
            self::$_response_time = 0;
            self::$_execute_time = 0;
        }
    }

    public static function getResponse()
    {
        return self::$_response;
    }


    /**
     * @param $model
     * @return mixed
     * @throws \Exception
     * @desc reponse数据格式输出
     */
    public static function display($model)
    {
        self::$_response_time = microtime(true);
        $key = Config::getField('project', 'response_time_key', self::RESPONSE_TIME_KEY);
        $startTime = Request::getRequestTime(true);
        self::$_execute_time = self::$_response_time - $startTime;
        self::addHeader($key . '-Start', $startTime);
        self::addHeader($key . '-End', self::$_response_time);
        self::addHeader($key, self::$_execute_time);
        return self::getContent($model);
    }

    /**
     * @param $model
     * @return mixed
     * @throws \Exception
     */
    public static function getContent($model)
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
        return $view->display();
    }

    /**
     * @param $key
     * @param $val
     * @desc 发送http头
     */
    public static function header($key, $val)
    {
        if (self::$_response) {
            self::$_response->header($key, $val);
            return;
        }

        \header("{$key}: {$val}");
    }

    /**
     * @param $key
     * @param $val
     * @desc 添加一个response头
     */
    public static function addHeader($key, $val)
    {
        self::$_headers[$key] = $val;
    }

    /**
     * @return array
     * @desc 获取所有response待发响应头
     */
    public static function getHeaders()
    {
        return self::$_headers;
    }

    /**
     * @desc 发送所有http response header头
     */
    public static function sendHttpHeader()
    {
        if (!empty(self::$_headers) && Request::isHttp()) {
            foreach (self::$_headers as $key => $val) {
                self::header($key, $val);
            }
        }
    }

    /**
     * @param $code
     * @desc 设置响应状态码
     */
    public static function status($code)
    {
        if (self::$_response) {
            self::$_response->status($code);
            return;
        }

        \http_response_code($code);

    }

    /**
     * @param $key
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @desc 设置cookie
     */
    public static function setcookie($key, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        if (self::$_response) {
            self::$_response->cookie($key, $value, $expire, $path, $domain, $secure, $httponly);
            return;
        }
        \setcookie($key, $value, $expire, $path, $domain, $secure, $httponly);

    }

    /**
     * @param $key
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @desc 设置原始cookie
     */
    public static function setrawcookie($key, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        if (self::$_response) {
            self::$_response->rawcookie($key, $value, $expire, $path, $domain, $secure, $httponly);
        }
        \setrawcookie($key, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * @return null
     * @desc 返回response响应的时间戳
     */
    public static function getResponseTime()
    {
        return self::$_response_time;
    }

    /**
     * @return int
     * @desc 返回一次请求的执行时间
     */
    public static function getExecuteTime()
    {
        return self::$_execute_time;
    }

}
