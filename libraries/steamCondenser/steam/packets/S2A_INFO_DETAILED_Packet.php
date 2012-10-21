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
 * This class represents a S2A_INFO_DETAILED response packet sent by a GoldSrc
 * server
 *
 * @author     Sebastian Staudt
 * @deprecated Only outdated GoldSrc servers (before 10/24/2008) use this
 *             format. Newer ones use the same format as Source servers now
 *             (see {@link S2A_INFO2_Packet}).
 * @package    steam-condenser
 * @subpackage packets
 * @see GameServer::updateServerInfo()
 */
class S2A_INFO_DETAILED_Packet extends S2A_INFO_BasePacket {

    /**
     * Creates a new S2A_INFO_DETAILED response object based on the given data
     *
     * @param string $data The raw packet data replied from the server
     */
    public function __construct($data) {
        parent::__construct(SteamPacket::S2A_INFO_DETAILED_HEADER, $data);

        $this->serverIp = $this->contentData->getString();
        $this->serverName = $this->contentData->getString();
        $this->mapName = $this->contentData->getString();
        $this->gameDir = $this->contentData->getString();
        $this->gameDescription = $this->contentData->getString();
        $this->numberOfPlayers = $this->contentData->getByte();
        $this->maxPlayers = $this->contentData->getByte();
        $this->networkVersion = $this->contentData->getByte();
        $this->dedicated = $this->contentData->getByte();
        $this->operatingSystem = $this->contentData->getByte();
        $this->passwordProtected = $this->contentData->getByte() == 1;
        $this->isMod = $this->contentData->getByte() == 1;

        if($this->isMod) {
            $this->modInfo['urlInfo'] = $this->contentData->getString();
            $this->modInfo['urlDl'] = $this->contentData->getString();
            $this->contentData->getByte();
            if($this->contentData->remaining() == 12) {
                $this->modInfo['modVersion'] = $this->contentData->getLong();
                $this->modInfo['modSize'] = $this->contentData->getLong();
                $this->modInfo['svOnly'] = ($this->contentData->getByte() == 1);
                $this->modInfo['clDll'] = ($this->contentData->getByte() == 1);
                $this->secure = $this->contentData->getByte() == 1;
                $this->numberOfBots = $this->contentData->getByte();
            }
        } else {
            $this->secure = $this->contentData->getByte() == 1;
            $this->numberOfBots = $this->contentData->getByte();
        }
    }
}
