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

    /**
	 *  获取当前业务的ctrl名
	 */
    public function getCtrl();

    /**
	 *  获取当前业务的methoed名
	 */
    public function getMethod();

    /**
	 *  获取当前业务的参数
	 */
    public function getParams();

    /**
	 *  显示当前业务逻辑处理后的结果数据
	 */
    public function display($model);

}
