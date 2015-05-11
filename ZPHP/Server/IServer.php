<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server;

interface IServer
{
	public function run();

    /**
     * @return \ZPHP\Protocol\IProtocol
     */
    public function getProtocol();
}
