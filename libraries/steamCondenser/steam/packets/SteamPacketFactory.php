<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'exceptions/PacketFormatException.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/rcon/RCONGoldSrcResponse.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/S2A_INFO_DETAILED_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/A2S_INFO_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/S2A_INFO2_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/A2S_PLAYER_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/S2A_PLAYER_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/A2S_RULES_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/S2A_RULES_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/A2S_SERVERQUERY_GETCHALLENGE_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/S2C_CHALLENGE_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/A2M_GET_SERVERS_BATCH2_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/M2A_SERVER_BATCH_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/M2C_ISVALIDMD5_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/M2S_REQUESTRESTART_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/S2A_LOGSTRING_Packet.php';

/**
 * This module provides functionality to handle raw packet data, including data
 * split into several UDP / TCP packets and BZIP2 compressed data. It's the
 * main utility to transform data bytes into packet objects.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @see        SteamPacket
 */
abstract class SteamPacketFactory {

    /**
     * Creates a new packet object based on the header byte of the given raw
     * data
     *
     * @param string $rawData The raw data of the packet
     * @throws PacketFormatException if the packet header is not recognized
     * @return SteamPacket The packet object generated from the packet data
     */
    public static function getPacketFromData($rawData) {
        $header = ord($rawData[0]);
        $data = substr($rawData, 1);

        switch($header) {
            case SteamPacket::S2A_INFO_DETAILED_HEADER:
                return new S2A_INFO_DETAILED_Packet($data);

            case SteamPacket::A2S_INFO_HEADER:
                return new A2S_INFO_Packet();

            case SteamPacket::S2A_INFO2_HEADER:
                return new S2A_INFO2_Packet($data);

            case SteamPacket::A2S_PLAYER_HEADER:
                return new A2S_PLAYER_Packet();

            case SteamPacket::S2A_PLAYER_HEADER:
                return new S2A_PLAYER_Packet($data);

            case SteamPacket::A2S_RULES_HEADER:
                return new A2S_RULES_Packet();

            case SteamPacket::S2A_RULES_HEADER:
                return new S2A_RULES_Packet($data);

            case SteamPacket::A2S_SERVERQUERY_GETCHALLENGE_HEADER:
                return new A2S_SERVERQUERY_GETCHALLENGE_Packet();

            case SteamPacket::S2C_CHALLENGE_HEADER:
                return new S2C_CHALLENGE_Packet($data);

            case SteamPacket::A2M_GET_SERVERS_BATCH2_HEADER:
                return new A2M_GET_SERVERS_BATCH2_Packet($data);

            case SteamPacket::M2A_SERVER_BATCH_HEADER:
                return new M2A_SERVER_BATCH_Packet($data);

            case SteamPacket::M2C_ISVALIDMD5_HEADER:
                return new M2C_ISVALIDMD5_Packet($data);

            case SteamPacket::M2S_REQUESTRESTART_HEADER:
                return new M2S_REQUESTRESTART_Packet($data);

            case SteamPacket::RCON_GOLDSRC_CHALLENGE_HEADER:
            case SteamPacket::RCON_GOLDSRC_NO_CHALLENGE_HEADER:
            case SteamPacket::RCON_GOLDSRC_RESPONSE_HEADER:
                return new RCONGoldSrcResponse($data);

            case SteamPacket::S2A_LOGSTRING_HEADER:
                return new S2A_LOGSTRING_Packet($data);

            default:
                throw new PacketFormatException('Unknown packet with header 0x' . dechex($header) . ' received.');
        }
    }

    /**
     * Reassembles the data of a split and/or compressed packet into a single
     * packet object
     *
     * @param array $splitPackets An array of packet data
     * @param bool $isCompressed whether the data of this packet is compressed
     * @param int $packetChecksum The CRC32 checksum of the decompressed
     *        packet data
     * @throws PacketFormatException if the calculated CRC32 checksum does not
     *         match the expected value
     * @return SteamPacket The reassembled packet
     * @see packetFromData()
     */
    public static function reassemblePacket($splitPackets, $isCompressed = false, $packetChecksum = 0) {
        $packetData = '';

        foreach($splitPackets as $splitPacket) {
            $packetData .= $splitPacket;
        }

        if($isCompressed) {
            $packetData = bzdecompress($packetData);

            if(crc32($packetData) != $packetChecksum) {
                throw new PacketFormatException('CRC32 checksum mismatch of uncompressed packet data.');
            }
        }

        $packetData = substr($packetData, 4);

        return self::getPacketFromData($packetData);
    }
}
