<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol;
use ZPHP\Core\Config as ZConfig;

class Request
{
    private static $_params;
    private static $_ctrl = 'main\\main';

    private static $_method = 'main';

    private static $_view_mode  = null;

    private static $_tpl_file = '';

    private static $_fd = null;

    private static $_long_server = 0;
    private static $_is_http = 1;
    private static $_request = 1;
    private static $_socket = null;

    /**
     * @var IProtocol
     */
    private static $_server;

    public static function init($ctrl, $method, $params, $viewMode=null)
    {
        if($ctrl) {
            self::$_ctrl = $ctrl;
        } else {
            self::$_ctrl = ZConfig::getField('project', 'default_ctrl_name', self::$_ctrl);
        }
        if($method) {
            self::$_method = $method;
        }else {
            self::$_method = ZConfig::getField('project', 'default_method_name', self::$_method);
        }
        self::$_params = $params;
        if($viewMode) {
            self::$_view_mode = $viewMode;
        }
        if(!is_string(self::$_ctrl) || !is_string(self::$_method)) {
            throw new \Exception('ctrl or method no string');
        }
        self::$_tpl_file = \str_replace('\\', DS, self::$_ctrl) . DS . self::$_method . '.php';
    }

	public static function setParams($params)
    {
        self::$_params = $params;
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

        if (!empty(self::$_params['ajax'])
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
        ) {
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

    public static function setLongServer($tag=1)
    {
        self::$_long_server = $tag;
    }

    public static function isLongServer()
    {
        return self::$_long_server;
    }

    public static function setHttpServer($tag=1)
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

}
