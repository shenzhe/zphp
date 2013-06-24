<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 * 初始化框架相关信息
 */
namespace ZPHP;
use ZPHP\View,
    ZPHP\Core\Config,
    ZPHP\Common\Daemon,
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

    private static $timeZone = 'Asia/Shanghai';
    private static $zPath;

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

    final public static function autoLoader($class)
    {
        $baseClasspath = \str_replace('\\', DS, $class) . '.php';
        $libs = array(
            self::$rootPath . DS . self::$appPath,
            self::$zPath,
            self::$zPath . DS . 'lib'
        );
        foreach ($libs as $lib) {
            $classpath =  $lib . DS . $baseClasspath;
            if (\is_file($classpath)) {
                require "{$classpath}";
                return;
            }
        }
    }

    final public static function exceptionHandler($exception)
    {
        $exceptionView = View\Factory::getInstance();
        $exceptionView->setModel(Formater::exception($exception));
        echo $exceptionView->output();
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
        $appPath = Config::get('app_path', self::$appPath);
        self::setAppPath($appPath);
        \set_exception_handler(__CLASS__ . '::exceptionHandler');
        $timeZone = Config::get('time_zone', self::$timeZone);
        \date_default_timezone_set($timeZone);
        $serverMode = Config::get('server_mode', 'Http');
        $service = Server\Factory::getInstance($serverMode);
        $service->run();
    }
}
