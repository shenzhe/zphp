<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 *  controller 接口
 */
namespace ZPHP\Controller;
interface IController
{
    function setServer($server);

    function _before();

    function _after();
}