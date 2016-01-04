<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Session\Adapter;

use ZPHP\Manager;
use ZPHP\ZPHP;

class File
{
    private $gcTime = 1800;
    private $config;
    private $filename;

    public function __construct($config)
    {
        if (!empty($config['cache_expire'])) {
            $this->gcTime = $config['cache_expire'] * 60;
        }
        $this->config = $config;
    }

    public function open($path, $sid)
    {
        $this->filename = $this->getFileName($path, $sid);
    }

    public function close()
    {
        return true;
    }

    public function gc($time)
    {
        $path = $this->getPath();
        $files = \ZPHP\Common\Dir::tree($path);
        foreach($files as $file) {
            if(false !==strpos($file, 'sess_')) {
                if(fileatime($file) < (time() - $this->gcTime)) {
                    unlink($file);
                }
            }
        }
        return true;
    }

    public function read($sid)
    {
        $this->filename = $this->getFileName($sid);
        if (is_file($this->filename)) {
            $content = file_get_contents($this->filename);
            if (strlen($content) < 10) {
                unlink($this->filename);
                return false;
            }
            $time = floatval(substr($content, 0, 10));
            if ($time < (time() - $this->gcTime)) {
                unlink($this->filename);
                return false;
            }
            return substr($content, 10);
        }
    }

    public function write($sid, $data)
    {
        $this->filename = $this->getFileName($sid);
        $content = time() + $this->gcTime . $data;
        file_put_contents($this->filename, $content);
        return true;
    }

    public function destroy($sid)
    {
        $this->filename = $this->getFileName($sid);
        if (is_file($this->filename)) {
            unlink($this->filename);
            return false;
        }
    }

    private function getPath()
    {
        return isset($this->config['save_path']) ? $this->config['save_path'] : ZPHP::getRootPath() . DS . 'session_tmp';
    }

    private function getFileName($sid)
    {
        $path = $this->getPath();
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        if(!empty($this->config['callback']) && is_callable($this->config['callback'])) {
            return call_user_func($this->config['callback'], $path, $sid);
        }

        return $path . DS . 'sess_' . $sid;
    }
}
