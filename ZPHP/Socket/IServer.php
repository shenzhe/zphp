<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 *  view接口
 */
namespace ZPHP\Socket;
interface IServer
{
    function setClient($client);
    function run();
}