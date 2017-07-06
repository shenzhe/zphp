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

class Ant extends Base
{
    public function display()
    {
        //http服务，请求头需处理
        if (Request::isHttp()) {
            $data = \json_encode($this->model, JSON_UNESCAPED_UNICODE);
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

            echo $data;
            return null;
        }

        //长驻服务，数据直接返回
        if (Request::isLongServer()) {
            if (class_exists('swoole_serialize')) {
                return \swoole_serialize::pack([Response::getHeaders(), $this->model]);
            }
            return \json_encode([Response::getHeaders(), $this->model], JSON_UNESCAPED_UNICODE);
        }

        //正常的php服务，直接echo
        echo \json_encode($this->model, JSON_UNESCAPED_UNICODE);
        return null;

    }
}
