<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 15-9-1
 * Time: 下午4:53
 */

namespace ZPHP\Log\Adapter;

use ZPHP\Log\Level;
use ZPHP\Log\Base;
use ZPHP\Core\Config as ZConfig;


class File extends Base
{

    private $_config;

    const SEPARATOR = ' | ';

    public function __construct($config)
    {
        if (!empty($config)) {
            $this->_config = $config;
        }
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
            $str = $level . self::SEPARATOR . $message . self::SEPARATOR . \implode(self::SEPARATOR, array_map('\ZPHP\Common\Log::myJson', $context));
            if ($this->_config['type_file']) {
                $logFile = $this->_config['dir'] . \DS . $level . '.' . $this->_config['suffix'];
            } else {
                $logFile = $this->_config['dir'] . \DS . ZConfig::getField('project', 'project_name', 'log') . '.' . $this->_config['suffix'];
            }
            \file_put_contents($logFile, $str . "\n", FILE_APPEND | LOCK_EX);
        }
        return false;
    }

}