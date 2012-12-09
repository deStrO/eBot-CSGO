<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'exceptions/SteamCondenserException.php';

/**
 * This exception class indicates that you have not authenticated yet with the
 * game server you're trying to send commands via RCON
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage exceptions
 * @see GameServer::rconAuth()
 * @see GameServer::rconExec()
 */
class RCONNoAuthException extends SteamCondenserException {

    /**
     * Creates a new <var>RCONNoAuthException</var> instance
     */
    public function __construct() {
        parent::__construct('Not authenticated yet.');
    }
}
