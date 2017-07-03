<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol;

use ZPHP\Core\Config as ZConfig;

class Request
{

    const REQUEST_ID_KEY = 'X-Request-Id';
    const REQUEST_TIME_KEY = 'X-Request-Time';

    private static $_params;
    private static $_ctrl = 'main';
    private static $_method = 'main';
    private static $_view_mode = null;
    private static $_tpl_file = '';
    private static $_fd = null;
    private static $_long_server = 0;
    private static $_is_http = 1;
    private static $_request = null;
    private static $_socket = null;
    private static $_headers = array();
    private static $_request_time = 0;

    /**
     * @var IProtocol
     */
    private static $_server;

    /**
     * @param $ctrl
     * @param $method
     * @param array $params
     * @param null $viewMode
     * @throws \Exception
     * @desc 请求初始化
     */
    public static function init($ctrl, $method, array $params, $viewMode = null)
    {
        if ($ctrl) {
            self::$_ctrl = $ctrl;
        } else {
            self::$_ctrl = ZConfig::getField('project', 'default_ctrl_name', self::$_ctrl);
        }
        if ($method) {
            self::$_method = $method;
        } else {
            self::$_method = ZConfig::getField('project', 'default_method_name', self::$_method);
        }
        self::$_params = $params;
        self::$_view_mode = $viewMode;
        if (!is_string(self::$_ctrl) || !is_string(self::$_method)) {
            throw new \Exception('ctrl or method no string');
        }
        self::$_tpl_file = \str_replace('\\', DS, self::$_ctrl) . DS . self::$_method . '.php';
        self::setRequestId();
    }

    /**
     * @param $params
     * @desc 设置请求参数数组
     */
    public static function setParams($params)
    {
        self::$_params = $params;
    }

    /**
     * @param $key
     * @param $val
     * @param bool $set
     * @desc 批量添加请求参数
     */
    public static function addParams($key, $val, $set = true)
    {
        if ($set || !isset(self::$_params[$key])) {
            self::$_params[$key] = $val;
        }
    }

    /**
     * @return mixed
     * @desc 获取请求参数数组
     */
    public static function getParams()
    {
        return self::$_params;
    }

    /**
     * @param $ctrlName
     * @desc 设置控制器类
     */
    public static function setCtrl($ctrlName)
    {
        self::$_ctrl = $ctrlName;
    }

    /**
     * @return string
     * @desc 获取控制器类
     */
    public static function getCtrl()
    {
        return self::$_ctrl;
    }

    /**
     * @param $methodName
     * @desc 设置执行的方法 eg:$ctrl->$method();
     */
    public static function setMethod($methodName)
    {
        self::$_method = $methodName;
    }

    /**
     * @return string
     * @desc 获取执行的方法
     */
    public static function getMethod()
    {
        return self::$_method;
    }

    /**
     * @param $tplFile
     * @desc 设置模板文件
     */
    public static function setTplFile($tplFile)
    {
        self::$_tpl_file = $tplFile;
    }

    /**
     * @return string
     * @desc 获取模板文件
     */
    public static function getTplFile()
    {
        return self::$_tpl_file;
    }

    /**
     * @param $viewMode
     * @desc 设置view模式
     */
    public static function setViewMode($viewMode)
    {
        self::$_view_mode = $viewMode;
    }

    /**
     * @return null
     * @desc 获取view模式
     */
    public static function getViewMode()
    {
        return self::$_view_mode;
    }

    /**
     * @param $fd
     * @desc 设置fd (swoole模式)
     */
    public static function setFd($fd)
    {
        self::$_fd = $fd;
    }

    /**
     * @return null
     * @desc 获取fd (swoole模式)
     */
    public static function getFd()
    {
        return self::$_fd;
    }

    /**
     * @return bool
     * @desc 是否ajax请求
     */
    public static function isAjax()
    {

        if (!empty(self::$_params['ajax'])) {
            return true;
        }
        if (self::isLongServer() && self::isHttp() && self::$_request
            && isset(self::$_request->header['X-Requested-With'])
            && 'xmlhttprequest' == strtolower(self::$_request->header['X-Requested-With']
            )
        ) {
            return true;
        }
        if ((isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
        ) {
            return true;
        }
        $field = ZConfig::getField('project', 'jsonp', 'jsoncallback');
        if (self::isLongServer() && self::isHttp() && isset(self::$_request->header[$field])) {
            return true;
        }

        if (!empty($_REQUEST[$field])) {
            return true;
        }
        return false;
    }

    /**
     * @param $server
     * @desc 设置protocal对像
     */
    public static function setServer($server)
    {
        self::$_server = $server;
    }

    /**
     * @return IProtocol
     * @desc 获取protocal对像
     */
    public static function getServer()
    {
        return self::$_server;
    }

    /**
     * @param $data
     * @return mixed
     * @desc 解析请求体
     */
    public static function parse($data)
    {
        return self::$_server->parse($data);
    }

    /**
     * @param int $tag
     * @desc 设置为长驻服务(swoole模式)
     */
    public static function setLongServer($tag = 1)
    {
        self::$_long_server = $tag;
    }

    /**
     * @return int
     * @desc 是否长驻服务 (swoole模式)
     */
    public static function isLongServer()
    {
        return self::$_long_server;
    }

    /**
     * @param int $tag
     * @desc 是否swoole_http运行 (swoole模式)
     */
    public static function setHttpServer($tag = 1)
    {
        self::$_is_http = $tag;
    }

    /**
     * @return int
     * @desc 是否http请求
     */
    public static function isHttp()
    {
        return self::$_is_http;
    }

    /**
     * @param $request
     * @desc 设置http_request对像(swoole模式)
     */
    public static function setRequest($request)
    {
        self::$_request = $request;
        if ($request) {
            self::$_request_time = 0;
        }
    }

    /**
     * @return null
     * @desc 获取http_request对像(swoole模式)
     */
    public static function getRequest()
    {
        return self::$_request;
    }

    /**
     * @param $socket
     * @desc 设置swoole_server对像(swoole模式)
     */
    public static function setSocket($socket)
    {
        self::$_socket = $socket;
    }

    /**
     * @return \swoole_server
     * @desc 获取swoole_server对像(swoole模式)
     */
    public static function getSocket()
    {
        return self::$_socket;
    }

    /**
     * @return mixed
     * @desc 获取请求方法名
     */
    public static function getRequestMethod()
    {
        if (self::isLongServer() && self::isHttp() && self::$_request) {
            return self::$_request->header['request_method'];
        }
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return string
     * @desc 获取pathinfo
     */
    public static function getPathInfo()
    {
        if (self::isLongServer() && self::isHttp() && self::$_request) {
            return isset(self::$_request->server['path_info']) ? self::$_request->server['path_info'] : '';
        }
        return isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    }

    /**
     * @return string
     * @desc 获取客户端ip
     */
    public static function getClientIp()
    {
        $realip = '';
        if (self::isLongServer()) {
            if (self::isHttp() && self::$_request) {
                $key = ZConfig::getField('project', 'clientIpKey', 'X-Forwarded-For');
                if (isset(self::$_request->header[$key])) {
                    $realip = self::$_request->header[$key];
                } else if (isset(self::$_request->header["remote_addr"])) {
                    $realip = self::$_request->header["remote_addr"];
                }
            } else {
                if (self::$_fd) {
                    $connInfo = self::getSocket()->connection_info(self::$_fd);
                    return $connInfo['remote_ip'];
                }
            }
        } else {
            $key = ZConfig::getField('project', 'clientIpKey', 'HTTP_X_FORWARDED_FOR');
            if (isset($_SERVER[$key])) {
                $realip = $_SERVER[$key];
            } else if (isset($_SERVER["REMOTE_ADDR"])) {
                $realip = $_SERVER["REMOTE_ADDR"];
            }
        }

        return $realip;

    }

    /**
     * @param $key
     * @param $val
     * @desc 添加一个请求头
     */
    public static function addHeader($key, $val)
    {
        self::$_headers[$key] = $val;
    }

    /**
     * @param array $headers
     * @param bool $init //是否初始化
     * @param bool $set //是否覆盖
     * @return array
     * @desc 添加一批请求头
     */
    public static function addHeaders(array $headers, $init = false, $set = false)
    {
        if ($init) {
            self::$_headers = $headers;
        } else {
            if ($set) {
                self::$_headers = $headers + self::$_headers;
            } else {
                self::$_headers += $headers;
            }
        }
        return self::$_headers;
    }

    /**
     * @return array
     * @desc 获取所有待发的请求头
     */
    public static function getHeaders()
    {
        return self::$_headers;
    }

    /**
     * @param $key
     * @return mixed|null
     * @desc 跟据key获取请求头信息
     */
    public static function getHeader($key)
    {
        if (!empty(self::$_headers[$key])) {
            return self::$_headers[$key];
        }

        if (self::isLongServer()) {
            if (self::isHttp() && self::$_request) {
                if (!empty(self::$_request->header[$key])) {
                    return self::$_request->header[$key];
                }
            }
        } else {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }
        return null;
    }

    /**
     * @return string
     * @desc 生成请求id
     */
    public static function makeRequestId()
    {
        return sha1(uniqid('_' . mt_rand(1, 1000000), true));
    }

    /**
     * @param bool $autoMake
     * @return mixed|null|string
     * @desc 获取请求id
     */
    public static function getRequestId($autoMake = false)
    {
        $requestId = self::getHeader(self::REQUEST_ID_KEY);
        if ($autoMake && empty($requestId)) {
            $requestId = self::makeRequestId();
        }
        return $requestId;
    }

    /**
     * @param null $requestId
     * @return mixed|null|string
     * @desc 设置请求唯一id
     */
    public static function setRequestId($requestId = null)
    {
        if (empty($requestId)) {
            $requestId = self::getRequestId(true);
        }
        $requestIdKey = ZConfig::getField('project', 'request_id_key', self::REQUEST_ID_KEY);
        self::addHeader($requestIdKey, $requestId);
        Response::addHeader($requestIdKey, $requestId);
        self::setRequestTime();
        return $requestId;
    }

    /**
     * @param int $timeOut
     * @return bool
     * @desc 检测请求是否已超时
     */
    public static function checkRequestTimeOut($timeOut = 0)
    {
        $key = ZConfig::getField('project', 'request_time_key', self::REQUEST_TIME_KEY);
        if (!isset(self::$_headers[$key])) {
            return false;
        }

        if (!$timeOut) {
            if (!empty(self::$_headers['X-Request-Timeout'])) {
                $timeOut = self::$_headers['X-Request-Timeout'];
            }
        }

        if ($timeOut <= 0) {
            return false;
        }

        $startTime = self::$_headers[$key];
        $nowTime = microtime(true);
        return $nowTime - $startTime > $timeOut;
    }

    /**
     * @param null $time
     * @return bool
     */
    public static function setRequestTime($time = null)
    {
        if (!empty(self::$_request_time)) {
            return false;
        }
        if (empty($time)) {
            if (!empty($_REQUEST['REQUEST_TIME_FLOAT'])) {
                $time = $_REQUEST['REQUEST_TIME_FLOAT'];
            } else {
                $time = microtime(true);
            }
        }
        self::$_request_time = $time;
        $key = ZConfig::getField('project', 'request_time_key', self::REQUEST_TIME_KEY);
        self::addHeader($key, $time);
        return true;
    }

    /**
     * @param bool $clear
     * @return null
     * @desc 获取请求开始时间
     */
    public static function getRequestTime($clear = false)
    {
        $time = self::$_request_time;
        if ($clear) {
            self::$_request_time = 0;
        }
        return $time;
    }

    public static function getHttpMethod()
    {
        if (self::isLongServer() && self::isHttp() && self::$_request) {
            return isset(self::$_request->server['request_method']) ? self::$_request->server['request_method'] : '';
        }
        return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }
}