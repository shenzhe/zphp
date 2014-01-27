<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 *  view接口
 */
namespace ZPHP\View;
interface IView
{
	//存入数据
    public function setModel($model);

    //获取数据
    public function getModel();
    //删除该方法  , Base抽象类 未实现该方法  change by ahuo 2013-09-12 21:49
    //function display();

    //渲染数据
    public function render();
}
