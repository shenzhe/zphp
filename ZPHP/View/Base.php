<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 *  view接口
 */
namespace ZPHP\View;

class Base implements IView
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
    public function output()
    {

    }
}