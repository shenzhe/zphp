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
use ZPHP\Common\WebSocketClient;


class WebSocket extends Base
{

    private $_config;
    private $_client;

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
            $str = $level . self::SEPARATOR . $message. self::SEPARATOR. \implode(self::SEPARATOR, array_map('\ZPHP\Common\Log::myJson', $context));
            if(!$this->_client) {
                $this->_client = new WebSocketClient($this->_config['host'], $this->_config['port']);
                $this->_client->connect();
            }
            $this->_client->send($str);
        }
        return false;
    }

}