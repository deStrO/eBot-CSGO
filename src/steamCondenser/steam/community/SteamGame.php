<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011-2012, Sebastian Staudt
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/GameLeaderboard.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/GameStats.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/WebApi.php';

/**
 * This class represents a game available on Steam
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class SteamGame {

    /**
     * @var array
     */
    private static $games = array();

    /**
     * @var int
     */
    private $appId;

    /**
     * @var string
     */
    private $logoHash;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $shortName;

    /**
     * Creates a new or cached instance of the game specified by the given XML
     * data
     *
     * @param int $appId The application ID of the game
     * @param SimpleXMLElement $gameData The XML data of the game
     * @return SteamGame The game instance for the given data
     * @see __construct()
     */
    public static function create($appId, SimpleXMLElement $gameData) {
        if(array_key_exists($appId, self::$games)) {
            return self::$games[$appId];
        } else {
            return new SteamGame($appId, $gameData);
        }
    }

    /*
     * Checks if a game is up-to-date by reading information from a
     * <var>steam.inf</var> file and comparing it using the Web API
     *
     * @param string $path The file system path of the `steam.inf` file
     * @return bool <var>true</var> if the game is up-to-date
     */
    public static function checkSteamInf($path) {
        $steamInf = file_get_contents($path);
        preg_match('/^\s*appID=(\d+)\s*$/im', $steamInf, $appId);
        preg_match('/^\s*PatchVersion=([\d\.]+)\s*$/im', $steamInf, $version);

        if($appId == null || $version == null) {
            throw new SteamCondenserException("The steam.inf file at \"$path\" is invalid.");
        }

        $appId = (int) $appId[1];
        $version = (int) str_replace('.', '', $version[1]);

        return self::checkUpToDate($appId, $version);
    }

    /**
     * Returns whether the given version of the game with the given application
     * ID is up-to-date
     *
     * @param int $appId The application ID of the game to check
     * @param int $version The version to check against the Web API
     * @return boolean <var>true</var> if the given version is up-to-date
     * @throws SteamCondenserException if the Web API request fails
     */
    public static function checkUpToDate($appId, $version) {
        $params = array('appid' => $appId, 'version' => $version);
        $result = WebApi::getJSON('ISteamApps', 'UpToDateCheck', 1, $params);
        $result = json_decode($result)->response;
        if(!$result->success) {
            throw new SteamCondenserException($result->error);
        }
        return $result->up_to_date;
    }

    /**
     * Creates a new instance of a game with the given data and caches it
     *
     * @param int $appId The application ID of the game
     * @param SimpleXMLElement $gameData The XML data of the game
     */
    private function __construct($appId, SimpleXMLElement $gameData) {
        $this->appId = $appId;

        if(!empty($gameData->name)) {
            $logoUrl = (string) $gameData->logo;
            $this->name = (string) $gameData->name;
            if($gameData->globalStatsLink != null && !empty($gameData->globalStatsLink)) {
                preg_match('#http://steamcommunity.com/stats/([^?/]+)/achievements/#', (string) $gameData->globalStatsLink, $shortName);
                $this->shortName = strtolower($shortName[1]);
            } else {
                $this->shortName = null;
            }
        } else {
            $this->iconUrl = (string) $gameData->gameIcon;
            $logoUrl = (string) $gameData->gameLogo;
            $this->name = (string) $gameData->gameName;
            $this->shortName = strtolower((string) $gameData->gameFriendlyName);
        }

        preg_match("#/$appId/([0-9a-f]+).jpg#", $logoUrl, $logoHash);
        if (!empty($logoHash)) {
            $this->logoHash = strtolower($logoHash[1]);
        }

        self::$games[$appId] = $this;
    }

    /**
     * Returns the Steam application ID of this game
     *
     * @return int The Steam application ID of this game
     */
    public function getAppId() {
        return $this->appId;
    }

    /**
     * Returns a unique identifier for this game
     *
     * This is either the numeric application ID or the unique short name
     *
     * @return mixed The application ID or short name of the game
     */
    public function getId() {
        if((string) $this->appId == $this->shortName) {
            return $this->appId;
        } else {
            return $this->shortName;
        }
    }

    /**
     * Returns the leaderboard for this game and the given leaderboard ID or
     * name
     *
     * @param mixed $id The ID or name of the leaderboard to return
     * @return GameLeaderboard The matching leaderboard if available
     */
    public function getLeaderboard($id) {
        return GameLeaderboard::getLeaderboard($this->shortName, $id);
    }

    /**
     * Returns an array containing all of this game's leaderboards
     *
     * @return array The leaderboards for this game
     */
    public function getLeaderboards() {
        return GameLeaderboard::getLeaderboards($this->shortName);
    }

    /**
     * Returns the URL for the logo image of this game
     *
     * @return string The URL for the game logo
     */
    public function getLogoUrl() {
        if ($this->logoHash == null) {
            return null;
        } else {
            return "http://media.steampowered.com/steamcommunity/public/images/apps/{$this->appId}/{$this->logoHash}.jpg";
        }
    }

    /**
     * Returns the URL for the logo thumbnail image of this game
     *
     * @return string The URL for the game logo thumbnail
     */
    public function getLogoThumbnailUrl() {
        if ($this->logoHash == null) {
            return null;
        } else {
            return "http://media.steampowered.com/steamcommunity/public/images/apps/{$this->appId}/{$this->logoHash}_thumb.jpg";
        }
    }

    /**
     * Returns the full name of this game
     *
     * @return string The full name of this game
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the overall number of players currently playing this game
     *
     * @return int The number of players playing this game
     */
    public function getPlayerCount() {
        $params = array('appid' => $this->appId);
        $result = WebApi::getJSON('ISteamUserStats', 'GetNumberOfCurrentPlayers', 1, $params);
        $result = json_decode($result);

        return $result->response->player_count;
    }

    /**
     * Returns the short name of this game (also known as "friendly name")
     *
     * @return string|null
     *   The short name of this game, or null if this game does not have a
     *   short name.
     */
    public function getShortName() {
        return $this->shortName;
    }

    /**
     * Returns the URL of this game's page in the Steam Store
     *
     * @return string This game's store page
     */
    public function getStoreUrl() {
        return "http://store.steampowered.com/app/{$this->appId}";
    }

    /**
     * Creates a stats object for the given user and this game
     *
     * @param string $steamId The custom URL or the 64bit Steam ID of the user
     * @return GameStats The stats of this game for the given user
     */
    public function getUserStats($steamId) {
        if(!$this->hasStats()) {
            return null;
        }

        return GameStats::createGameStats($steamId, $this->shortName);
    }

    /**
     * Returns whether this game has statistics available
     *
     * @return bool <var>true</var> if this game has stats
     */
    public function hasStats() {
        return $this->shortName != null;
    }

    /**
     * Returns whether the given version of this game is up-to-date
     *
     * @param int $version The version to check against the Web API
     * @return boolean <var>true</var> if the given version is up-to-date
     */
    public function isUpToDate($version) {
        return self::checkUpToDate($this->appId, $version);
    }

}
