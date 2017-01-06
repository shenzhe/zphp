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
    private static $_request_time = null;

    /**
     * @var IProtocol
     */
    private static $_server;

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
        if ($viewMode) {
            self::$_view_mode = $viewMode;
        }
        if (!is_string(self::$_ctrl) || !is_string(self::$_method)) {
            throw new \Exception('ctrl or method no string');
        }
        self::$_tpl_file = \str_replace('\\', DS, self::$_ctrl) . DS . self::$_method . '.php';
        self::setRequestId();
    }

    public static function setParams($params)
    {
        self::$_params = $params;
    }

    public static function addParams($key, $val, $set = true)
    {
        if ($set || !isset(self::$_params[$key])) {
            self::$_params[$key] = $val;
        }
    }

    public static function getParams()
    {
        return self::$_params;
    }

    public static function setCtrl($ctrlName)
    {
        self::$_ctrl = $ctrlName;
    }

    public static function getCtrl()
    {
        return self::$_ctrl;
    }

    public static function setMethod($methodName)
    {
        self::$_method = $methodName;
    }

    public static function getMethod()
    {
        return self::$_method;
    }

    public static function setTplFile($tplFile)
    {
        self::$_tpl_file = $tplFile;
    }

    public static function getTplFile()
    {
        return self::$_tpl_file;
    }

    public static function setViewMode($viewMode)
    {
        self::$_view_mode = $viewMode;
    }

    public static function getViewMode()
    {
        return self::$_view_mode;
    }

    public static function setFd($fd)
    {
        self::$_fd = $fd;
    }

    public static function getFd()
    {
        return self::$_fd;
    }

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

    public static function setServer($server)
    {
        self::$_server = $server;
    }

    public static function getServer()
    {
        return self::$_server;
    }

    public static function parse($data)
    {
        return self::$_server->parse($data);
    }

    public static function setLongServer($tag = 1)
    {
        self::$_long_server = $tag;
    }

    public static function isLongServer()
    {
        return self::$_long_server;
    }

    public static function setHttpServer($tag = 1)
    {
        self::$_is_http = $tag;
    }

    public static function isHttp()
    {
        return self::$_is_http;
    }

    public static function setRequest($request)
    {
        self::$_request = $request;
    }

    public static function getRequest()
    {
        return self::$_request;
    }

    public static function setSocket($socket)
    {
        self::$_socket = $socket;
    }

    public static function getSocket()
    {
        return self::$_socket;
    }

    public static function getRequestMethod()
    {
        if (self::isLongServer() && self::isHttp() && self::$_request) {
            return self::$_request->header['request_method'];
        }
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function getPathInfo()
    {
        if (self::isLongServer() && self::isHttp() && self::$_request) {
            return isset(self::$_request->header['path_info']) ? self::$_request->header['path_info'] : '';
        }
        return isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    }

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

    public static function addHeader($key, $val)
    {
        self::$_headers[$key] = $val;
    }

    public static function addHeaders(array $headers)
    {
        self::$_headers += $headers;
    }

    public static function getHeaders()
    {
        return self::$_headers;
    }

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

    public static function makeRequestId()
    {
        return sha1(uniqid('_' . mt_rand(1, 1000000), true));
    }

    public static function getRequestId($autoMake = false)
    {
        $requestId = self::getHeader(self::REQUEST_ID_KEY);
        if ($autoMake && empty($requestId)) {
            $requestId = self::makeRequestId();
        }
        return $requestId;
    }

    public static function setRequestId($reqeustId = null)
    {
        if (empty($requestId)) {
            $requestId = self::getRequestId(true);
        }
        $requestIdKey = ZConfig::getField('project', 'request_id_key', self::REQUEST_ID_KEY);
        self::addHeader($requestIdKey, $requestId);
        Response::addHeader($requestIdKey, $requestId);
    }

    public static function setRequestTime($time = null)
    {
        if (!empty(self::$_request_time)) {
            return;
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
    }

    public static function getRequestTime($clear = false)
    {
        $time = self::$_request_time;
        if ($clear) {
            self::$_request_time = null;
        }
        return $time;
    }
}