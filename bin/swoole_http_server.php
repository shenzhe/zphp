<?php
use ZPHP\ZPHP;
use ZPHP\Core\Config as ZConfig;

class HttpServer
{

    private static $instance;
    public static $server;
    public static $request;
    public static $response;
    private $zphp;
    private $webPath;
    private $defaultFiles = ['index.html', 'main.html', 'default.html'];



    public function __construct($webPath)
    {

        $this->webPath = $webPath;

        $http = new swoole_http_server("0.0.0.0", 9502);

        $http->set(
            array(
                'worker_num' => 4,
                'daemonize' => 0,
                'max_request' => 10000,
                'dispatch_mode' => 0
            )
        );

        $http->on('WorkerStart', array($this, 'onWorkerStart'));

        $http->on('message', function ($data, $response) {
		echo "receive data:";
		var_dump($data);
                var_dump($response);
                $response->message("server:".$data);
	});
        $http->on('request', function ($request, $response) {
            print_r($request);
            HttpServer::$request = $request;
            HttpServer::$response = $response;
            $_GET = $_POST = $_REQUEST = $_SERVER = array();
            
            if (isset($request->server)) {
                foreach ($request->server as $key => $value) {
                    $_SERVER[strtoupper($key)] = $value;
                }
            }
            if (isset($request->header)) {
                foreach ($request->server as $key => $value) {
                    $_SERVER['HTTP_' . strtoupper($key)] = $value;
                }
            }
            HttpServer::$server = $_SERVER;

            if($_SERVER['PATH_INFO'] == '/') {
                if(!empty($this->defaultFiles)) {
                    foreach ($this->defaultFiles as $file) {
                        $staticFile = $this->getStaticFile(DIRECTORY_SEPARATOR.$file);
                        if(is_file($staticFile) {
                            $response->end(file_get_contents($staticFile));
                            return;
                        }
                    }
                }
            }

            $staticFile = $this->getStaticFile($_SERVER['PATH_INFO']);
            if(\is_file($staticFile)) { //读取静态文件
                $response->end(file_get_contents($staticFile));
                return;
            }


            if($_SERVER['PATH_INFO'] == '/favicon.ico') {
                $response->end('');
                return;
            }

            if (isset($request->get)) {
                $_GET = $request->get;
            }

            if (isset($request->post)) {
                $_POST = $request->post;
            }

            ob_start();
            $result = $this->zphp->run();
            if(null == $result) {
                $result = ob_get_contents();
            }
            ob_end_clean();
            $response->end($result);
        });

        $http->start();
    }

    public function onWorkerStart()
    {
        //这里require zphp框架目录地址
        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ZPHP' . DIRECTORY_SEPARATOR . 'ZPHP.php';
        ///home/wwwroot/www.zphp.com, 是应用的地址
        $this->zphp = ZPHP::run($this->webPath, false);
        ZConfig::set('server_mode', 'Http');
    }

    public static function getInstance($webPath)
    {
        if (!self::$instance) {
            self::$instance = new HttpServer($webPath);
        }
        return self::$instance;
    }

    private function getStaticFile($file, $path='webroot')
    {
        return $this->webPath.DIRECTORY_SEPARATOR.$path.$file);
    }
}

if (empty($argv[1])) {
    echo "example: php swoole_http_server.php 'your webapp path'" . PHP_EOL;
    return;
}

define('USE_SWOOLE_HTTP_SERVER', 1);
HttpServer::getInstance($argv[1]);
