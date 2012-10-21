<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * This class represents an IP socket
 *
 * It can connect to a remote host, send and receive packets
 *
 * @author  Sebastian Staudt
 * @package steam-condenser
 */
abstract class Socket {

    /**
     * The IP address the socket is connected to
     *
     * @var InetAddress
     */
    protected $ipAddress;

    /**
     * The port number the socket is connected to
     *
     * @var int
     */
    protected $portNumber;

    /**
     * @var string
     */
    protected $readBuffer = '';

    /**
     * The socket itself
     * @var resource
     */
    protected $socket;

    /**
     * Stores if the sockets extension is loaded
     * @var bool
     */
    protected $socketsEnabled;

    /**
     * Constructs the Socket object
     *
     * This will check if PHP's sockets extension is loaded which might be used
     * for socket communication.
     */
    public function __construct() {
        $this->socketsEnabled = extension_loaded('sockets');
    }

    /**
     * Destructor of this socket
     *
     * Automatically calls close()
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Connects the socket to the host with the given IP address and port
     * number
     *
     * @param string $ipAddress The IP address to connect to
     * @param int $portNumber The TCP port to connect to
     * @param int $timeout The timeout in milliseconds
     */
    abstract public function connect($ipAddress, $portNumber, $timeout);

    /**
     * Closes the socket
     */
    public function close() {
        if(!empty($this->socket)) {
            if($this->socketsEnabled) {
                @socket_close($this->socket);
            } else {
                @fclose($this->socket);
            }
            $this->socket = null;
        }
    }

    /**
     * Returns whether this socket has an open connection
     *
     * @return bool <var>true</var> if this socket is open
     */
    public function isOpen() {
        return !empty($this->socket);
    }

    /**
     * Receives the specified amount of data from the socket
     *
     * @param int $length The number of bytes to read from the socket
     * @return string The data read from the socket
     * @throws Exception if reading from the socket fails
     */
    public function recv($length = 128) {
        if($this->socketsEnabled) {
            $data = @socket_read($this->socket, $length, PHP_BINARY_READ);
        } else {
            $data = fread($this->socket, $length);
        }

        if(!$data) {
            throw new Exception('Could not read from socket.');
        }

        return $data;
    }

    /**
     * Waits for data to be read from this socket before the specified timeout
     * occurs
     *
     * @param int $timeout The number of milliseconds to wait for data arriving
     *        on this socket before timing out
     * @return bool whether data arrived on this socket before the timeout
     */
    public function select($timeout = 0) {
        $read = array($this->socket);
        $write = null;
        $except = null;

        $sec = floor($timeout / 1000);
        $usec = $timeout % 1000;
        if($this->socketsEnabled) {
            $select = socket_select($read, $write, $except, $sec, $usec);
        } else {
            $select = stream_select($read, $write, $except, $sec, $usec);
        }

        return $select > 0;
    }

    /**
     * Sends the specified data to the peer this socket is connected to
     *
     * @param string $data The data to send to the connected peer
     * @throws Exception if sending fails
     */
    public function send($data) {
        if($this->socketsEnabled) {
            $sendResult = socket_send($this->socket, $data, strlen($data), 0);
        } else {
            $sendResult = fwrite($this->socket, $data, strlen($data));
        }

        if(!$sendResult) {
            throw new Exception('Could not send data.');
        }
    }

    /**
     * Returns the file descriptor of the underlying socket
     *
     * @return resource The underlying socket descriptor
     */
    public function resource() {
        return $this->socket;
    }
}
