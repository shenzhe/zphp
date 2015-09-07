<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 15-9-1
 * Time: 下午4:53
 */

namespace ZPHP\Log\Adapter;

use ZPHP\Log\Level;
use ZPHP\Core\Config as ZConfig;


class File
{

    private $_config;

    const SEPARATOR = ' | ';

    public function __construct($config)
    {
        if (!empty($config)) {
            $this->_config = $config;
        }
    }

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
    public function log($level, $message, array $context = array())
    {
        $logLevel = ZConfig::getField('project', 'log_level', Level::ALL);
        if (Level::$levels[$level] & $logLevel) {
            $str = $level . self::SEPARATOR . \implode(self::SEPARATOR, array_map('\ZPHP\Common\Log::myJson', $params));
            if ($this->_config['type_file']) {
                $logFile = $this->_config['dir'] . \DS . $level . '.' . $this->_config['suffix'];
            } else {
                $logFile = $this->_config['dir'] . \DS . ZConfig::getField('project', 'project_name', 'log') . '.' . $this->_config['suffix'];
            }
            \file_put_contents($logFile, $str . "\n", FILE_APPEND|LOCK_EX);
        }
        return false;
    }

}