<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 16-4-5
 * Time: 上午11:00
 */

namespace ZPHP\Common;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Protocol\Request;



class Lang
{
    public static function get($key)
    {
        $local = ZConfig::getField('project', 'lang', 'zh_cn');
        if(isset($config['lang'][$local][$key])) {
            return $config['lang'][$local][$key];
        }

        return $key;
    }
}