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

    function display();

    function render();
}