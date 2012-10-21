<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * This class is subclassed by all classes implementing server functionality
 *
 * It provides basic name resolution features and the ability to rotate
 * between different IP addresses belonging to a single DNS name.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage servers
 */
abstract class Server {

    /**
     * @var array The currently selected IP address of this server
     */
    protected $ipAddress;

    /**
     * @var array The IP addresses of this server
     */
    protected $ipAddresses;

    /**
     * @var int The index of the currently selected IP address
     */
    protected $ipIndex;

    /**
     * @var int The port of this server
     */
    protected $port;

    /**
     * Creates a new server instance with the given address and port
     *
     * @param string $address Either an IP address, a DNS name or one of them
     *        combined with the port number. If a port number is given, e.g.
     *        'server.example.com:27016' it will override the second argument.
     * @param int $port The port the server is listening on
     * @see initSocket()
     * @throws SteamCondenserException if an host name cannot be resolved
     */
    public function __construct($address, $port = null) {
        $address = strval($address);

        if(strpos($address, ':') !== false) {
            $address = explode(':', $address, 2);
            $port    = $address[1];
            $address = $address[0];
        }
        $this->ipAddresses = array();
        $this->ipIndex     = 0;
        $this->port        = intval($port);

        $addresses = gethostbynamel($address);
        if(empty($addresses)) {
            throw new SteamCondenserException("Cannot resolve $address");
        }

        foreach($addresses as $address) {
            $this->ipAddresses[] = $address;
        }

        $this->ipAddress = $this->ipAddresses[0];


        $this->initSocket();
    }

    /**
     * Rotate this server's IP address to the next one in the IP list
     *
     * If this method returns <var>true</var>, it indicates that all IP
     * addresses have been used, hinting at the server(s) being unreachable. An
     * appropriate action should be taken to inform the user.
     *
     * Servers with only one IP address will always cause this method to return
     * <var>true</var> and the sockets will not be reinitialized.
     *
     * @return bool <var>true</var>, if the IP list reached its end. If the
     *         list contains only one IP address, this method will instantly
     *         return <var>true</var>
     * @see initSocket()
     */
    public function rotateIp() {
        if(sizeof($this->ipAddresses) == 1) {
            return true;
        }

        $this->ipIndex   = ($this->ipIndex + 1) % sizeof($this->ipAddresses);
        $this->ipAddress = $this->ipAddresses[$this->ipIndex];

        $this->initSocket();

        return $this->ipIndex == 0;
    }

    /**
     * Initializes the socket(s) to communicate with the server
     *
     * Must be implemented in subclasses to prepare sockets for server
     * communication
     */
    protected abstract function initSocket();

}
