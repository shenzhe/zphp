<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 * 初始化框架相关信息
 */

namespace ZPHP;

use ZPHP\Protocol\Request;
use ZPHP\Protocol\Response;
use ZPHP\Core\Config;
use ZPHP\Common\Debug;
use ZPHP\Common\Formater;
use ZPHP\Common\Log;

class ZPHP
{

    /**
     * ZPHP版本
     */
    const VERSION = '1.1';

    /**
     * 项目目录
     * @var string
     */
    private static $rootPath;
    /**
     * 配置目录
     * @var string
     */
    private static $configPath = 'default';
    private static $appPath = 'apps';
    private static $zPath;
    private static $libPath = [];
    private static $classPath = array();

    /**
     * @return string
     * @desc 获取root目录
     */
    public static function getRootPath()
    {
        return self::$rootPath;
    }

    /**
     * @param $rootPath
     * @desc 设置root目录
     */
    public static function setRootPath($rootPath)
    {
        self::$rootPath = $rootPath;
    }

    /**
     * @return string
     * @desc 获取配置路径
     */
    public static function getConfigPath()
    {
        $dir = self::getRootPath() . DS . 'config' . DS . self::$configPath;
        if (\is_dir($dir)) {
            return $dir;
        }
        return self::getRootPath() . DS . 'config' . DS . 'default';
    }

    /**
     * @param $path
     * @desc 设置配置路径
     */
    public static function setConfigPath($path)
    {
        self::$configPath = $path;
    }

    /**
     * @return string
     * @desc 获取app目录
     */
    public static function getAppPath()
    {
        return self::$appPath;
    }

    /**
     * @param $path
     * @desc 设置app目录
     */
    public static function setAppPath($path)
    {
        self::$appPath = $path;
    }

    /**
     * @return mixed
     * @desc 获取zphp框架目录
     */
    public static function getZPath()
    {
        return self::$zPath;
    }

    /**
     * @return string
     * @desc 获取第三方lib包目录
     */
    public static function getLibPath()
    {
        return self::$libPath;
    }

    /**
     * @param $class
     * @desc 自动加载类, ps当高并发情况下，is_file可能会导致cpu打满，可以取消掉is_file判断, swoole模式无此问题
     */
    final public static function autoLoader($class)
    {
        if (isset(self::$classPath[$class])) {
            return;
        }
        $baseClasspath = \str_replace('\\', DS, $class) . '.php';
        $libs = array(
            self::$rootPath . DS . self::$appPath,
            self::$zPath
        );
        if (is_array(self::$libPath)) {
            $libs = array_merge($libs, self::$libPath);
        } else {
            $libs[] = self::$libPath;
        }
        foreach ($libs as $lib) {
            $classpath = $lib . DS . $baseClasspath;
            if (\is_file($classpath)) {
                self::$classPath[$class] = $classpath;
                require "{$classpath}";
                return;
            }
        }
    }

    /**
     * @param $exception
     * @return mixed
     * @desc 默认的异常处理
     */
    final public static function exceptionHandler($exception)
    {
        $ret = Formater::exception($exception);
        Log::info('exception', $ret);
        return Response::display($ret);
    }

    /**
     * @desc 默认的fatal处理
     */
    final public static function fatalHandler()
    {
        $error = \error_get_last();
        if (empty($error)) {
            return "";
        }
        if (!in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            return "";
        }
        Response::status('200');
        $ret = Formater::fatal($error);
        Log::info('fatal', $ret);
        return Response::display($ret);
    }

    /**
     * @param $rootPath
     * @param bool $run
     * @param null $configPath
     * @return \ZPHP\Server\IServer
     * @throws \Exception
     * @desc 运行框架
     */
    public static function run($rootPath, $run = true, $configPath = null)
    {
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        self::$zPath = \dirname(__DIR__);
        self::setRootPath($rootPath);
        if (empty($configPath)) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                $configPath = \str_replace(':', '_', $_SERVER['HTTP_HOST']);
            } elseif (!empty($_SERVER['argv'][1])) {
                $configPath = $_SERVER['argv'][1];
            }
        }
        if (!empty($configPath)) {
            self::setConfigPath($configPath);
        }
        \spl_autoload_register(__CLASS__ . '::autoLoader');
        Request::setRequestTime();
        Config::load(self::getConfigPath());
        $serverMode = Config::get('server_mode', 'Http');
        if ('Ant' == $serverMode) { //ant模式的约定
            self::$libPath = Config::get('lib_path', []);
            $antLibPath = ZPHP::getRootPath() . DS . '..' . DS . 'ant-lib';
            if (is_dir($antLibPath)) {   //compsoer方式，此目录并不存在，不需要加到libpath
                if (is_array(self::$libPath)) {
                    self::$libPath += [
                        'ant-lib' => $antLibPath,
                        'ant-rpc' => ZPHP::getRootPath() . DS . '..' . DS . 'ant-rpc',
                    ];
                } else {
                    self::$libPath = [self::$libPath];
                    self::$libPath += [
                        'ant-lib' => ZPHP::getRootPath() . DS . '..' . DS . 'ant-lib',
                        'ant-rpc' => ZPHP::getRootPath() . DS . '..' . DS . 'ant-rpc',
                    ];
                }
            }
        } else {
            self::$libPath = Config::get('lib_path', self::$zPath . DS . 'lib');
        }
        if ($run && Config::getField('project', 'debug_mode', 0)) {
            Debug::start();
        }
        $loadendHooK = Config::get('loadend_hook');
        if ($loadendHooK && is_callable($loadendHooK)) {
            call_user_func($loadendHooK);
        }
        $appPath = Config::get('app_path', self::$appPath);
        self::setAppPath($appPath);
        \set_exception_handler(Config::getField('project', 'exception_handler', __CLASS__ . '::exceptionHandler'));
        \register_shutdown_function(Config::getField('project', 'fatal_handler', __CLASS__ . '::fatalHandler'));
        if (Config::getField('project', 'error_handler')) {
            \set_error_handler(Config::getField('project', 'error_handler'));
        }
        $timeZone = Config::get('time_zone', 'Asia/Shanghai');
        \date_default_timezone_set($timeZone);

        $service = Server\Factory::getInstance($serverMode);
        if ($run) {
            $service->run();
        } else {
            return $service;
        }
        if ($run && Config::getField('project', 'debug_mode', 0)) {
            Debug::end();
        }
    }
}
