<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/SteamPacket.php';

/**
 * This abstract class implements a method to generate raw packet data used by
 * request packets which send a challenge number
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 */
abstract class RequestPacketWithChallenge extends SteamPacket {

    /**
     * Returns the raw data representing this packet
     *
     * @return string A string containing the raw data of this request packet
     */
    public function __toString() {
        return pack('cccccV', 0xFF, 0xFF, 0xFF, 0xFF, $this->headerData, $this->contentData->_array());
    }
}
