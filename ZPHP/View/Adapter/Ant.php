<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * Json view
 */


namespace ZPHP\View\Adapter;

use ZPHP\Protocol\Request;
use ZPHP\Protocol\Response;
use ZPHP\View\Base;
use ZPHP\Core\Config;
use ZPHP\Common\MessagePacker;

class Ant extends Base
{
    public function display()
    {
        $data = \json_encode($this->model, JSON_UNESCAPED_UNICODE);

        if (Request::isHttp()) {
            Response::sendHttpHeader();
            $params = Request::getParams();
            $key = Config::getField('project', 'jsonp', 'jsoncallback');
            if (isset($params[$key])) {
                Response::header("Content-Type", 'application/x-javascript; charset=utf-8');
                $data = $params[$key] . '(' . $data . ')';
            } else {
                Response::header("Content-Type", "application/json; charset=utf-8");
            }

            if (Request::isLongServer()) {
                return $data;
            }
        }

        if (Request::isLongServer()) {
            $message = new MessagePacker();
            $header = Response::getHeaders();
            $message->writeString(json_encode($header));
            $message->writeString($data);
            return $message->getData();
        }
        echo $data;
        return null;

    }


}
