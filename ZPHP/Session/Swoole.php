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
        if(!empty($request->get[$sessionName])) {
            $sid = $request->get[$sessionName];
        }
        if(!empty($request->post[$sessionName])) {
            $sid = $request->post[$sessionName];
        }
        if($sid) {
            $handler = Factory::getInstance($sessionType, $config);
            $_SESSION = unserialize($handler->open($sid));
        } else {
            //种cookie
            $sid = sha1(microtime().$request->header['User-Agent'].$request->server['remote_addr'].rand(100000, 999999));
            //string $key, string $value = '', int $expire = 0 , string $path = '/', string $domain  = '', bool $secure = false , bool $httponly = false
            $path = empty($config['path']) ? '/' : $config['path'];
            $domain = empty($config['domain']) ? '' : $config['domain'];
            $secure = empty($config['secure']) ? false : $config['secure'];
            $httponly = !isset($config['httponly']) ? true : $config['httponly'];
            $lifetime = 0;
            if(!empty($config['cache_expire'])) {
                $lifetime = time() + $config['cache_expire'] * 60;
            }
            Response::getResponse()->cookie($sessionName, $sid, $lifetime, $path, $domain, $secure, $httponly);
            $_SESSION = [];
        }
        self::$_sid = $sid;
    }

    public static function save()
    {
        if(self::$_sid) {
            $handler = Factory::getInstance(self::$_sessionType, self::$_config);
            if(!isset($_SESSION)) {  //session清空
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
}