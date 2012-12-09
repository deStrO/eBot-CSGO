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
 * This packet class represents a SERVERDATA_RESPONSE_VALUE packet sent by a
 * Source server
 *
 * It is used to transport the output of a command from the server to the
 * client which requested the command execution.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage rcon-packets
 * @see SourceServer::rconExec()
 */
class RCONExecResponse extends RCONPacket {

    /**
     * Creates a RCON command response for the given request ID and command
     * output
     *
     * @param int $requestId The request ID of the RCON connection
     * @param string $commandResponse The output of the command executed on the
     *        server
     */
    public function __construct($requestId, $commandResponse) {
        parent::__construct($requestId, RCONPacket::SERVERDATA_RESPONSE_VALUE, $commandResponse);
    }

    /**
     * Returns the output of the command execution
     *
     * @return string The output of the command
     */
    public function getResponse() {
        $response = $this->contentData->_array();
        return substr($response, 0, strlen($response) - 2);
    }
}
