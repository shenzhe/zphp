<?php
/**
 * Created by PhpStorm.
 * User: 王晶
 * Date: 2015/12/30
 * Time: 11:11
 */

namespace ZPHP\Session;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Protocol\Request;
use ZPHP\Protocol\Response;


class Swoole
{

    private static $_sid = null;
    private static $_sessionType = null;
    private static $_config = null;

    public static function start($sessionType, $config)
    {
        if(null !== self::$_sid) {
            return;
        }
        //判断参数里是否有sessid
        if(empty($config)) {
            $config = ZConfig::get('session');
        }

        if(!empty($config['adapter'])) {
            $sessionType = $config['adapter'];
        }

        self::$_config = $config;
        self::$_sessionType = $sessionType;
        $request = Request::getRequest();

        $sessionName = empty($config['session_name']) ? 'ZPHPSESSID' : $config['session_name'];
        $sid = null;
        if(!empty($request->cookie[$sessionName])) {
            $sid = $request->cookie[$sessionName];
        }
        if(!$sid && !empty($request->get[$sessionName])) {
            $sid = $request->get[$sessionName];
        }
        if(!$sid && !empty($request->post[$sessionName])) {
            $sid = $request->post[$sessionName];
        }
        if($sid) {
            $handler = Factory::getInstance($sessionType, $config);
            $data = $handler->read($sid);
            if(!empty($data)) {
                $_SESSION = unserialize($data);
            } else {
                $_SESSION = array();
            }
        } else {
            $sid = sha1($request->header['user-agent'].$request->server['remote_addr'].uniqid(Request::getSocket()->worker_pid.'_', true));
            $path = empty($config['path']) ? '/' : $config['path'];
            $domain = empty($config['domain']) ? '' : $config['domain'];
            $secure = empty($config['secure']) ? false : $config['secure'];
            $httponly = !isset($config['httponly']) ? true : $config['httponly'];
            $lifetime = 0;
            if(!empty($config['cache_expire'])) {
                $lifetime = time() + $config['cache_expire'] * 60;
            }
            Response::getResponse()->cookie($sessionName, $sid, $lifetime, $path, $domain, $secure, $httponly);
            $_SESSION = array();
        }
        self::$_sid = $sid;
    }

    public static function save()
    {
        if(self::$_sid) {
            $handler = Factory::getInstance(self::$_sessionType, self::$_config);
            if (!isset($_SESSION) || empty($_SESSION)) {  //session清空
                $handler->destroy(self::$_sid);
            } else {
                $handler->write(self::$_sid, serialize($_SESSION));
                unset($_SESSION);
            }
            self::$_sid = null;
            self::$_config = null;
            self::$_sessionType = null;
        }
    }

    public static function getSid()
    {
        return self::$_sid;
    }
}