<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol;

interface IProtocol
{
	/**
	 *  解析不同方式传来的数据成统一的格式
	 */
    public function parse($data);

}
