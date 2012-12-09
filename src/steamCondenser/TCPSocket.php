<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'Socket.php';

/**
 * This class represents a TCP socket
 *
 * It can connect to a remote host, send and receive packets
 *
 * @author  Sebastian Staudt
 * @package steam-condenser
 */
class TCPSocket extends Socket {

    /**
     * Connects the TCP socket to the host with the given IP address and port
     * number
     *
     * Depending on whether PHP's sockets extension is loaded, this uses either
     * <var>socket_create</var>/<var>socket_connect</var> or
     * <var>fsockopen</var>.
     *
     * @param string $ipAddress The IP address to connect to
     * @param int $portNumber The TCP port to connect to
     * @param int $timeout The timeout in milliseconds
     * @throws Exception if an error occurs during connecting the socket
     */
    public function connect($ipAddress, $portNumber, $timeout) {
        $this->ipAddress = $ipAddress;
        $this->portNumber = $portNumber;

        if($this->socketsEnabled) {
            if(!$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
                $errorCode = socket_last_error($this->socket);
                throw new Exception('Could not create socket: ' . socket_strerror($errorCode));
            }

            socket_set_nonblock($this->socket);
            @socket_connect($this->socket, $ipAddress, $portNumber);
            $write = array($this->socket);
            $read = $except = array();
            $sec = floor($timeout / 1000);
            $usec = $timeout % 1000;
            if(!socket_select($read, $write, $except, $sec, $usec)) {
                $errorCode = socket_last_error($this->socket);
            } else {
                $errorCode = socket_get_option($this->socket, SOL_SOCKET, SO_ERROR);
            }

            if($errorCode) {
                throw new Exception('Could not connect socket: ' . socket_strerror($errorCode));
            }

            socket_set_block($this->socket);
        } else {
            if(!$this->socket = @fsockopen("tcp://$ipAddress", $portNumber, $socketErrno, $socketErrstr, $timeout / 1000)) {
                throw new Exception("Could not create socket: $socketErrstr");
            }
            stream_set_blocking($this->socket, true);
        }
    }
}
