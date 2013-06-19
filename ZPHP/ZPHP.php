<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 * 初始化框架相关信息
 */
namespace ZPHP;
use ZPHP\View,
    ZPHP\Core\Config,
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
    private static $configPath='default';

    private static $appPath = 'apps';

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
        return self::getRootPath(). DS. 'config'. DS. self::$configPath;
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
        $baseClasspath = str_replace('\\', DS, $class) . '.php';
        if(is_file(self::$rootPath. DS. $baseClasspath)) {  //框架文件
            $classpath = self::$rootPath. DS. $baseClasspath;
        }elseif(is_file(self::$rootPath. DS. self::$appPath. DS . $baseClasspath)){  //classes文件
            $classpath = self::$rootPath . DS . self::$appPath. DS . $baseClasspath;
        } else {    //第三方库文件
            $classpath = self::$rootPath. DS. 'lib' . DS. $baseClasspath;
        }
        if(is_file($classpath)) {
            require "{$classpath}";
        }
    }

    final public static function exceptionHandler($exception) {
        $exceptionView = View\Factory::getInstance();
        $exceptionView->setModel(Formater::exception($exception));
        $exceptionView->display();
    }

    public static function run($rootPath, $configPath='default', $appPath='apps')
    {
        if(!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        self::setRootPath($rootPath);
        self::setConfigPath($configPath);
        self::setAppPath($appPath);
        \spl_autoload_register(__CLASS__.'::autoLoader');
        \set_exception_handler(__CLASS__.'::exceptionHandler');
        $config = Config::load(self::getConfigPath());
        $timeZone = empty($config['time_zone']) ? 'Asia/Shanghai' : $config['time_zone'];
        \date_default_timezone_set($timeZone);
        $serverMode = empty($config['server_mode']) ? 'Http' : $config['server_mode'];
        Server\Factory::getInstance($serverMode);
    }
}
