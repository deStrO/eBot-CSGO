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
 * This packet class class represents a A2S_INFO request send to a game server
 *
 * It will cause the server to send some basic information about itself, e.g.
 * the running game, map and the number of players.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @see        GameServer::updateServerInfo()
 */
class A2S_INFO_Packet extends SteamPacket {

    /**
     * Creates a new A2S_INFO request object
     */
    public function __construct() {
        parent::__construct(SteamPacket::A2S_INFO_HEADER, "Source Engine Query\0");
    }
}
