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
 * This packet class represents a M2S_ISVALIDMD5 response replied by a master
 * server
 *
 * It is used to provide a challenge number to a game server
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @see        MasterServer::getChallenge()
 */
class M2C_ISVALIDMD5_Packet extends SteamPacket {

    /**
     * @var int
     */
    private $challenge;

    /**
     * Creates a new M2S_ISVALIDMD5 response object based on the given data
     *
     * @param string $data The raw packet data replied from the server
     */
    public function __construct($data) {
        parent::__construct(SteamPacket::M2C_ISVALIDMD5_HEADER, $data);

        $this->contentData->getByte();
        $this->challenge = $this->contentData->getUnsignedLong();
    }

    /**
     * Returns the challenge number to use for master server communication
     *
     * @return int The challenge number
     */
    public function getChallenge() {
        return $this->challenge;
    }
}
