<?php
/**
 * User: shenzhe
 * Date: 13-6-19
 */
namespace socket;

class ReactThread implements Thread
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
        $this->_server->parse($this->_data);

        $this->_server = $this->route($this->_server);
        $this->_conn->write($this->_server->getData() . "\n");
    }
}