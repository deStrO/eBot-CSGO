<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * This exception class indicates that an operation could not be finished
 * within a reasonable amount of time
 *
 * This usually indicates that a server could not be contacted because of
 * network problems.
 *
 * <b>Note:</b> {@link SteamSocket::setTimeout()} allows to set a custom
 * timeout for socket operations
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage exceptions
 */
class TimeoutException extends Exception {

    /**
     * Creates a new <var>TimeoutException</var> instance
     */
    public function __construct() {
        parent::__construct('The operation timed out.');
    }

}
