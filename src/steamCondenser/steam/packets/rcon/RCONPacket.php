<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/SteamPacket.php';

/**
 * This module is included by all classes representing a packet used by
 * Source's RCON protocol
 *
 * It provides a basic implementation for initializing and serializing such a
 * packet.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage rcon-packets
 * @see RCONPacketFactory
 */
abstract class RCONPacket extends SteamPacket {

    /**
     * @var int Header for authentication requests
     */
    const SERVERDATA_AUTH = 3;

    /**
     * @var int Header for replies to authentication attempts
     */
    const SERVERDATA_AUTH_RESPONSE = 2;

    /**
     * @var int Header for command execution requests
     */
    const SERVERDATA_EXECCOMMAND = 2;

    /**
     * @var int Header for packets with the output of a command execution
     */
    const SERVERDATA_RESPONSE_VALUE = 0;

    /**
     * @var int The request ID used to identify the RCON communication
     */
    private $requestId;

    /**
     * Creates a new RCON packet object with the given request ID, type and
     * content data
     *
     * @param int $requestId The request ID for the current RCON communication
     * @param int $rconHeader The header for the packet type
     * @param string $rconData The raw packet data
     */
    public function __construct($requestId, $rconHeader, $rconData = null) {
        parent::__construct($rconHeader, "$rconData\0\0");

        $this->requestId = $requestId;
    }

    /**
     * Returns the request ID used to identify the RCON communication
     *
     * @return int The request ID used to identify the RCON communication
     */
    public function getRequestId() {
        return $this->requestId;
    }

    /**
     * Returns the raw data representing this packet
     *
     * @return string A string containing the raw data of this RCON packet
     */
    public function __toString() {
        $contentData = $this->contentData->_array();
        return pack('V3a*', strlen($contentData) + 8, $this->requestId, $this->headerData, $contentData);
    }
}
