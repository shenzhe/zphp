<?php

namespace socket;

use common\Utils;
class Swoole implements ISocket
{


    public function onStart() {
        echo 'server start';
    }

    public function onConnect() {
        $params = func_get_args();
        $fd = $params[1];
        echo "Client {$fd}：Connect\n";
    }

    public function onReceive(){

    }

    public function onClose() {
        $params = func_get_args();
        $fd = $params[1];
        echo "Client {$fd}: close";
    }

    public function onShutdown() {
        echo "server close";
    }

    public function sendOne($serv, $fd, $data) {
        if(empty($serv) || empty($fd) || empty($data)) {
            return ;
        }
        $data = json_encode($data);
        echo "send {$fd} data={$data}\n";
        return \swoole_server_send($serv, $fd, $data."\0");
    }

    public function sendToChannel($serv, $data, $channel='ALL') {
        $list = $this->getConnection()->getChannel($channel);
        if(empty($list)) {
            return ;
        }

        foreach($list as $fd) {
            $this->sendOne($serv, $fd, $data);
        }
    }

    public function heartbeat() {

    }

    public function hbcheck($serv) {
        $list = $this->getConnection()->getChannel();
//        Log::info('socket', ['hbcheck', var_export($list, true)], true);
        if(empty($list)) {
            return ;
        }

        foreach($list as $uid=>$fd) {
            if(!$this->getConnection()->heartbeat($uid)) {
                $this->sendOne($serv, $fd, [8, []]);
//               $this->getConnection()->delete($fd, $uid);
//                \swoole_server_close($serv, $fd);
            }
        }
    }

    public function onTimer() {
        $params = func_get_args();
        $serv = $params[0];
        $interval = $params[1];
//        Log::info('socket', ['timer', $interval], true);
        switch ($interval) {
            case 66:                //heartbeat check
                $this->hbcheck($serv);
                break;
        }

    }

    public function rpc($params) {
//        $fcgiClient = new fcgiClient('127.0.0.1', 9000);
//        $response = $fcgiClient->request(
//            ['query'=>http_build_query($params)]
//        );
//        if(!empty($response['content'])) {
//            return json_encode($response['content'], true);
//        }
//        return false;
        $client = new \Yar_Client(APP_HOST."rpc.php");
        try{
            $result = $client->api($params);
            return $result;
        }catch (\Exception $e) {
            return Formater::formatException($e);
        }
    }
}
