<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/SteamPacket.php';

/**
 * This packet class represents a C2M_CHECKMD5 request sent to a master server
 *
 * It is used to initialize (challenge) master server communication.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @deprecated
 * @see        MasterServer::getChallenge()
 */
class C2M_CHECKMD5_Packet extends SteamPacket {

    /**
     * Creates a new C2M_CHECKMD% request object
     */
    public function __construct() {
        parent::__construct(SteamPacket::C2M_CHECKMD5_HEADER);
    }

    /**
     * Returns the raw data representing this packet
     *
     * @return string A string containing the raw data of this request packet
     */
    public function __toString() {
        return chr($this->headerData) . "\xFF";
    }

}
