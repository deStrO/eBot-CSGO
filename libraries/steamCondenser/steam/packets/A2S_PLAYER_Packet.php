<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/RequestPacketWithChallenge.php';

/**
 * This packet class represents a A2S_PLAYER request send to a game server
 *
 * It is used to request the list of players currently playing on the server.
 *
 * This packet type requires the client to challenge the server in advance,
 * which is done automatically if required.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @see        GameServer::updatePlayerInfo()
 */
class A2S_PLAYER_Packet extends RequestPacketWithChallenge {

    /**
     * Creates a new A2S_PLAYER request object including the challenge number
     *
     * @param int $challengeNumber The challenge number received from the
     *        server
     */
    public function __construct($challengeNumber = 0xFFFFFFFF) {
        parent::__construct(SteamPacket::A2S_PLAYER_HEADER, $challengeNumber);
    }
}
