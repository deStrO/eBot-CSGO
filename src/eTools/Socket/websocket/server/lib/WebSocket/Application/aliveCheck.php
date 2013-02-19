<?php

namespace WebSocket\Application;
use eBot\Config\Config;

class aliveCheck extends Application {

    private $_clients = array();
    private $_socket = null;

    public function onConnect($client) {
        if (!isset($this->_socket))
            $this->_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $id = $client->getClientId();
        $this->_clients[$id] = $client;
        if ($client->getClientIp() != Config::getInstance()->getBot_ip())
            socket_sendto($this->_socket, '__aliveCheck__', strlen('__aliveCheck__'), 0, Config::getInstance()->getBot_ip(), Config::getInstance()->getBot_port());
    }
    public function onDisconnect($client) {
        $id = $client->getClientId();
        unset($this->_clients[$id]);
    }

    public function onData($data, $client) {
        foreach($this->_clients as $sendto) {
            $sendto->send($data);
        }
    }
}