<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/SteamPacket.php';

/**
 * This class represents a S2A_LOGSTRING packet used to transfer log messages
 *
 * @package    steam-condenser
 * @subpackage packets
 * @author     Sebastian Staudt
 */
class S2A_LOGSTRING_Packet extends SteamPacket {

    /**
     * @var string The log message contained in this packet
     */
    private $message;

    /**
     * Creates a new S2A_LOGSTRING object based on the given data
     *
     * @param string $data The raw packet data sent by the server
     */
    public function __construct($data) {
        parent::__construct(SteamPacket::S2A_LOGSTRING_HEADER, $data);

        $this->contentData->getByte();
        $this->message = $this->contentData->getString();
    }

    /**
     * Returns the log message contained in this packet
     *
     * @return string The log message
     */
    public function getMessage() {
        return $this->message;
    }

}
