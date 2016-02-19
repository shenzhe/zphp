<?php
/**
 * User: shenzhe
 * Date: 13-6-19
 */

namespace ZPHP\Queue;

interface IQueue
{
    /**
     * @param $key
     * @return mixed
     * @desc 取出队头数据
     */
    public function get($key);

    /**
     * @param $key
     * @param $data
     * @return mixed
     * @desc 向队尾里添加数据
     */
    public function add($key, $data);
}