<?php
/**
 * User: shenzhe
 * Date: 13-6-19
 */

namespace ZPHP\Storage;

interface IStorage
{
    //设置从库
    public function setSlave($name);
    //批量获取数据
    public function getMutilMD($userId, $keys, $slaveConfig = "");
    //获取某个数据
    public function getMD($userId, $key, $slaveConfig = "");
    //从从库中获取某个数据
    public function getSD($userId, $key, $slaveConfig = "");
    //从库中设置某个数据
    public function setSD($userId, $key, $data);
    //从库中删除某个数据
    public function delSD($userId, $key, $slaveConfig = "");
    //设置某个数据
    public function setMD($userId, $key, $data);
    //批量设置某个数据
    public function setMultiMD($userId, $keys);
    //删除某个数据
    public function del($userId, $key);
    //关闭服务
    public function close();
}
