<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/S2A_INFO_BasePacket.php';

/**
 * This class represents a S2A_INFO_DETAILED response packet sent by a Source
 * or GoldSrc server
 *
 * Out-of-date (before 10/24/2008) GoldSrc servers use an older format (see
 * {@link S2A_INFO_DETAILED_Packet}).
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @see        GameServer::updateServerInfo()
 */
class S2A_INFO2_Packet extends S2A_INFO_BasePacket {

    /**
     * Creates a new S2A_INFO2 response object based on the given data
     *
     * @param string $data The raw packet data replied from the server
     */
    public function __construct($data) {
        parent::__construct(SteamPacket::S2A_INFO2_HEADER, $data);

        $this->networkVersion = $this->contentData->getByte();
        $this->serverName = $this->contentData->getString();
        $this->mapName = $this->contentData->getString();
        $this->gameDir = $this->contentData->getString();
        $this->gameDesc = $this->contentData->getString();
        $this->appId = $this->contentData->getShort();
        $this->numberOfPlayers = $this->contentData->getByte();
        $this->maxPlayers = $this->contentData->getByte();
        $this->botNumber = $this->contentData->getByte();
        $this->dedicated = chr($this->contentData->getByte());
        $this->operatingSystem = chr($this->contentData->getByte());
        $this->passwordProtected = $this->contentData->getByte() == 1;
        $this->secureServer = $this->contentData->getByte() == 1;
        $this->gameVersion = $this->contentData->getString();

        if($this->contentData->remaining() > 0) {
            $extraDataFlag = $this->contentData->getByte();

            if($extraDataFlag & 0x80) {
                $this->serverPort = $this->contentData->getShort();
            }

            if($extraDataFlag & 0x10) {
                $this->serverId = $this->contentData->getUnsignedLong() | ($this->contentData->getUnsignedLong() << 32);
            }

            if($extraDataFlag & 0x40) {
                $this->tvPort = $this->contentData->getShort();
                $this->tvName = $this->contentData->getString();
            }

            if($extraDataFlag & 0x20) {
                $this->serverTags = $this->contentData->getString();
            }
        }
    }
}
