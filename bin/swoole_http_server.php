<?php
use ZPHP\ZPHP;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Core\Factory as ZFactory;

class HttpServer
{

    private static $instance;
    public static $server;
    public static $request;
    public static $response;
    public static $wsresponse;
    public static $http;
    private $zphp;
    private $webPath;
    private $defaultFiles = ['index.html', 'main.html', 'default.html'];
    private $configPath = 'default';
    private $mimes= [];



    public function __construct($webPath, $config='default')
    {

        $this->webPath = $webPath;
        if(!empty($config)) {
            $this->configPath = $config;
        }

        $http = new swoole_http_server("0.0.0.0", 9502);
        self::$wsresponse = new swoole_http_wsresponse();

        $http->set(
            array(
                'worker_num' => 4,
                'daemonize' => 0,
                'max_request' => 0,
                'dispatch_mode' => 0
            )
        );

        $http->on('WorkerStart', array($this, 'onWorkerStart'));
        $http->on('WorkerError', array($this, 'onWorkerError'));
        $http->on('WorkerStop', array($this, 'onWorkerStop'));

        $http->on('close', function(){
            $params = func_get_args();
//            echo "{$params[1]} close".PHP_EOL;
            $conn = $params[0]->connection_info($params[1]);
            if($conn['websocket_status'] > 1) {
                $parse = ZFactory::getInstance(ZConfig::getField('socket', 'parse_class', 'WebSocketChatParse'));
                $_REQUEST = $parse->close($params[1]);
                $this->zphp->run();
            }
        });

        $http->on('open', function($response) {
            echo "handshake success====".PHP_EOL;
            var_dump($response);
        });
        $http->on('message', function ($response) {
            var_dump($response);
            $data = $response->data;
            print_r(json_decode($data, true));
            echo "fd:".$response->fd." receive data:".$data.PHP_EOL;
//            $response->message("server:".$data);
            if(method_exists($response, 'message')) {
                echo "has method message=====".PHP_EOL;
            } else {
                var_dump($response);
                var_dump(get_class_methods($response));
                echo "no method message=====".PHP_EOL;
            }

            HttpServer::$wsresponse->fd = $response->fd;
//            var_dump($response);
//            echo ZConfig::getField('socket', 'parse_class')." parse class".PHP_EOL;
            $parse =  ZFactory::getInstance(ZConfig::getField('websocket', 'parse_class', 'WebSocketChatParse'));
            $_REQUEST = $parse->parse($data);
//            print_r($_REQUEST);
            $this->zphp->run();
        });

        $http->on('handshake', function ($request, $response) {
            if (!isset($request->header['sec-websocket-key']))
            {
                $this->log('Bad protocol implementation: it is not RFC6455.');
                $response->end('');
                return false;
            }
            if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $request->header['sec-websocket-key']) || 16 !== strlen(base64_decode($request->header['sec-websocket-key'])))
            {
                $this->log('Header Sec-WebSocket-Key: $key is illegal.');
                $response->end('');
                return false;
            }

            $headers =  array(
                'Upgrade' => 'websocket',
                'Connection' => 'Upgrade',
                'Sec-WebSocket-Accept' => ''. base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true)),
                'Sec-WebSocket-Version' => '13',
                'KeepAlive' => 'off'
            );


           print_r($headers);

            foreach($headers as $key => $val) {
                $response->header($key, $val);
            }

            $response->status(101);

            $response->end('');
        });

        $http->on('request', function ($request, $response) {
//            echo "fd:".$response->fd." path:".$request->server['path_info'].PHP_EOL;
            HttpServer::$request = $request;
            HttpServer::$response = $response;
            $_GET = $_POST = $_REQUEST = $_SERVER = array();
            
            if (isset($request->server)) {
                foreach ($request->server as $key => $value) {
                    $_SERVER[strtoupper($key)] = $value;
                }
            }
            if (isset($request->header)) {
                foreach ($request->header as $key => $value) {
                    $_SERVER['HTTP_' . strtoupper($key)] = $value;
                }
            }
            HttpServer::$server = $_SERVER;

            if($_SERVER['PATH_INFO'] == '/') {
                if(!empty($this->defaultFiles)) {
                    foreach ($this->defaultFiles as $file) {
                        $staticFile = $this->getStaticFile(DIRECTORY_SEPARATOR.$file);
                        if(is_file($staticFile)) {
                            $response->end(file_get_contents($staticFile));
                            return;
                        }
                    }
                }
            }

            if($_SERVER['PATH_INFO'] == '/favicon.ico') {
                $response->header('Content-Type', $this->mimes['ico']);
                $response->end('');
                return;
            }
            if($_SERVER['PATH_INFO'] == '/jump') {
                $this->log("jump to baidu");
                $response->header('Location', 'http://www.baidu.com');
                $response->status(302);
                $response->end();
                return;
            }

            $staticFile = $this->getStaticFile($_SERVER['PATH_INFO']);

            if(\is_dir($staticFile)) { //是目录
                foreach($this->defaultFiles as $file) {
                    if(is_file($staticFile.$file)) {
                        $response->header('Content-Type', 'text/html');
                        $response->end(file_get_contents($staticFile.$file));
                        return;
                    }
                }
            }

            $ext  = \pathinfo($_SERVER['PATH_INFO'], PATHINFO_EXTENSION);

            if(isset($this->mimes[$ext])) {  //非法的扩展名
                if (\is_file($staticFile)) { //读取静态文件
                    $response->header('Content-Type', $this->mimes[$ext]);
                    $response->end(file_get_contents($staticFile));
                    return;
                } else {
                    $response->status(404);
                    $response->end('');
                    return;
                }
            }

            if (isset($request->get)) {
                $_GET = $request->get;
                $_REQUEST+=$_GET;
            }

            if (isset($request->post)) {
                $_POST = $request->post;
                $_REQUEST+=$_POST;
            }

            ob_start();
            $result = $this->zphp->run();
            if(null == $result) {
                $result = ob_get_contents();
            }
            ob_end_clean();
            $response->end($result);
        });

        self::$http = $http;
        self::$http ->start();
    }

    public function log($msg)
    {
        echo $msg.PHP_EOL;
    }


    public function message($data) {
        if(empty(self::$wsresponse)) {
            self::$wsresponse = new swoole_http_wsresponse();
        }

        self::$wsresponse->message($data);
    }

    public function onWorkerStart()
    {
        //这里require zphp框架目录地址
        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ZPHP' . DIRECTORY_SEPARATOR . 'ZPHP.php';
        ///home/wwwroot/www.zphp.com, 是应用的地址
        $this->zphp = ZPHP::run($this->webPath, false, $this->configPath);
        ZConfig::set('server_mode', 'Http');
        $params = func_get_args();
//        echo "worker {$params[1]} start".PHP_EOL;
        $this->mimes = require 'mimes.php';
    }

    public function onWorkerStop()
    {
        $params = (func_get_args());
//        echo "{$params[1]} stop, code: {$params[3]}".PHP_EOL;
    }

    public function onWorkerError()
    {
        $params = (func_get_args());
//        echo "{$params[1]} error, code: {$params[3]}".PHP_EOL;
    }

    public static function getInstance($webPath, $config='default')
    {
        if (!self::$instance) {
            self::$instance = new HttpServer($webPath, $config);
        }
        return self::$instance;
    }

    private function getStaticFile($file, $path='webroot')
    {
        return $this->webPath.DIRECTORY_SEPARATOR.$path.$file;
    }

}

if (empty($argv[1])) {
    echo "example: php swoole_http_server.php 'your webapp path' 'config dir name'" . PHP_EOL;
    return;
}

define('USE_SWOOLE_HTTP_SERVER', 1);
HttpServer::getInstance($argv[1], $argv[2]);
