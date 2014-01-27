<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * socket server接口
 */
namespace ZPHP\Socket;
interface IServer
{
	/**
	 * 设置socket回调类
	 */
    function setClient($client);

    /**
     * 运行socket服务
     */
    function run();
}