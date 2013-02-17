<?php

namespace WebSocket\Application;
use eBot\Config\Config;

class livemap extends Application {

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
        foreach($this->_clients as $sendto) {
            if ($sendto == $client)
                continue;
            $sendto->send($data);
        }
    }
}