<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 * 日志输出类
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
        $logPath = Config::getField('project', 'log_path', '');
        if(empty($logPath)) {
            $dir = ZPHP::getRootPath() . DS . 'log' . DS . $t;
        } else {
            $dir = $logPath . DS . $t;
        }
        Dir::make($dir);
        $str = \date('Y-m-d H:i:s', Config::get('now_time', time())) . self::SEPARATOR . \implode(self::SEPARATOR, array_map('ZPHP\Common\Log::myJson', $params));
        $logFile = $dir . \DS . $type . '.log';
        \file_put_contents($logFile, $str . "\n", FILE_APPEND|LOCK_EX);
    }
    
    public static function myJson($data)
    {
		return json_encode($data,  JSON_UNESCAPED_UNICODE);
	}
}
