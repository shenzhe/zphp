<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol;

interface IProtocol
{
    /**
     * @param $data 原始数据
     * @return mixed
     * @desc 把原始数据流parse成框架统一数据格式
     */
    public function parse($data);

}
