<?php
/**
 * User: shenzhe
 * Date: 13-6-19
 */
namespace socket;

use ZPHP\Core;

class ReactThread extends \Thread
{
    private $_conn;
    private $_data;
    private $_server;

    public function __construct($conn, $data, $server)
    {
        $this->_conn = $conn;
        $this->_data = $data;
        $this->_server = $server;
        $this->start();
    }

    public function run()
    {
        $result = $this->_server->parse($this->_data);
        if (empty($result['a'])) {
            $data = $this->_data;
        } else {
            $this->_server = $this->route($this->_server);
            $data = $this->_server->getData();
        }
        \stream_socket_sendto($this->_conn, $data."\n");
    }

    private function route($server)
    {
        try {
            Core\Route::route($server);
        } catch (\Exception $e) {
            $server->display($e->getMessage());
        }
        return $server;
    }
}