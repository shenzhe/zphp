<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */
namespace ZPHP\Session;
use ZPHP\Core\Factory as CFactory,
    ZPHP\Core\Config as ZConfig;
use ZPHP\Protocol\Request;

class Factory
{
    protected static $isStart=false;
    public static function getInstance($adapter = 'Redis', $config=null)
    {
        if(empty($config)) {
            $config = ZConfig::get('session');
            if(!empty($config['adapter'])) {
                $adapter = $config['adapter'];
            }
        }
        $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return CFactory::getInstance($className, $config);
    }

    public static function start($sessionType = '', $config = '')
    {
        if(Request::isLongServer()) {
            return Swoole::start($sessionType, $config);
        }

        if(false === self::$isStart) {
            if(empty($config)) {
                $config = ZConfig::get('session');
            }

            if(!empty($config['adapter'])) {
                $sessionType = $config['adapter'];
            }

            $lifetime = 0;
            if(!empty($config['cache_expire'])) {
                \session_cache_expire($config['cache_expire']);
                $lifetime = $config['cache_expire'] * 60;
            }
            $path = empty($config['path']) ? '/' : $config['path'];
            $domain = empty($config['domain']) ? '' : $config['domain'];
            $secure = empty($config['secure']) ? false : $config['secure'];
            $httponly = !isset($config['httponly']) ? true : $config['httponly'];
            \session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);

            $sessionName = empty($config['session_name']) ? 'ZPHPSESSID' : $config['session_name'];
            \session_name($sessionName);

            if(!empty($_GET[$sessionName])) {
                \session_id($_GET[$sessionName]);
            }elseif(!empty($_SERVER[$sessionName])) {
                \session_id($_SERVER[$sessionName]);
            }

            if (!empty($sessionType)) {
                $handler = self::getInstance($sessionType, $config);
                \session_set_save_handler(
                    array($handler, 'open'),
                    array($handler, 'close'),
                    array($handler, 'read'),
                    array($handler, 'write'),
                    array($handler, 'destroy'),
                    array($handler, 'gc')
                );
            }
            
            
            \session_start();
            self::$isStart = true;
        }
    }
}