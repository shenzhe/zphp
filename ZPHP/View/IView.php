<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 *  view接口
 */
namespace ZPHP\View;
interface IView
{
    function setModel($model);

    function getModel();
    //删除该方法  , Base抽象类 未实现该方法  change by ahuo 2013-09-12 21:49
    //function display();

    function render();
}
