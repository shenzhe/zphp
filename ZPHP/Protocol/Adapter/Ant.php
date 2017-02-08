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
        $message = new MessagePacker($_data);
        $headers = json_decode($message->readString(), true);
        if (is_array($headers)) {
            Request::addHeaders($headers, true);
        }
        $data = json_decode($message->readString(), true);
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
