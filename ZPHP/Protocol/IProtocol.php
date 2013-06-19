<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol;

interface IProtocol
{
    public function getAction();
    public function getMethod();
    public function getParams();
    public function display($model);
}