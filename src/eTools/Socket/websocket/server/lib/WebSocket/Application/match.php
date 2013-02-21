<?php

namespace WebSocket\Application;

use eBot\Config\Config;

class match extends Application {

    private $_clients = array();

    public function onConnect($client) {
        $id = $client->getClientId();
        $this->_clients[$id] = $client;
    }

    public function onDisconnect($client) {
        $id = $client->getClientId();
        unset($this->_clients[$id]);
    }

    public function onData($data, $client) {
        if ($client->getClientIp() != Config::getInstance()->getBot_ip()) {
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if (!$socket)
                die('Unable to create socket');
            if (!socket_bind($socket, Config::getInstance()->getBot_ip(), (Config::getInstance()->getBot_port() + 20)))
                die(socket_strerror(socket_last_error($socket)));
            socket_sendto($socket, $data, strlen($data), 0, Config::getInstance()->getBot_ip(), Config::getInstance()->getBot_port());
            socket_close($socket);
        }
        foreach ($this->_clients as $sendto) {
            if ($sendto == $client)
                continue;
            $sendto->send($data);
        }
    }

}