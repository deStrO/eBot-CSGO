<?php

namespace WebSocket\Application;
use eBot\Config\Config;

class livemap extends Application {

    private $_clients = array();
    private $_matchs = array();
    private $_sendByServer = null;

    public function onConnect($client) {
        $id = $client->getClientId();
        $this->_clients[$id] = $client;
    }
    public function onDisconnect($client) {
        $id = $client->getClientId();
        unset($this->_clients[$id], $this->_matchs[$id]);
    }

    public function onData($data, $client) {
        if ($client->getClientIp() != Config::getInstance()->getBot_ip()) {
            if (preg_match('/registerMatch_(?<id>\d+)/', $data, $preg)) {
                $id = $client->getClientId();
                $this->_matchs[$id] = $preg["id"];
            }
        } else {
            $matchid = json_decode($data, true);
            $matchid = $matchid['id'];
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