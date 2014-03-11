<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 * 初始化框架相关信息
 */
namespace ZPHP;
use ZPHP\View,
    ZPHP\Core\Config,
    ZPHP\Common\Debug,
    ZPHP\Common\Formater;

class ZPHP
{
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
    private static $libPath='lib';
    private static $classPath = array();

    public static function getRootPath()
    {
        return self::$rootPath;
    }

    public static function setRootPath($rootPath)
    {
        self::$rootPath = $rootPath;
    }

    public static function getConfigPath()
    {
        $dir = self::getRootPath() . DS . 'config' . DS . self::$configPath;
        if (\is_dir($dir)) {
            return $dir;
        }
        return self::getRootPath() . DS . 'config' . DS . 'default';
    }

    public static function setConfigPath($path)
    {
        self::$configPath = $path;
    }

    public static function getAppPath()
    {
        return self::$appPath;
    }

    public static function setAppPath($path)
    {
        self::$appPath = $path;
    }

    public static function getZPath()
    {
        return self::$zPath;
    }

    public static function getLibPath()
    {
        return self::$libPath;
    }

    final public static function autoLoader($class)
    {
        if(isset(self::$classPath[$class])) {
            require self::$classPath[$class];
            return;
        }
        $baseClasspath = \str_replace('\\', DS, $class) . '.php';
        $libs = array(
            self::$rootPath . DS . self::$appPath,
            self::$zPath,
            self::$libPath
        );
        foreach ($libs as $lib) {
            $classpath = $lib . DS . $baseClasspath;
            if (\is_file($classpath)) {
                self::$classPath[$class] = $classpath;
                require "{$classpath}";
                return;
            }
        }
    }

    final public static function exceptionHandler($exception)
    {
        $exceptionView = View\Factory::getInstance();
        $exceptionView->setModel(Formater::exception($exception));
        $exceptionView->display();
    }

    public static function run($rootPath)
    {
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        self::$zPath = \dirname(__DIR__);
        self::setRootPath($rootPath);
        if (!empty($_SERVER['HTTP_HOST'])) {
            $configPath = $_SERVER['HTTP_HOST'];
        } elseif (!empty($_SERVER['argv'][1])) {
            $configPath = $_SERVER['argv'][1];
        }
        if (!empty($configPath)) {
            self::setConfigPath($configPath);
        }
        \spl_autoload_register(__CLASS__ . '::autoLoader');
        Config::load(self::getConfigPath());
        if (Config::getField('project', 'debug_mode', 0)) {
            Debug::start();
        }
        self::$libPath = Config::get('lib_path', self::$zPath . DS .'lib');
        $appPath = Config::get('app_path', self::$appPath);
        self::setAppPath($appPath);
        $eh = Config::getField('project', 'exception_handler', __CLASS__ . '::exceptionHandler');
        \set_exception_handler($eh);
        $timeZone = Config::get('time_zone', 'Asia/Shanghai');
        \date_default_timezone_set($timeZone);
        $serverMode = Config::get('server_mode', 'Http');
        $service = Server\Factory::getInstance($serverMode);
        $service->run();
        if (Config::getField('project', 'debug_mode', 0)) {
            Debug::end();
        }
    }
}
