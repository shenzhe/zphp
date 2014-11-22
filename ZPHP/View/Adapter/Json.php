<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * Json view
 */


namespace ZPHP\View\Adapter;
use ZPHP\View\Base,
    ZPHP\Common\Utils,
    ZPHP\Core\Config;

class Json extends Base
{
    public function display()
    {
        if (Config::get('server_mode') == 'Http') {
            $data = \json_encode($this->model);
            if(isset($_GET['jsoncallback'])) {
                Utils::header("Content-Type", 'application/x-javascript; charset=utf-8');
                echo $_GET['jsoncallback'].'('.$data.')';
            } else {
                Utils::header("Content-Type", "application/json; charset=utf-8");
                echo $data;
            }
            
        } else {
        	return \json_encode($this->model);
        }

        

    }


}
