<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 *
 */


namespace ZPHP\View\Adapter;

use ZPHP;
use ZPHP\View\Base;
use ZPHP\Core\Config;
use ZPHP\Protocol\Response;
use ZPHP\Protocol\Request;

class Php extends Base
{
    private $tplFile;

    public function setTpl($tpl)
    {
        $this->tplFile = $tpl;
    }

    public function display()
    {
        Response::sendHttpHeader();
        $tplPath = ZPHP\Core\Config::getField('project', 'tpl_path', ZPHP\ZPHP::getRootPath() . DS . 'template' . DS . 'default' . DS);
        $fileName = $tplPath . $this->tplFile;
        if (!\is_file($fileName)) {
            throw new \Exception("no file {$fileName}");
        }
        if (!empty($this->model) && is_array($this->model)) {
            \extract($this->model);
        }
        if (Request::isLongServer()) {
            \ob_start();
            include "{$fileName}";
            $content = ob_get_contents();
            \ob_end_clean();
            return $content;
        }
        include "{$fileName}";
        return null;
    }


}
