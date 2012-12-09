<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'ByteBuffer.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/SteamPacketFactory.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/rcon/RCONAuthResponse.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/rcon/RCONExecResponse.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/rcon/RCONPacket.php';

/**
 * This module provides functionality to handle raw packet data for Source RCON
 *
 * It's is used to transform data bytes into packet objects for RCON
 * communication with Source servers.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage rcon-packets
 * @see RCONPacket
 */
abstract class RCONPacketFactory
{

    /**
     * Creates a new packet object based on the header byte of the given raw
     * data
     *
     * @param string $rawData The raw data of the packet
     * @return RCONPacket The packet object generated from the packet data
     * @throws PacketFormatException if the packet header is not recognized
     */
    public static function getPacketFromData($rawData) {
        $byteBuffer = new ByteBuffer($rawData);

        $requestId = $byteBuffer->getLong();
        $header = $byteBuffer->getLong();
        $data = $byteBuffer->getString();

        switch($header) {
            case RCONPacket::SERVERDATA_AUTH_RESPONSE:
                return new RCONAuthResponse($requestId);
            case RCONPacket::SERVERDATA_RESPONSE_VALUE:
                return new RCONExecResponse($requestId, $data);
            default:
                throw new PacketFormatException('Unknown packet with header ' . dechex($header) . ' received.');
        }
    }
}
