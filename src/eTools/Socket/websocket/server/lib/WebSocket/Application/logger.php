<?php

namespace WebSocket\Application;
use eBot\Config\Config;

class logger extends Application {

    private $_clients = array();
    private $_socket = null;
    private $_matchs = array();
    private $_sendByServer = null;

    public function onConnect($client) {
        if (!isset($this->_socket))
            $this->_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $id = $client->getClientId();
        $this->_clients[$id] = $client;
        if (count($this->_clients) > 1) {
            $data = '__true__';
            socket_sendto($this->_socket, $data, strlen($data), 0, Config::getInstance()->getBot_ip(), Config::getInstance()->getBot_port());
        }
    }
    public function onDisconnect($client) {
        $id = $client->getClientId();
        unset($this->_clients[$id]);
        if (count($this->_clients) == 1) {
            $data = '__false__';
            socket_sendto($this->_socket, $data, strlen($data), 0, Config::getInstance()->getBot_ip(), Config::getInstance()->getBot_port());
        }
    }

    public function onData($data, $client) {
        if ($client->getClientIp() != Config::getInstance()->getBot_ip()) {
            if (preg_match('/registerMatch_(?<id>\d+)/', $data, $preg)) {
                $id = $client->getClientId();
                $this->_matchs[$id] = $preg["id"];
            } else
                socket_sendto($this->_socket, $data, strlen($data), 0, Config::getInstance()->getBot_ip(), Config::getInstance()->getBot_port());
            $this->_sendByServer = false;
        } else {
            $matchid = json_decode($data);
            $matchid = $matchid[0];
            $this->_sendByServer = true;
        }

        if ($this->_sendByServer) {
            foreach($this->_clients as $sendto) {
                $id = $sendto->getClientId();
                if ($sendto == $client OR $this->_matchs[$id] != $matchid)
                    continue;
                $sendto->send($data);
            }
        }
    }
}