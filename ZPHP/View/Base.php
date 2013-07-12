<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 *  view接口
 */
namespace ZPHP\View;

abstract class Base implements IView
{

    protected $model;

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }

    abstract public function display();

    public function render()
    {
        \ob_start();
        $this->display();
        $content = \ob_get_contents();
        \ob_end_clean();
        return $content;
    }
}