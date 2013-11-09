<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eTools;

abstract class Rcon {

    protected $ip;
    protected $port;
    protected $rcon;
    protected $status;

    public function __construct($ip, $port, $rcon) {
        $this->ip = $ip;
        $this->port = $port;
        $this->rcon = $rcon;

        if (!$this->auth()) {
            throw new \eBot\Exception\MatchException("Can't auth to rcon " . $this->ip . ":" . $this->port . " (" . $this->error . ")");
        }
    }

    public function getIp() {
        return $this->ip;
    }

    public function getPort() {
        return $this->port;
    }

    public function getRcon() {
        return $this->rcon;
    }

    public function getError() {
        return $this->error;
    }

    public function getState() {
        return $this->status;
    }

    public abstract function auth();

    public abstract function send($cmd);
}

?>
