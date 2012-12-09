<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/rcon/RCONPacket.php';

/**
 * This packet class represents a SERVERDATA_EXECCOMMAND packet sent to a
 * Source server
 *
 * It is used to request a command execution on the server.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage rcon-packets
 * @see SourceServer::rconExec()
 */
class RCONExecRequest extends RCONPacket {

    /**
     * Creates a RCON command execution request for the given request ID and
     * command
     *
     * @param int $requestId The request ID of the RCON connection
     * @param string $command The command to execute on the server
     */
    public function __construct($requestId, $command) {
        parent::__construct($requestId, RCONPacket::SERVERDATA_EXECCOMMAND, $command);
    }
}
