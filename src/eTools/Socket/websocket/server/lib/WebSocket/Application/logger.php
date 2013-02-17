<?php

namespace WebSocket\Application;
use eBot\Config\Config;

class logger extends Application {

    private $_clients = array();
    private $_socket = null;

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
            socket_sendto($this->_socket, $data, strlen($data), 0, Config::getInstance()->getBot_ip(), Config::getInstance()->getBot_port());
        }
        foreach($this->_clients as $sendto) {
            if ($sendto == $client)
            continue;
            $sendto->send($data);
        }
    }
}