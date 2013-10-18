<?php

class WebSocket {

    private static $sock = null;

    public function __call($name, $arguments) {
        \eTools\Utils\Logger::error("Call undefined method $name " . print_r($arguments, true));
    }

    public function open() {
        
    }

    public function __construct($url) {
        $this->url = str_replace("ws://", "http://", $url);
        preg_match("!^http://(.*):(\d+)/(.*)$!", $this->url, $match);
        $this->ip = $match[1];
        $this->port = $match[2] + 1;
        $this->scope = $match[3];
        \eTools\Utils\Logger::log("Setting WebSocket fix to " . $this->ip . ":" . $this->port);

        if (self::$sock == null)
            self::$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    public function sendData($data_string) {
        $msg = json_encode(array("scope" => $this->scope, "data" => $data_string));
        $len = strlen($msg);
        socket_sendto(self::$sock, $msg, $len, 0, $this->ip, $this->port);
    }

    public function send($data_string) {
        $msg = json_encode(array("scope" => $this->scope, "data" => $data_string));
        $len = strlen($msg);
        socket_sendto(self::$sock, $msg, $len, 0, $this->ip, $this->port);
    }

}

?>
