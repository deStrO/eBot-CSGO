<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/rcon/RCONPacket.php';

/**
 * This packet class represents a special SERVERDATA_RESPONSE_VALUE packet
 * which is sent to the server
 *
 * It is used to determine the end of a RCON response from Source servers.
 * Packets of this type are sent after the actual RCON command and the empty
 * response packet from the server will indicate the end of the response.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage rcon-packets
 * @see SourceServer::rconExec()
 */
class RCONTerminator extends RCONPacket {

    /**
     * Creates a new RCON terminator packet instance for the given request ID
     *
     * @param int $requestId The request ID for the current RCON communication
     */
    public function __construct($requestId) {
        parent::__construct($requestId, RCONPacket::SERVERDATA_RESPONSE_VALUE);
    }

}
