<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 
 */


namespace ZPHP\View\Adapter;
use ZPHP,
    ZPHP\View\Base,
    ZPHP\Core\Config;

class Php extends Base
{
    private $tplFile;

    public function setTpl($tpl)
    {
        $this->tplFile = $tpl;
    }

    public function display()
    {
        $tplPath = ZPHP\Core\Config::getField('project', 'tpl_path', ZPHP\ZPHP::getRootPath() . DS  . 'template' . DS . 'default'. DS);
        $fileName = $tplPath . $this->tplFile;
        if (!\is_file($fileName)) {
            throw new \Exception("no file {$fileName}");
        }
        if (!empty($this->model) && is_array($this->model)) {
            \extract($this->model);
        }
        if (ZPHP\Protocol\Request::isLongServer()) {
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
