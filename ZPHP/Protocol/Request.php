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

    public static function init($ctrl, $method, $params, $viewMode)
    {
        self::$_ctrl = $ctrl;
        self::$_method = $method;
        self::$_params = $params;
        self::$_view_mode = $viewMode;
        self::$_tpl_file = \str_replace('\\', DS, $ctrl) . DS . $method . '.php';
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

}
