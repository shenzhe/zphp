<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;

use ZPHP\Core\Config,
    ZPHP\Socket\Client as ZSClient,
    ZPHP\Protocol\IProtocol,
    ZPHP\View;
use ZPHP\Protocol\Request;

class Json implements IProtocol
{
    public function parse($_data)
    {
        $ctrlName = Config::getField('project', 'default_ctrl_name', 'main\\main');
        $methodName = Config::getField('project', 'default_method_name', 'main');
        $data = [];
        if (!empty($_data)) {
            if (is_array($_data)) {
                $data = $_data;
            } else {
                $data = \json_decode($_data, true);
            }
        }
        $apn = Config::getField('project', 'ctrl_name', 'a');
        $mpn = Config::getField('project', 'method_name', 'm');
        if (isset($data[$apn])) {
            $ctrlName = \str_replace('/', '\\', $data[$apn]);
        }
        if (isset($data[$mpn])) {
            $methodName = $data[$mpn];
        }
        Request::init($ctrlName, $methodName, $data, Config::getField('project', 'view_mode', 'Json'));
        return true;
    }
}
