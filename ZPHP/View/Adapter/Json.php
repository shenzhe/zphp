<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * Json view
 */


namespace ZPHP\View\Adapter;
use ZPHP\Protocol\Request;
use ZPHP\Protocol\Response;
use ZPHP\View\Base,
    ZPHP\Core\Config;

class Json extends Base
{
    public function display()
    {
        if (Request::isHttp()) {
            $params = Request::getParams();
            if(isset($params['jsoncallback'])) {
                Response::header("Content-Type", 'application/x-javascript; charset=utf-8');
            } else {
                Response::header("Content-Type", "application/json; charset=utf-8");
            }
        }

        $data = \json_encode($this->model);

        if(Request::isLongServer()) {
            return $data;
        }
        echo $data;
        return null;

    }


}
