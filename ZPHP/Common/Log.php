<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 * 文件目录操作类
 */

namespace ZPHP\Common;
use ZPHP\ZPHP,
    ZPHP\Core\Config;

class Log
{
    const SEPARATOR = "\t";

    public static function info($type, $params = array())
    {
        $t = \date("Ymd");
        $logPath = Config::get('log_path', 'log');
        $dir = ZPHP::getRootPath() . DS . $logPath . DS . $t;
        $str = \date('Y-m-d H:i:s', Config::get('now_time'), time()) . self::SEPARATOR . \implode(self::SEPARATOR, $params);
        $logFile = $dir . \DS . $type . '.log';
        \error_log($str . "\n", 3, $logFile);
    }
}