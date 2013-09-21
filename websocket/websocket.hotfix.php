<?php

class WebSocket {

    public function __call($name, $arguments) {
        \eTools\Utils\Logger::error("Call undefined method $name " . print_r($arguments, true));
    }

    public function open() {
        
    }

    public function __construct($url) {
        $this->url = str_replace("ws://", "http://", $url);
        $this->ch = curl_init($this->url);
        \eTools\Utils\Logger::log("Setting WebSocket fix to ".$this->url);
    }

    public function sendData($data_string) {
        if (!$this->ch) {
            $this->ch = curl_init($this->url);
        }
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );

        curl_exec($this->ch);
    }
    
    public function send($data_string) {
        if (!$this->ch) {
            $this->ch = curl_init($this->url);
        }
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );

        curl_exec($this->ch);
    }

}

?>
