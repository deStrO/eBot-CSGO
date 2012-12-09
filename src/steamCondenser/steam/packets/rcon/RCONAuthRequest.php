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
 * This packet class represents a SERVERDATA_AUTH request sent to a Source
 * server
 *
 * It is used to authenticate the client for RCON communication.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage rcon-packets
 * @see SourceServer::rconAuth()
 */
class RCONAuthRequest extends RCONPacket {

    /**
     * Creates a RCON authentication request for the given request ID and RCON
     * password
     *
     * @param int $requestId The request ID of the RCON connection
     * @param string $password The RCON password of the server
     */
    public function __construct($requestId, $password) {
        parent::__construct($requestId, RCONPacket::SERVERDATA_AUTH, $password);
    }
}
