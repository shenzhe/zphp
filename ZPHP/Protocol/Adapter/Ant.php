<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;

use ZPHP\Core\Config;
use ZPHP\Protocol\IProtocol;
use ZPHP\Protocol\Request;
use ZPHP\Common\MessagePacker;

class Ant implements IProtocol
{
    public function parse($_data)
    {
        $ctrlName = Config::getField('project', 'default_ctrl_name', 'main\\main');
        $methodName = Config::getField('project', 'default_method_name', 'main');
        if (Request::isHttp()) {
            $data = $_data;
            Request::addHeaders(Request::getRequest()->header, true);
        } else {
            $message = json_decode($_data, true);
            if (is_array($message[0])) {
                Request::addHeaders($message[0], true);
            }
            $data = is_array($message[1]) ? $message[1] : [];
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
