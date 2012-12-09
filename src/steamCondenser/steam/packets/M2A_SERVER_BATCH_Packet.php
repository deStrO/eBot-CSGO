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
 * This packet class represents a M2A_SERVER_BATCH response replied by a master
 * server
 *
 * It contains a list of IP addresses and ports of game servers matching the
 * requested criteria.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @see        MasterServer::getServers()
 */
class M2A_SERVER_BATCH_Packet extends SteamPacket {

    /**
     * @var array
     */
    private $serverArray;

    /**
     * Creates a new M2A_SERVER_BATCH response object based on the given data
     *
     * @param string $data The raw packet data replied from the server
     * @throws PacketFormatException if the packet data is not well formatted
     */
    public function __construct($data) {
        parent::__construct(SteamPacket::M2A_SERVER_BATCH_HEADER, $data);

        if($this->contentData->getByte() != 10) {
            throw new PacketFormatException('Master query response is missing additional 0x0A byte.');
        }

        do {
            $firstOctet = $this->contentData->getByte();
            $secondOctet = $this->contentData->getByte();
            $thirdOctet = $this->contentData->getByte();
            $fourthOctet = $this->contentData->getByte();
            $portNumber = $this->contentData->getShort();
            $portNumber = (($portNumber & 0xFF) << 8) + ($portNumber >> 8);

            $this->serverArray[] = "$firstOctet.$secondOctet.$thirdOctet.$fourthOctet:$portNumber";
        } while($this->contentData->remaining() > 0);
    }

   /**
    * Returns the list of servers returned from the server in this packet
    *
    * @return array An array of server addresses (i.e. IP addresses + port
    *         numbers)
    */
    public function getServers() {
        return $this->serverArray;
    }
}
