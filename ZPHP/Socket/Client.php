<?php

namespace ZPHP\Socket;


class Client
{
    private $sockfp = null;

    public function __construct($host, $port)
    {
        if (!function_exists('socket_create')) {
            throw new \Exception("can not enabled socket");
        }

        $this->sockfp = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (false === $this->sockfp) {
            $this->getError();
        } else {
            socket_connect($this->sockfp, $host, $port);
        }
        return;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function getError()
    {
        $code = socket_last_error($this->sockfp);
        throw new \Exception(socket_strerror($code), $code);
    }

    public function disconnect()
    {
        if ($this->sockfp) {
            socket_close($this->sockfp);
            $this->sockfp = null;
        }
    }

    public function send($data)
    {
        if (!$this->sockfp) {
            return false;
        }

        $dataLen = strlen($data);
        while (true) {
            $len = socket_write($this->sockfp, $data, $dataLen);
            if (false === $len) {
                $this->getError();
            } elseif ($len < $dataLen) {
                $data = substr($data, $len);
                $dataLen -= $len;
            } else {
                break;
            }
        }

        return true;
    }

    public function read()
    {
        if (!$this->sockfp) {
            return null;
        }
        $ret = '';
        socket_set_nonblock($this->sockfp);
        while (true) {
            $tmp = socket_read($this->sockfp, 4096);
            if (false === $tmp) {
                $this->getError();
            }

            if ('' === $tmp) {
                break;
            }

            $ret .= $tmp;
        }
        socket_set_block($this->sockfp);
        return $ret;
    }


}
