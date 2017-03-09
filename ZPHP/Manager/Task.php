<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 2017/3/8
 * Time: 09:49
 */

namespace ZPHP\Manager;


use ZPHP\Cache\Factory as ZCache;
use ZPHP\Conn\Factory as ZConn;
use ZPHP\ZPHP;

class Task
{
    public static $map = [
        'cache' => '_task_cache_',
        'conn' => '_task_conn_',
    ];

    public static function check($data)
    {
        foreach (self::$map as $key => $val) {
            $len = strlen($val);
            $pre = substr($data, 0, $len);
            if ($pre == $val) {
                return [
                    $key, substr($data, $len),
                ];
            }
        }

        return false;
    }

    public static function handle($params)
    {
        if ('cache' == $params[0]) {
            return self::cache($params[1]);
        }

        if ('conn' == $params[0]) {
            return self::conn($params[1]);
        }
    }

    public static function cache($data)
    {
        $input = json_decode($data, true);
        $phpCache = ZCache::getInstance('Php', ['_prefix' => self::$map['cache']]);
        switch ($input['type']) {
            case 'add':
                $phpCache->add($input['key'], $input['value'], $input['ttl']);
                break;
            case 'set':
                $phpCache->add($input['key'], $input['value'], $input['ttl']);
                break;
            case 'get':
                return $phpCache->get($input['key']);
                break;
            case 'delete':
                $phpCache->delete($input['key']);
                break;
            case 'increment':
                $phpCache->increment($input['key'], $input['step']);
                break;
            case 'decrement':
                $phpCache->decrement($input['key'], $input['step']);
                break;
            case 'clear':
                $phpCache->clear();
                break;
            case 'all':
                return $phpCache->all();
                break;
            case 'load':
                $phpCache->load($input['workerId']);
                break;
            case 'flush':
                $phpCache->flush($input['workerId']);
                break;
        }
    }

    public static function conn($data)
    {
        $input = json_decode($data, true);
        $conn = ZConn::getInstance('Php', ['_prefix' => self::$map['conn']]);
        switch ($input['type']) {
            case 'get':
                return $conn->get($input['uid']);
                break;
            case 'getUid':
                return $conn->getUid($input['fd']);
                break;
            case 'add':
                $conn->add($input['uid'], $input['fd']);
                break;
            case 'addFd':
                $conn->addFd($input['fd'], $input['uid']);
                break;
            case 'addChannel':
                $conn->addChannel($input['fd'], $input['channel']);
                break;
            case 'delChannel':
                $conn->delChannel($input['fd'], $input['channel']);
                break;
            case 'getChannel':
                return $conn->getChannel($input['channel']);
                break;
            case 'clear':
                $conn->clear();
                break;
            case 'delete':
                $conn->delete($input['fd'], $input['uid'], $input['oid']);
                break;
            case 'delBuff':
                $conn->delBuff($input['fd']);
                break;
            case 'setBuff':
                $conn->setBuff($input['fd'], $input['data']);
                break;
            case 'getBuff':
                return $conn->getBuff($input['fd']);
                break;
            case 'load':
                $conn->load($input['workerId']);
                break;
            case 'flush':
                $conn->flush($input['workerId']);
                break;
        }
    }

    public static function flush($workerId, $type, $data)
    {
        $dir = ZPHP::getRootPath() . DS . 'tmp';
        if (!is_dir($dir)) {
            if (!mkdir($dir)) {
                return false;
            }
        }
        $filename = $dir . DS . '_tmp_' . $workerId . '_' . $type . '.tmp';
        file_put_contents($filename, serialize($data));
    }

    public static function load($workerId, $type)
    {
        $filename = ZPHP::getRootPath() . DS . 'tmp' . DS . '_tmp_' . $workerId . '_' . $type . '.tmp';
        if (is_file($filename)) {
            $data = unserialize(file_get_contents($filename));
            unlink($filename);
            return $data;
        }
        return false;
    }
}