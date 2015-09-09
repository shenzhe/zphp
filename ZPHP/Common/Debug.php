<?php

namespace ZPHP\Common;
use ZPHP\Protocol\Request;
use ZPHP\ZPHP,
    ZPHP\Core\Config,
    ZPHP\Common\Log,
    ZPHP\Common\Terminal;


class Debug
{
    private static $xhprof = false;
    private static $records;

    private static $DEBUG_TRACE = false;

    public static function getMicroTime()
    {
        list($usec, $sec) = \explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public static function start($key = 'ALL')
    {
        if (!self::$xhprof && Config::getField('project', 'xhprof', 0) && \function_exists('xhprof_enable')) {
            require(ZPHP::getLibPath() . DS . 'xhprof_lib' . DS . 'utils' . DS . 'xhprof_lib.php');
            require(ZPHP::getLibPath() . DS . 'xhprof_lib' . DS . 'utils' . DS . 'xhprof_runs.php');
            \xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
            self::$xhprof = true;
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
        if(empty($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = '';
        }
        Log::info($logName, array($times, self::convert($mem_use), $run_id, $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'], Request::getParams()));
    }


    private static function convert($size)
    {
        $unit = array('B', 'K', 'M', 'G', 'T', 'P');
        return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }


    /**
     * Send print to terminal.
     */
    private static function _log($msgType, $args)
    {
        if (!Config::getField('project', 'debug_mode', 0)) {
            return;
        }

        if (count($args) == 1) {
            $msg = is_scalar($args[0]) ? $args[0] : self::dump($args[0]);
        } else {
            $msg = self::dump($args);
        }

        if (self::$DEBUG_TRACE) {
            $trace = self::getTrace();
        } else {
            $trace = array();
        }
        if ($msgType == 'debug') {
            Terminal::drawStr($msg, 'magenta');
        } else if ($msgType == 'error') {
            Terminal::drawStr($msg, 'red');
        } else if ($msgType == 'info') {
            Terminal::drawStr($msg, 'brown');
        } else {
            Terminal::drawStr($msg, 'default');
        }
        //echo "\n";
        !empty($trace) && Terminal::drawStr("\t" . implode(" <-- ", $trace) . "\n");
    }

    private static function getTrace()
    {
        $traces = debug_backtrace();
        // only display 2 to 6 backtrace
        for ($i = 2, $n = count($traces); $i < $n && $i < 7; $i++) {
            //for ($i = 3, $n = count($traces); $i < $n; $i++){
            $trace = $traces[$i];
            if (isset($trace['type'])) {
                $callInfo = $trace['class'] . $trace['type'] . $trace['function'] . '()';
            } else {
                $callInfo = 'internal:' . $trace['function'] . '()';
            }
            if (isset($trace['file'])) {
                $fileInfo = str_replace(ZPHP::getRootPath() . '/', '', $trace['file']) . ':' . $trace['line'];
            } else {
                $fileInfo = '';
            }
            //$traces_data[] = $fileInfo . " " . $callInfo;
            $traces_data[] = $callInfo . " " . $fileInfo;
        }
        return $traces_data;
    }

    private static function dump()
    {
        ob_start();

        foreach (func_get_args() as $v) {
            var_dump($v);
        }

        $dump = ob_get_contents();
        ob_end_clean();

        return $dump;
    }

    public static function log()
    {
        self::_log('log', func_get_args());
    }

    public static function info()
    {
        self::_log('info', func_get_args());
    }

    public static function debug($a)
    {
        self::_log('debug', func_get_args());
    }

    public static function error()
    {
        self::_log('error', func_get_args());
    }

}

