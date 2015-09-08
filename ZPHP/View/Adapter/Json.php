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
        if (Config::get('server_mode') == 'Http') {
            $data = \json_encode($this->model);
            $params = Request::getParams();
            if(isset($params['jsoncallback'])) {
                Response::header("Content-Type", 'application/x-javascript; charset=utf-8');
                echo $params['jsoncallback'].'('.$data.')';
            } else {
                Response::header("Content-Type", "application/json; charset=utf-8");
                echo $data;
            }
        } else {
        	return \json_encode($this->model);
        }

        

    }


}
