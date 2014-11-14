<?php
use ZPHP\ZPHP;

class HttpServer
{

	private static $instance;
	private $zphp;
	private $webPath;


	public function __construct($webPath) {

		$this->webPath = $webPath;
		
		$http = new swoole_http_server("127.0.0.1", 9502);

		$http->set(
				array(
					'worker_num' => 16,
					'daemonize' => 1,
					'max_request' => 10000,
					'dispatch_mode' => 1
				     )
			  );

		$http->on('WorkerStart' , array( $this , 'onWorkerStart'));

		$http->on('request', function ($request, $response) {
			$_GET = $_POST = $_SERVER = array();
			$_SERVER['REQUEST_TIME'] = time();

			if( isset($request->server) ) {
			    foreach ($request->server as $key => $value) {
					$_SERVER[ strtoupper($key) ] = $value;
				}
			}
			if( isset($request->header) ) {
				foreach ($request->server as $key => $value) {
					$_SERVER[ 'HTTP_'.strtoupper($key) ] = $value;
				}
			}

			if( isset($request->get) ) {
			    $_GET = $request->get;
			}
			if( isset($request->post) ) {
			    $_POST = $request->post;
			}
			ob_start();
            $this->zphp->run();
			$result = ob_get_contents();
			ob_end_clean();
			$response->end($result);
		});

		$http->start();
	}

	public function onWorkerStart() {
		//这里require zphp框架目录地址
	    require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ZPHP' . DIRECTORY_SEPARATOR . 'ZPHP.php';
	    ///home/wwwroot/www.zphp.com, 是应用的地址
	    $this->zphp = ZPHP::run($this->webPath, false);
	}

	public static function getInstance($webPath) {
		if (!self::$instance) {
			self::$instance = new HttpServer($webPath);
		}
		return self::$instance;
	}
}

if(empty($argv[1])) {
	echo "example: php swoole_http_server.php 'your webapp path'".PHP_EOL;
	return ;
}

HttpServer::getInstance($argv[1]);