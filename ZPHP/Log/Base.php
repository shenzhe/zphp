<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 15-9-1
 * Time: 下午4:53
 */

namespace ZPHP\Log;

use ZPHP\Log\Level;
use ZPHP\Core\Config as ZConfig;


abstract class Base
{

    public function emergency($message, array $context = array())
    {
        $this->log(Level::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->log(Level::ALERT, $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->log(Level::CRITICAL, $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->log(Level::ERROR, $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->log(Level::WARNING, $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->log(Level::NOTICE, $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log(Level::INFO, $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->log(Level::DEBUG, $message, $context);
    }

    /**
     * @param $level
     * @param $message
     * @param array $context
     * @return bool
     * @throws \Exception
     * @desc {type} | {timeStamp} |{dateTime} | {$message}
     */
    abstract public function log($level, $message, array $context = array());

}