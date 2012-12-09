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
 * This packet class represents a RCON request packet sent to a GoldSrc server
 *
 * It is used to request a command execution on the server.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage rcon-packets
 * @see GoldSrcServer::rconExec()
 */
class RCONGoldSrcRequest extends SteamPacket {

    /**
     * Creates a request for the given request string
     *
     * The request string has the form <var>rcon {challenge number} {RCON
     * password} {command}</var>.
     *
     * @param string $request The request string to send to the server
     */
    public function __construct($request) {
        parent::__construct(0x00, $request);
    }

    /**
     * Returns the raw data representing this packet
     *
     * @return string A string containing the raw data of this request packet
     */
    public function __toString() {
        return pack('Va*', 0xFFFFFFFF, $this->contentData->_array());
    }
}
