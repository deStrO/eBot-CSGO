<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/GameAchievement.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/GameLeaderboard.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/XMLData.php';

/**
 * This class represents the game statistics for a single user and a specific
 * game
 *
 * It is subclassed for individual games if the games provide special
 * statistics that are unique to this game.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class GameStats extends XMLData {

    /**
     * @var array
     */
    protected $achievements;

    /**
     * @var int
     */
    protected $achievementsDone;

    /**
     * @var SteamGame
     */
    protected $game;

    /**
     * @var SteamId
     */
    protected $user;

    /**
     * Used to cache the XML data of the statistics for this game and this
     * user
     *
     * @var SimpleXMLElement
     */
    protected $xmlData;

    /**
     * Creates a <var>GameStats</var> (or one of its subclasses) instance for
     * the given user and game
     *
     * @param string $steamId The custom URL or the 64bit Steam ID of the user
     * @param string $gameName The friendly name of the game
     * @return GameStats The game stats object for the given user and game
     */
    public static function createGameStats($steamId, $gameName) {
        switch($gameName) {
            case 'alienswarm':
                require_once STEAM_CONDENSER_PATH . 'steam/community/alien_swarm/AlienSwarmStats.php';
                return new AlienSwarmStats($steamId);
            case 'cs:s':
                require_once STEAM_CONDENSER_PATH . 'steam/community/css/CSSStats.php';
                return new CSSStats($steamId);
            case 'defensegrid:awakening':
                require_once STEAM_CONDENSER_PATH . 'steam/community/defense_grid/DefenseGridStats.php';
                return new DefenseGridStats($steamId);
            case 'dod:s':
                require_once STEAM_CONDENSER_PATH . 'steam/community/dods/DoDSStats.php';
                return new DoDSStats($steamId);
            case 'l4d':
                require_once STEAM_CONDENSER_PATH . 'steam/community/l4d/L4DStats.php';
                return new L4DStats($steamId);
            case 'l4d2':
                require_once STEAM_CONDENSER_PATH . 'steam/community/l4d/L4D2Stats.php';
                return new L4D2Stats($steamId);
            case 'portal2':
                require_once STEAM_CONDENSER_PATH . 'steam/community/portal2/Portal2Stats.php';
                return new Portal2Stats($steamId);
            case 'tf2':
                require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2Stats.php';
                return new TF2Stats($steamId);
            default:
                return new GameStats($steamId, $gameName);
        }
    }

    /**
     * Returns the base Steam Communtiy URL for the given player and game IDs
     *
     * @param string $userId The 64bit SteamID or custom URL of the user
     * @param mixed $gameId The application ID or short name of the game
     * @return string The base URL used for the given stats IDs
     */
    protected static function _getBaseUrl($userId, $gameId) {
        $gameUrl = $gameId;
        if(is_numeric($gameId)) {
            $gameUrl = 'appid/' . $gameUrl;
        }

        if(is_numeric($userId)) {
            return "http://steamcommunity.com/profiles/$userId/stats/$gameUrl";
        } else {
            return "http://steamcommunity.com/id/$userId/stats/$gameUrl";
        }
    }

    /**
     * Creates a <var>GameStats</var> object and fetches data from the Steam
     * Community for the given user and game
     *
     * @param string $steamId The custom URL or the 64bit Steam ID of the user
     * @param string $gameId The app ID or friendly name of the game
     * @throws SteamCondenserException if the stats cannot be fetched
     */
    protected function __construct($steamId, $gameId) {
        $this->user = SteamId::create($steamId, false);

        $url = self::_getBaseUrl($steamId, $gameId) . '?xml=all';

        $this->xmlData = $this->getData($url);

        if($this->xmlData->error != null && !empty($this->xmlData->error)) {
            throw new SteamCondenserException((string) $this->xmlData->error);
        }

        $this->privacyState = (string) $this->xmlData->privacyState;
        if($this->isPublic()) {
            preg_match('#http://steamcommunity.com/+app/+([1-9][0-9]*)#', (string) $this->xmlData->game->gameLink, $appId);
            $this->game = SteamGame::create((int) $appId[1], $this->xmlData->game);
            $this->hoursPlayed = (string) $this->xmlData->stats->hoursPlayed;
        }
    }

    /**
     * Returns the achievements for this stats' user and game
     *
     * If the achievements' data hasn't been parsed yet, parsing is done now.
     *
     * @return array All achievements belonging to this game
    */
    public function getAchievements() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->achievements)) {
            $this->achievementsDone = 0;
            foreach($this->xmlData->achievements->children() as $achievementData) {
                $this->achievements[] = new GameAchievement($this->user, $this->game, $achievementData);
                if((int) $achievementData->attributes()->closed) {
                    $this->achievementsDone += 1;
                }
            }
        }

        return $this->achievements;
    }

    /**
     * Returns the number of achievements done by this player
     *
     * If achievements haven't been parsed yet for this player and this game,
     * parsing is done now.
     *
     * @return int The number of achievements completed
     * @see getAchievements()
     */
    public function getAchievementsDone() {
        if(empty($this->achievements)) {
            $this->getAchievements();
        }

        return $this->achievementsDone;
    }

    /**
     * Returns the percentage of achievements done by this player
     * <p>
     * If achievements haven't been parsed yet for this player and this game,
     * parsing is done now.
     *
     * @return float The percentage of achievements completed
     * @see #getAchievementsDone
     */
    public function getAchievementsPercentage() {
        return $this->getAchievementsDone() / sizeof($this->achievements);
    }

    /**
     * Returns the base Steam Communtiy URL for the stats contained in this
     * object
     *
     * @return string The base URL used for queries on these stats
     */
    public function getBaseUrl() {
        return self::_getBaseUrl($this->user->getId(), $this->game->getId());
    }

    /**
     * Returns the privacy setting of the Steam ID profile
     *
     * @return string The privacy setting of the Steam ID
     */
    public function getPrivacyState() {
        return $this->privacyState;
    }

    /**
     * Returns the game these stats belong to
     *
     * @return SteamGame The game object
     */
    public function getGame() {
        return $this->game;
    }

    /**
     * Returns the number of hours this game has been played by the player
     *
     * @return string The number of hours this game has been played
     */
    public function getHoursPlayed() {
        return $this->hoursPlayed;
    }

    /**
     * Returns whether this Steam ID is publicly accessible
     *
     * @return bool <var>true</var> if this Steam ID is publicly accessible
     */
    public function isPublic() {
        return $this->privacyState == 'public';
    }
}
