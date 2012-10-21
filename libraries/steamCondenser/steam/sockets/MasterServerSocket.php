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
require_once STEAM_CONDENSER_PATH . 'steam/sockets/SteamSocket.php';

/**
 * This class represents a socket used to communicate with master servers
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage sockets
 */
class MasterServerSocket extends SteamSocket {

    /**
     * Reads a single packet from the socket
     *
     * @return SteamPacket The packet replied from the server
     * @throws PacketFormatException if the packet has the wrong format
     */
    public function getReply() {
        $this->receivePacket(1500);

        if($this->buffer->getLong() != -1) {
            throw new PacketFormatException("Master query response has wrong packet header.");
        }

        $packet = SteamPacketFactory::getPacketFromData($this->buffer->get());

        trigger_error("Received reply of type \"" . get_class($packet) . "\"");

        return $packet;
    }

}
