<?php

namespace ZPHP\Common;
use ZPHP\ZPHP,
    ZPHP\Core\Config,
    ZPHP\Common\Log;


class Debug
{
    private static $xhprof = false;
    private static $records;

    public static function getMicroTime()
    {
        list($usec, $sec) = \explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public static function start($key = 'ALL')
    {
        self::$xhprof = Config::getField('project', 'xhprof', 0) && \function_exists('xhprof_enable');
        if (self::$xhprof) {
            require(ZPHP::getZPath() . DS . 'lib' . DS . 'xhprof_lib' . DS . 'utils' . DS . 'xhprof_lib.php');
            require(ZPHP::getZPath() . DS . 'lib' . DS . 'xhprof_lib' . DS . 'utils' . DS . 'xhprof_runs.php');
            \xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
        }
        self::$records[$key]['start_time'] = self::getMicroTime();
        self::$records[$key]['memory_use'] = memory_get_usage();
    }

    public static function end($key = 'ALL', $logName = 'debug')
    {
        $endTime = self::getMicroTime();
        $run_id = 0;
        if (self::$xhprof) {
            $xhprof_data = \xhprof_disable();
            $xhprof_runs = new \XHProfRuns_Default();
            $run_id = $xhprof_runs->save_run($xhprof_data, 'random');
        }
        $times = $endTime - self::$records[$key]['start_time'];
        $mem_use = memory_get_usage() - self::$records[$key]['memory_use'];
        unset(self::$records[$key]);
        Log::info($logName, [$times, $mem_use, $run_id, $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'], \json_encode($_REQUEST)]);
    }

}

