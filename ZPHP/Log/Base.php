<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 15-9-1
 * Time: 下午4:53
 */

namespace ZPHP\Log;


abstract class Base
{

    /**
     * @param $message
     * @param array $context
     * @return bool
     * @throws \Exception
     */
    public function emergency($message, array $context = array())
    {
        return $this->log(Level::EMERGENCY, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return bool
     * @throws \Exception
     */
    public function alert($message, array $context = array())
    {
        return $this->log(Level::ALERT, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return bool
     * @throws \Exception
     */
    public function critical($message, array $context = array())
    {
        return $this->log(Level::CRITICAL, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return bool
     * @throws \Exception
     */
    public function error($message, array $context = array())
    {
        return $this->log(Level::ERROR, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return bool
     * @throws \Exception
     */
    public function warning($message, array $context = array())
    {
        return $this->log(Level::WARNING, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return bool
     * @throws \Exception
     */
    public function notice($message, array $context = array())
    {
        return $this->log(Level::NOTICE, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return bool
     * @throws \Exception
     */
    public function info($message, array $context = array())
    {
        return $this->log(Level::INFO, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return bool
     * @throws \Exception
     */
    public function debug($message, array $context = array())
    {
        return $this->log(Level::DEBUG, $message, $context);
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