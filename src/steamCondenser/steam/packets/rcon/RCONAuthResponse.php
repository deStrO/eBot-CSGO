<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/rcon/RCONPacket.php';

/**
 * This packet class represents a SERVERDATA_AUTH_RESPONSE packet sent by a
 * Source server
 *
 * It is used to indicate the success or failure of an authentication attempt
 * of a client for RCON communication.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage rcon-packets
 * @see SourceServer::rconAuth()
 */
class RCONAuthResponse extends RCONPacket {

    /**
     * Creates a RCON authentication response for the given request ID
     *
     * The request ID of the packet will match the client's request if
     * authentication was successful
     *
     * @param int $requestId The request ID of the RCON connection
     */
    public function __construct($requestId) {
        parent::__construct($requestId, RCONPacket::SERVERDATA_AUTH_RESPONSE);
    }
}
