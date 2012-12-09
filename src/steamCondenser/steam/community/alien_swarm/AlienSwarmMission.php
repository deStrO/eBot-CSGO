<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * This class holds statistical information about missions played by a player
 * in Alien Swarm
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class AlienSwarmMission {

    /**
     * @var float
     */
    private $avgDamageTaken;

    /**
     * @var float
     */
    private $avgFriendlyFire;

    /**
     * @var float
     */
    private $avgKills;

    /**
     * @var string
     */
    private $bestDifficulty;

    /**
     * @var int
     */
    private $damageTaken;

    /**
     * @var int
     */
    private $friendlyFire;

    /**
     * @var int
     */
    private $gamesSuccessful;

    /**
     * @var string
     */
    private $img;

    /**
     * @var int
     */
    private $kills;

    /**
     * @var string
     */
    private $mapName;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $time;

    /**
     * @var string
     */
    private $totalGames;

    /**
     * @var float
     */
    private $totalGamesPercentage;

    /**
     * Creates a new mission instance of based on the given XML data
     *
     * @param SimpleXMLElement $missionData The data representing this mission
     */
    public function __construct(SimpleXMLElement $missionData) {
        $this->avgDamageTaken       = (float) $missionData->damagetakenavg;
        $this->avgFriendlyFire      = (float) $missionData->friendlyfireavg;
        $this->avgKills             = (float) $missionData->killsavg;
        $this->bestDifficulty       = (string) $missionData->bestdifficulty;
        $this->damageTaken          = (int) $missionData->damagetaken;
        $this->friendlyFire         = (int) $missionData->friendlyfire;
        $this->gamesSuccessful      = (int) $missionData->gamessuccess;
        $this->img                  = AlienSwarmStats::BASE_URL . (string) $missionData->image;
        $this->kills                = (int) $missionData->kills;
        $this->mapName              = $missionData->getName();
        $this->name                 = (string) $missionData->name;
        $this->totalGames           = (int) $missionData->gamestotal;
        $this->totalGamesPercentage = (float) $missionData->gamestotalpct;

        $this->time = array();
        $this->time['average'] = (string) $missionData->avgtime;
        $this->time['brutal']  = (string) $missionData->brutaltime;
        $this->time['easy']    = (string) $missionData->easytime;
        $this->time['hard']    = (string) $missionData->hardtime;
        $this->time['insane']  = (string) $missionData->insanetime;
        $this->time['normal']  = (string) $missionData->normaltime;
        $this->time['total']   = (string) $missionData->totaltime;
    }

    /**
     * Returns the avarage damage taken by the player while playing a round in
     * this mission
     *
     * @return float The average damage taken by the player
     */
    public function getAvgDamageTaken() {
        return $this->avgDamageTaken;
    }

    /**
     * Returns the avarage damage dealt by the player to team mates while
     * playing a round in this mission
     *
     * @return float The average damage dealt by the player to team mates
     */
    public function getAvgFriendlyFire() {
        return $this->avgFriendlyFire;
    }

    /**
     * Returns the avarage number of aliens killed by the player while playing
     * a round in this mission
     *
     * @return float The avarage number of aliens killed by the player
     */
    public function getAvgKills() {
        return $this->avgKills;
    }

    /**
     * Returns the highest difficulty the player has beat this mission in
     *
     * @return string The highest difficulty the player has beat this mission
     *         in
     */
    public function getBestDifficulty() {
        return $this->bestDifficulty;
    }

    /**
     * Returns the total damage taken by the player in this mission
     *
     * @return int The total damage taken by the player
     */
    public function getDamageTaken() {
        return $this->damageTaken;
    }

    /**
     * Returns the total damage dealt by the player to team mates in this
     * mission
     *
     * @return int The total damage dealt by the player to team mates
     */
    public function getFriendlyFire() {
        return $this->friendlyFire;
    }

    /**
     * Returns the number of successful rounds the player played in this
     * mission
     *
     * @return int The number of successful rounds of this mission
     */
    public function getGamesSuccessful() {
        return $this->gamesSuccessful;
    }

    /**
     * Returns the URL to a image displaying the mission
     *
     * @return string The URL of the mission's image
     */
    public function getImg() {
        return $this->img;
    }

    /**
     * Returns the total number of aliens killed by the player in this mission
     *
     * @return int The total number of aliens killed by the player
     */
    public function getKills() {
        return $this->kills;
    }

    /**
     * Returns the file name of the mission's map
     *
     * @return string The file name of the mission's map
     */
    public function getMapName() {
        return $this->mapName;
    }

    /**
     * Returns the name of the mission
     *
     * @return string The name of the mission
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns various statistics about the times needed to accomplish this
     * mission
     *
     * This includes the best times for each difficulty, the average time and
     * the total time spent in this mission.
     *
     * @return array Various time statistics about this mission
     */
    public function getTime() {
        return $this->time;
    }

    /**
     * Returns the number of games played in this mission
     *
     * @return int The number of games played in this mission
     */
    public function getTotalGames() {
        return $this->totalGames;
    }

    /**
     * Returns the percentage of successful games played in this mission
     *
     * @return float The percentage of successful games played in this mission
     */
    public function getTotalGamesPercentage() {
        return $this->totalGamesPercentage;
    }

}
