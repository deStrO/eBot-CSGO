<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'ByteBuffer.php';

/**
 * This module implements the basic functionality used by most of the packets
 * used in communication with master, Source or GoldSrc servers.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @see        SteamPacketFactory
 */
abstract class SteamPacket {

    const S2A_INFO_DETAILED_HEADER = 0x6D;
    const A2S_INFO_HEADER = 0x54;
    const S2A_INFO2_HEADER = 0x49;
    const A2S_PLAYER_HEADER = 0x55;
    const S2A_PLAYER_HEADER = 0x44;
    const A2S_RULES_HEADER = 0x56;
    const S2A_RULES_HEADER = 0x45;
    const A2S_SERVERQUERY_GETCHALLENGE_HEADER = 0x57;
    const S2C_CHALLENGE_HEADER = 0x41;
    const A2M_GET_SERVERS_BATCH2_HEADER = 0x31;
    const C2M_CHECKMD5_HEADER = 0x4D;
    const M2A_SERVER_BATCH_HEADER = 0x66;
    const M2C_ISVALIDMD5_HEADER = 0x4E;
    const M2S_REQUESTRESTART_HEADER = 0x4F;
    const RCON_GOLDSRC_CHALLENGE_HEADER = 0x63;
    const RCON_GOLDSRC_NO_CHALLENGE_HEADER = 0x39;
    const RCON_GOLDSRC_RESPONSE_HEADER = 0x6C;
    const S2A_LOGSTRING_HEADER = 0x52;
    const S2M_HEARTBEAT2_HEADER = 0x30;

    /**
     * @var string This variable stores the content of the packet
     */
    protected $contentData;

    /**
     * @var int This byte stores the type of the packet
     */
    protected $headerData;

    /**
     * Creates a new packet object based on the given data
     *
     * @param int $headerData The packet header
     * @param string $contentData The raw data of the packet
     */
    public function __construct($headerData, $contentData = null) {
        $this->headerData = $headerData;
        $this->contentData = ByteBuffer::wrap($contentData);
    }

    /**
     * @return string The data payload of the packet
     */
    public function getData() {
        return $this->contentData;
    }

    /**
     * @return int The header of the packet
     */
    public function getHeader() {
        return $this->headerData;
    }

    /**
     * Returns the raw data representing this packet
     *
     * @return string A string containing the raw data of this request packet
     */
    public function __toString() {
        $packetData = pack('cccc', 0xFF, 0xFF, 0xFF, 0xFF);
        $packetData .= pack('ca*', $this->headerData, $this->contentData->_array());

        return $packetData;
    }
}
