<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * This class holds statistical information about a map played by a player in
 * Survival mode of Left4Dead
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class L4DMap {

    const GOLD   = 1;

    const SILVER = 2;

    const BRONZE = 3;

    const NONE   = 0;

    protected $bestTime;

    protected $id;

    protected $medal;

    protected $name;

    private $timesPlayed;

    /**
     * Creates a new instance of a Left4Dead Survival map based on the given
     * XML data
     *
     * @param SimpleXMLElement $mapData The XML data for this map
     */
    public function __construct(SimpleXMLElement $mapData) {
        $this->bestTime    = (float)  $mapData->besttimeseconds;
        $this->id          = $mapData->getName();
        $this->name        = (string) $mapData->name;
        $this->timesPlayed = (int)    $mapData->timesplayed;

        switch((string) $mapData->medal) {
            case 'gold':
                $this->medal = self::GOLD;
                break;
            case 'silver':
                $this->medal = self::SILVER;
                break;
            case 'bronze':
                $this->medal = self::BRONZE;
                break;
            default:
                $this->medal = self::NONE;
        }
    }

    /**
     * Returns the best survival time of this player on this map
     *
     * @return float The best survival time of this player on this map
     */
    public function getBestTime() {
        return $this->bestTime;
    }

    /**
     * Returns the ID of this map
     *
     * @return string The ID of this map
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the highest medal this player has won on this map
     *
     * @return int The highest medal won by this player on this map
     */
    public function getMedal() {
        return $this->medal;
    }

    /**
     * Returns the name of the map
     *
     * @return string The name of the map
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the number of times this map has been played by this player
     *
     * @return int The number of times this map has been played
     */
    public function getTimesPlayed() {
        return $this->timesPlayed;
    }

}
