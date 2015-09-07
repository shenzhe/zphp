<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 15-9-1
 * Time: 下午5:49
 */

namespace ZPHP\Log;


class Level
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    public static $levels = array(
        self::EMERGENCY => 1,
        self::ALERT => 2,
        self::CRITICAL => 4,
        self::ERROR => 8,
        self::WARNING => 16,
        self::NOTICE => 32,
        self::INFO => 64,
        self::DEBUG => 128,
    );

    const ALL = 0xff;
}