<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'ByteBuffer.php';
require_once STEAM_CONDENSER_PATH . 'UDPSocket.php';
require_once STEAM_CONDENSER_PATH . 'exceptions/TimeoutException.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/SteamPacketFactory.php';

/**
 * This abstract class implements common functionality for sockets used to
 * connect to game and master servers
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage sockets
 */
abstract class SteamSocket {

    /**
     * @var int The default socket timeout
     */
    protected static $timeout = 1000;

    /**
     * @var ByteBuffer
     */
    protected $buffer;

    /**
     * @var UDPSocket
     */
    protected $socket;

    /**
     * Sets the timeout for socket operations
     *
     * Any request that takes longer than this time will cause a {@link
     * TimeoutException}.
     *
     * @param int $timeout The amount of milliseconds before a request times
     *        out
     */
    public static function setTimeout($timeout) {
        self::$timeout = $timeout;
    }

    /**
     * Creates a new UDP socket to communicate with the server on the given IP
     * address and port
     *
     * @param string $ipAddress Either the IP address or the DNS name of the
     *        server
     * @param int $portNumber The port the server is listening on
     */
    public function __construct($ipAddress, $portNumber = 27015) {
        $this->socket = new UDPSocket();
        $this->socket->connect($ipAddress, $portNumber, 0);
    }

    /**
     * Closes this socket
     *
     * @see #close()
     */
    public function __destruct() {
        if(!empty($this->socket) && $this->socket->isOpen()) {
            $this->close();
        }
    }

    /**
     * Closes the underlying socket
     *
     * @see UDPSocket::close()
     */
    public function close() {
        $this->socket->close();
    }

    /**
     * Subclasses have to implement this method for their individual packet
     * formats
     *
     * @return SteamPacket The packet replied from the server
     */
    abstract public function getReply();

    /**
     * Reads the given amount of data from the socket and wraps it into the
     * buffer
     *
     * @param int $bufferLength The data length to read from the socket
     * @throws TimeoutException if no packet is received on time
     * @return int The number of bytes that have been read from the socket
     * @see ByteBuffer
     */
    public function receivePacket($bufferLength = 0) {
        if(!$this->socket->select(self::$timeout)) {
            throw new TimeoutException();
        }

        if($bufferLength == 0) {
            $this->buffer->clear();
        } else {
            $this->buffer = ByteBuffer::allocate($bufferLength);
        }

        $data = $this->socket->recv($this->buffer->remaining());
        $this->buffer->put($data);
        $bytesRead = $this->buffer->position();
        $this->buffer->rewind();
        $this->buffer->limit($bytesRead);

        return $bytesRead;
    }

    /**
     * Sends the given packet to the server
     *
     * This converts the packet into a byte stream first before writing it to
     * the socket.
     *
     * @param SteamPacket $dataPacket The packet to send to the server
     * @see SteamPacket::__toString()
     */
    public function send(SteamPacket $dataPacket) {
        trigger_error("Sending packet of type \"" . get_class($dataPacket) . "\"...");

        $this->socket->send($dataPacket->__toString());
    }
}
