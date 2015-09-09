<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;
use ZPHP\Core;
use ZPHP\Core\Config;
use ZPHP\Protocol\Request;
use ZPHP\View;
use ZPHP\Protocol\IProtocol;

class Cli implements IProtocol
{

    /**
     * 会取$_SERVER['argv']最后一个参数
     * 原始格式： a=action&m=method&param1=val1
     * @param $_data
     * @return bool
     */
    public function parse($_data)
    {
        $ctrlName = Config::getField('project', 'default_ctrl_name', 'main\\main');
        $methodName = Config::getField('project', 'default_method_name', 'main');
        \parse_str(array_pop($_data), $data);
        $apn = Config::getField('project', 'ctrl_name', 'a');
        $mpn = Config::getField('project', 'method_name', 'm');
        if (isset($data[$apn])) {
            $ctrlName = \str_replace('/', '\\', $data[$apn]);
        }
        if (isset($data[$mpn])) {
            $methodName = $data[$mpn];
        }
        Request::init($ctrlName, $methodName, $data, Config::getField('project', 'view_mode', 'String'));
        return true;
    }
}
