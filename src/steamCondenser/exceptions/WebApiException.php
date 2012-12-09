<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'exceptions/SteamCondenserException.php';

/**
 * This exception is raised when a Steam Web API request or a related action
 * fails. This can have various reasons like an invalid Web API key or a broken
 * request.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage exceptions
 * @see WebApi
 */
class WebApiException extends SteamCondenserException {

    const HTTP_ERROR   = 0;

    const INVALID_KEY  = 1;

    const STATUS_BAD   = 2;

    const UNAUTHORIZED = 3;

    /**
     * Creates a new WebApiException with an error message according to the
     * given <var>$cause</var>. If this cause is <var>STATUS_BAD</var> (which
     * will origin from the Web API itself) or <var>HTTP_ERROR</var> the
     * details about this failed request will be taken from
     * <var>$statusCode</var> and <var>$statusMessage</var>.
     *
     * @param int $cause An integer indicating the problem which caused this
     *        exception:
     *
     *        <ul>
     *        <li><var>HTTP_ERROR</var>: An error during the HTTP request
     *            itself will result in an exception with this reason.</li>
     *        <li><var>INVALID_KEY</var>: This occurs when trying to set a Web
     *            API key that isn't valid, i.e. a 128 bit integer in a
     *            hexadecimal string.
     *        <li><var>STATUS_BAD</var>: This is caused by a successful request
     *            that fails for some Web API internal reason (e.g. an invalid
     *            argument). Details about this failed request will be taken
     *            from <var>$statusCode</var> and <var>$statusMessage</var>.
     *        <li><var>UNAUTHORIZED</var>: This happens when a Steam Web API
     *            request is rejected as unauthorized. This most likely means
     *            that you did not specify a valid Web API key using
     *            {@link WebApi::setApiKey()}. A Web API key can be obtained
     *            from http://steamcommunity.com/dev/apikey.
     *        </ul>
     *
     *        Other undefined reasons will cause a generic error message.
     * @param int $statusCode The HTTP status code returned by the Web API
     * @param string $statusMessage The status message returned in the response
     */
    public function __construct($cause, $statusCode = null, $statusMessage = '') {
        switch($cause) {
            case self::HTTP_ERROR:
                $message = "The Web API request has failed due to an HTTP error: $statusMessage (status code: $statusCode).";
                break;
            case self::INVALID_KEY:
                $message = 'This is not a valid Steam Web API key.';
                break;
            case self::STATUS_BAD:
                $message = "The Web API request failed with the following error: $statusMessage (status code: $statusCode).";
                break;
            case self::UNAUTHORIZED:
                $message = 'Your Web API request has been rejected. You most likely did not specify a valid Web API key.';
                break;
            default:
                $message = 'An unexpected error occured while executing a Web API request.';
        }

        parent::__construct($message);
    }

}
