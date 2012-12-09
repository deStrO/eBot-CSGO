<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'exceptions/WebApiException.php';

/**
 * This adds support for Steam Web API to classes needing this functionality.
 * The Web API requires you to register a domain with your Steam account to
 * acquire an API key. See http://steamcommunity.com/dev for further details.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class WebApi {

    /**
     * @var string
     */
    private static $apiKey = null;

    /**
     * @var WebApi
     */
    protected static $instance = null;

    /**
     * Returns the Steam Web API key
     *
     * @return string The Steam Web API key
     */
    public static function getApiKey() {
        return self::$apiKey;
    }

    /**
     * Fetches JSON data from Steam Web API using the specified interface,
     * method and version. Additional parameters are supplied via HTTP GET.
     *
     * @param string $interface The Web API interface to call, e.g. ISteamUser
     * @param string $method The Web API method to call, e.g.
     *        GetPlayerSummaries
     * @param int $version The API method version to use
     * @param array $params Additional parameters to supply via HTTP GET
     * @return string Data is returned as a JSON-encoded string.
     */
    public static function getJSON($interface, $method, $version = 1, $params = null) {
        return self::instance()->_getJSON($interface, $method, $version, $params);
    }

    /**
     * Fetches JSON data from Steam Web API using the specified interface,
     * method and version. Additional parameters are supplied via HTTP GET.
     *
     * @param string $interface The Web API interface to call, e.g. ISteamUser
     * @param string $method The Web API method to call, e.g.
     *        GetPlayerSummaries
     * @param int $version The API method version to use
     * @param array $params Additional parameters to supply via HTTP GET
     * @return stdClass Data is returned as a json_decoded object
     */
    public static function getJSONData($interface, $method, $version = 1, $params = null) {
        return self::instance()->_getJSONData($interface, $method, $version, $params);
    }

    /**
     * Fetches data from Steam Web API using the specified interface, method
     * and version. Additional parameters are supplied via HTTP GET. Data is
     * returned as a String in the given format.
     *
     * @param string $format The format to load from the API ('json', 'vdf', or
     *        'xml')
     * @param string $interface The Web API interface to call, e.g. ISteamUser
     * @param string $method The Web API method to call, e.g.
     *        GetPlayerSummaries
     * @param int $version The API method version to use
     * @param array $params Additional parameters to supply via HTTP GET
     * @return string Data is returned as a String in the given format (which
     *                may be 'json', 'vdf' or 'xml').
     */
    public static function load($format, $interface, $method, $version = 1, $params = null) {
        return self::instance()->_load($format, $interface, $method, $version, $params);
    }

    /**
     * Returns a singleton instance of an internal <var>WebApi</var> object
     *
     * @return WebApi The internal <var>WebApi</var> instance
     */
    private static function instance() {
        if(self::$instance == null) {
            self::$instance = new WebApi();
        }

        return self::$instance;
    }

    /**
     * Sets the Steam Web API key
     *
     * @param string $apiKey The 128bit API key that has to be requested from
     *                      http://steamcommunity.com/dev
     * @throws WebApiException if the given API key is not a valid 128bit
     *         hexadecimal string
     */
    public static function setApiKey($apiKey) {
        if($apiKey != null && !preg_match('/^[0-9A-F]{32}$/', $apiKey)) {
            throw new WebApiException(WebApiException::INVALID_KEY);
        }

        self::$apiKey = $apiKey;
    }

    /**
     * Private constructor to prevent direct usage of <var>WebApi</var>
     * instances
     */
    private function __construct() {}

    /**
     * Fetches JSON data from Steam Web API using the specified interface,
     * method and version. Additional parameters are supplied via HTTP GET.
     *
     * @param string $interface The Web API interface to call, e.g. ISteamUser
     * @param string $method The Web API method to call, e.g.
     *        GetPlayerSummaries
     * @param int $version The API method version to use
     * @param array $params Additional parameters to supply via HTTP GET
     * @return string Data is returned as a JSON-encoded string.
     */
    protected function _getJSON($interface, $method, $version = 1, $params = null) {
        return $this->load('json', $interface, $method, $version, $params);
    }

    /**
     * Fetches JSON data from Steam Web API using the specified interface,
     * method and version. Additional parameters are supplied via HTTP GET.
     *
     * @param string $interface The Web API interface to call, e.g. ISteamUser
     * @param string $method The Web API method to call, e.g.
     *        GetPlayerSummaries
     * @param int $version The API method version to use
     * @param array $params Additional parameters to supply via HTTP GET
     * @return stdClass Data is returned as a json_decoded object
     * @throws WebApiException In case of any request failure
     */
    protected function _getJSONData($interface, $method, $version = 1, $params = null) {
        $data = $this->getJSON($interface, $method, $version, $params);
        $result = json_decode($data)->result;

        if($result->status != 1) {
            throw new WebApiException(WebApiException::STATUS_BAD, $result->status, $result->statusDetail);
        }

        return $result;
    }

    /**
     * Fetches data from Steam Web API using the specified interface, method
     * and version. Additional parameters are supplied via HTTP GET. Data is
     * returned as a String in the given format.
     *
     * @param string $format The format to load from the API ('json', 'vdf', or
     *        'xml')
     * @param string $interface The Web API interface to call, e.g. ISteamUser
     * @param string $method The Web API method to call, e.g.
     *        GetPlayerSummaries
     * @param int $version The API method version to use
     * @param array $params Additional parameters to supply via HTTP GET
     * @return string Data is returned as a String in the given format (which
     *                may be 'json', 'vdf' or 'xml').
     */
    protected function _load($format, $interface, $method, $version = 1, $params = null) {
        $version = str_pad($version, 4, '0', STR_PAD_LEFT);
        $url = "http://api.steampowered.com/$interface/$method/v$version/";

        $params['format'] = $format;
        $params['key']    = self::$apiKey;

        if($params != null && !empty($params)) {
            $url .= '?';
            $url_params = array();
            foreach($params as $k => $v) {
                $url_params[] = "$k=$v";
            }
            $url .= join('&', $url_params);
        }

        return $this->request($url);
    }

    /**
     * Fetches data from Steam Web API using the specified URL
     *
     * @param string $url The URL to load
     * @return string The data returned by the Web API
     * @throws WebApiException if the request failed
     */
    protected function request($url) {
        $data = @file_get_contents($url);

        if(empty($data)) {
            preg_match('/^.* (\d{3}) (.*)$/', $http_response_header[0], $http_status);

            if($http_status[1] == 401) {
                throw new WebApiException(WebApiException::UNAUTHORIZED);
            }

            throw new WebApiException(WebApiException::HTTP_ERROR, $http_status[1], $http_status[2]);
        }

        return $data;
    }

}
