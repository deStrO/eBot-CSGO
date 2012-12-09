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
 * This exception class indicates a problem when parsing packet data from the
 * responses received from a game or master server
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage exceptions
 */
class PacketFormatException extends SteamCondenserException {

    /**
     * Creates a new <var>PacketFormatException</var> instance
     *
     * @param string $message The message to attach to the exception
     */
    public function __construct($message = "The packet data received doesn't match the packet format.") {
        parent::__construct($message);
    }

}
