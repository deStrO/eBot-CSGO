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
 * This packet class represents a S2C_CHALLENGE response replied by a game
 * server
 *
 * It is used to provide a challenge number to a client requesting information
 * from the game server.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage packets
 * @see GameServer::updateChallengeNumber()
 */
class S2C_CHALLENGE_Packet extends SteamPacket {

    /**
     * Creates a new S2C_CHALLENGE response object based on the given data
     *
     * @param string $challengeNumber The raw packet data replied from the
     *        server
     */
    public function __construct($challengeNumber) {
        parent::__construct(SteamPacket::S2C_CHALLENGE_HEADER, $challengeNumber);
    }

    /**
     * Returns the challenge number received from the game server
     *
     * @return int The challenge number provided by the game server
     */
    public function getChallengeNumber() {
        return $this->contentData->rewind()->getLong();
    }
}
