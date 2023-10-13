<?php

class WebSocket
{

    private static $sock = null;

    public function __call($name, $arguments)
    {
        \eTools\Utils\Logger::error("Call undefined method $name " . print_r($arguments, true));
    }

    public function open()
    {

    }

    public function __construct($url)
    {
        $config = \eBot\Config\Config::getInstance();
        $redis = new \Redis();
        $redis->connect(
            $config->getRedisHost(),
            $config->getRedisPort(),
            1,
            null,
            0,
            0,
            $config->getRedisAuthUsername() ? ['auth' => [$config->getRedisAuthUsername(), $config->getRedisAuthPassword()]] : []
        );

        $this->client = $redis;
        $this->url = str_replace("ws://", "http://", $url);
        preg_match("!^http://(.*):(\d+)/(.*)$!", $this->url, $match);
        $this->ip = $match[1];
        $this->port = $match[2] + 1;
        $this->scope = $match[3];
        \eTools\Utils\Logger::log("Setting WebSocket fix to " . $this->ip . ":" . $this->port);

        if (self::$sock == null)
            self::$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    public function sendData($data_string)
    {
        $config = \eBot\Config\Config::getInstance();
        $msg = json_encode(["scope" => $this->scope, "data" => $data_string]);
        $this->client->publish($config->getRedisChannelEbotToWs(), $msg);
    }

    public function send($data_string)
    {
        $config = \eBot\Config\Config::getInstance();
        $msg = json_encode(["scope" => $this->scope, "data" => $data_string]);
        $this->client->publish($config->getRedisChannelEbotToWs(), $msg);
    }

}

?>
