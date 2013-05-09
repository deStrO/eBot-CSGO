<?php
/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eTools\Socket;

use eTools\Exception\SocketException;
use eTools\Utils\Logger;

class UDPSocket {
    private $socket = null;

    public function __construct($bot_ip, $bot_port) {
        Logger::debug("Creating $bot_ip:$bot_port");
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($this->socket) {
            if (socket_bind($this->socket, $bot_ip, $bot_port)) {
                if (!socket_set_nonblock($this->socket)) {
                    throw new SocketException("Can't set non-block mode !");
                }
            } else {
                throw new SocketException("Can't bind the socket");
            }
        } else {
            throw new SocketException("Can't create the socket");
        }
    }

    public function recvfrom(&$ip) {
        $int = @socket_recvfrom($this->socket, $line, 1500, 0, $from, $port);
        if ($int) {
            $ip = $from . ":" . $port;
            return $line;
        } else {
            usleep(1000);
        }
    }

    public function sendto($mess, $ip, $port) {
        return socket_sendto($this->socket, $mess, strlen($mess), 0, $ip, $port);
    }
}

?>
