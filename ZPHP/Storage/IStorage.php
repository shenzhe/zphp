<?php
/**
 * User: shenzhe
 * Date: 13-6-19
 */

namespace ZPHP\Storage;

interface IStorage {
    //设置从库
    public function setSlave($name);

    public function getMutilMD($userId, $keys);

    public function getMD($userId, $key, $slaveConfig = "");

    public function getSD($userId, $key, $slaveConfig = "");

    public function setSD($userId, $key, $data);

    public function delSD($userId, $key, $slaveConfig = "");

    public function setMD($userId, $key, $data);

    public function setMultiMD($userId, $keys);

    public function del($userId, $key);

    public function close();
}