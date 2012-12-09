<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/SteamPacket.php';

/**
 * This packet class represent a M2S_REQUESTRESTART response replied from a
 * master server
 *
 * It is used to request a game server restart, e.g. when the server is
 * outdated.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage packets
 * @see MasterServer::sendHeartbeat
 */
class M2S_REQUESTRESTART_Packet extends SteamPacket {

    /**
     * @var int
     */
    private $challenge;

    /**
     * Creates a new M2S_REQUESTRESTART object based on the given data
     *
     * @param string $data The raw packet data replied from the server
     */
    public function __construct($data) {
        parent::__construct(SteamPacket::C2M_CHECKMD5_HEADER, $data);

        $this->challenge = $this->contentData->getUnsignedLong();
    }

    /**
     * Returns the challenge number used for master server communication
     *
     * @return int The challenge number
     */
    public function getChallenge() {
        return $this->challenge;
    }

}
