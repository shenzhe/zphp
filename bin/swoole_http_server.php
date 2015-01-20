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
    public static $wsfarme;
    public static $http;
    private $zphp;
    private $webPath;
    private $defaultFiles = ['index.html', 'main.html', 'default.html'];
    private $configPath = 'default';
    private $mimes = [];


    public function __construct($opt, $config = 'default')
    {
        $this->webPath = $opt['path'];
        if (!empty($opt['config'])) {
            $this->configPath = $opt['config'];
        }

        $ip = empty($opt['ip']) ? '0.0.0.0' : $opt['ip'];
        $port = empty($opt['port']) ? '9501' : $opt['port'];

        $http = new swoole_http_server($ip, $port);
        self::$wsfarme = new swoole_websocket_frame();
        if (isset($opt['d'])) {
            $daemonize = 1;
        } else {
            $daemonize = 0;
        }
        $worker_num = empty($opt['worker']) ? 4 : $opt['worker'];
        $http->set(
            array(
                'worker_num' => $worker_num,
                'daemonize' => $daemonize,
                'max_request' => 0
            )
        );

        $http->setGlobal(HTTP_GLOBAL_ALL, HTTP_GLOBAL_GET|HTTP_GLOBAL_POST);

        $http->on('WorkerStart', array($this, 'onWorkerStart'));
        $http->on('WorkerError', array($this, 'onWorkerError'));
        $http->on('WorkerStop', array($this, 'onWorkerStop'));

        $http->on('close', function () {
            $params = func_get_args();
            $conn = $params[0]->connection_info($params[1]);
            if ($conn['websocket_status'] > 1) {
                $parse = ZFactory::getInstance(ZConfig::getField('socket', 'parse_class', 'WebSocketChatParse'));
                $parse->close($this->zphp, $params[1]);
            }
        });

        $http->on('open', function ($response) {
            $parse = ZFactory::getInstance(ZConfig::getField('socket', 'parse_class', 'WebSocketChatParse'));
            $parse->open($this->zphp, $response->fd);
        });

        $http->on('message', function ($frame) {
            HttpServer::$wsfarme = $frame;
            $parse = ZFactory::getInstance(ZConfig::getField('websocket', 'parse_class', 'WebSocketChatParse'));
            $parse->message($this->zphp, $frame);
        });

        $http->on('request', function ($request, $response) {
            HttpServer::$request = $request;
            HttpServer::$response = $response;
            if ($_SERVER['PATH_INFO'] == '/') {
                if (!empty($this->defaultFiles)) {
                    foreach ($this->defaultFiles as $file) {
                        $staticFile = $this->getStaticFile(DIRECTORY_SEPARATOR . $file);
                        if (is_file($staticFile)) {
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



            $staticFile = $this->getStaticFile($_SERVER['PATH_INFO']);

            if (\is_dir($staticFile)) { //是目录
                foreach ($this->defaultFiles as $file) {
                    if (is_file($staticFile . $file)) {
                        $response->header('Content-Type', 'text/html');
                        $response->end(file_get_contents($staticFile . $file));
                        return;
                    }
                }
            }

            $ext = \pathinfo($_SERVER['PATH_INFO'], PATHINFO_EXTENSION);

            if (isset($this->mimes[$ext])) {  //非法的扩展名
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
            try {
                ob_start();
                $result = $this->zphp->run();
                if (null == $result) {
                    $result = ob_get_contents();
                }
                ob_end_clean();
            }  catch (Exception $e) {
                $result = json_encode($e->getTrace());
            }

            $response->status(200);
            $response->end($result);
        });

        self::$http = $http;
        self::$http->start();
    }

    public function onWorkerStart()
    {
        //这里require zphp框架目录地址
        opcache_reset();
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

    public static function getInstance($webPath, $config = 'default')
    {
        if (!self::$instance) {
            self::$instance = new HttpServer($webPath, $config);
        }
        return self::$instance;
    }

    private function getStaticFile($file, $path = 'webroot')
    {
        return $this->webPath . DIRECTORY_SEPARATOR . $path . $file;
    }

}


define('USE_SWOOLE_HTTP_SERVER', 1);
$opt = getopt("d", [
    "path::",
    "ip::",
    "port::",
    "worker::",
    "config::"
]);
if (empty($opt['path'])) {
    echo "examples:  php swoole_http_server.php --path=/home/www/zphpdemo --config=default --ip=0.0.0.0 --port=9501 --worker=4 -d" . PHP_EOL;
    echo "path is required" . PHP_EOL;
    return;
}

HttpServer::getInstance($opt);
