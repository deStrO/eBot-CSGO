<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/WebApi.php';

/**
 * The GameAchievement class represents a specific achievement for a single
 * game and for a single user
 *
 * It also provides the ability to load the global unlock percentages of all
 * achievements of a specific game.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class GameAchievement {

    /**
     * @var string
     */
    private $apiName;

    /**
     * @var SteamGame
     */
    private $game;

    /**
     * @var string
     */
    private $iconClosedUrl;

    /**
     * @var string
     */
    private $iconOpenUrl;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var SteamId
     */
    private $user;

    /**
     * @var bool
     */
    private $unlocked;

    /**
     * Loads the global unlock percentages of all achievements for the given
     * game
     *
     * @param int $appId The unique Steam Application ID of the game (e.g.
     *        <var>440</var> for Team Fortress 2). See
     *        http://developer.valvesoftware.com/wiki/Steam_Application_IDs for
     *        all application IDs
     * @return array The symbolic achievement names with the corresponding
     *         global unlock percentages
     * @throws WebApiException if a request to Steam's Web API fails
     */
    public static function getGlobalPercentages($appId) {
        $params = array('gameid' => $appId);
        $data = json_decode(WebApi::getJSON('ISteamUserStats', 'GetGlobalAchievementPercentagesForApp', 2, $params));

        $percentages = array();
        foreach($data->achievementpercentages->achievements as $achievementData) {
            $percentages[$achievementData->name] = (float) $achievementData->percent;
        }

        return $percentages;
    }

    /**
     * Creates the achievement with the given name for the given user and game
     * and achievement data
     *
     * @param SteamId $user The Steam ID of the player this achievement belongs
     *        to
     * @param SteamGame $game The game this achievement belongs to
     * @param SimpleXMLElement $achievementData The achievement data extracted
     *        from XML
     */
    public function __construct(SteamId $user, SteamGame $game, SimpleXMLElement $achievementData) {
        $this->apiName       = (string) $achievementData->apiname;
        $this->description   = (string) $achievementData->description;
        $this->game          = $game;
        $this->iconClosedUrl = (string) $achievementData->iconClosed;
        $this->iconOpenUrl   = (string) $achievementData->iconOpen;
        $this->name          = (string) $achievementData->name;
        $this->unlocked      = (bool)(int) $achievementData->attributes()->closed;
        $this->user          = $user;

        if($this->unlocked && $achievementData->unlockTimestamp != null) {
            $this->timestamp = (int) $achievementData->unlockTimestamp;
        }
    }

    /**
     * Returns the symbolic API name of this achievement
     *
     * @return string The API name of this achievement
     */
    public function getApiName() {
        return $this->apiName;
    }

    /**
     * Returns the description of this achievement
     *
     * @return string The description of this achievement
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Returns the game this achievement belongs to
     *
     * @return SteamGame The game this achievement belongs to
     */
    public function getGame() {
        return $this->game;
    }

    /**
     * Returns the url for the closed icon of this achievement
     *
     * @return string The url of the closed achievement icon
     */
    public function getIconClosedUrl() {
        return $this->iconClosedUrl;
    }

    /**
     * Returns the url for the open icon of this achievement
     *
     * @return string The url of the open achievement icon
     */
    public function getIconOpenUrl() {
        return $this->iconOpenUrl;
    }

    /**
     * Returns the name of this achievement
     *
     * @return string The name of this achievement
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the time this achievement has been unlocked by its owner
     *
     * @return int The time this achievement has been unlocked
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Returns the SteamID of the user who owns this achievement
     *
     * @return SteamId The SteamID of this achievement's owner
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Returns whether this achievement has been unlocked by its owner
     *
     * @return bool <var>true</var> if the achievement has been unlocked by the
     *         user
     */
    public function isUnlocked() {
        return $this->unlocked;
    }
}
