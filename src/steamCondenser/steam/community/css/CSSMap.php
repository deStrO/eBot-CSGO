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
 * Represents the stats for a Counter-Strike: Source map for a specific user
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class CSSMap {

    /**
     * @var bool
     */
    private $favorite;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $roundsPlayed;

    /**
     * @var int
     */
    private $roundsLost;

    /**
     * @var int
     */
    private $roundsWon;

    /**
     * Creates a new instance of a Counter-Strike: Source class based on the
     * given XML data
     *
     * @param string $mapName The name of the map
     * @param SimpleXMLElement $mapsData The XML data of all maps
     */
    public function __construct($mapName, SimpleXMLElement $mapsData) {
        $this->name = $mapName;

        $this->favorite     = ((string) $mapsData->favorite) == $this->name;
        $this->roundsPlayed = (int) $mapsData->{"{$this->name}_rounds"};
        $this->roundsWon    = (int) $mapsData->{"{$this->name}_wins"};
        $this->roundsLost   = $this->roundsPlayed - $this->roundsWon;
    }

    /**
     * Returns whether this map is the favorite map of this player
     *
     * @return bool <var>true</var> if this is the favorite map
     */
    public function isFavorite() {
        return $this->favorite;
    }

    /**
     * Returns the name of this map
     *
     * @return string The name of this map
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the number of rounds the player has lost on this map
     *
     * @return int The number of rounds lost
     */
    public function getRoundsLost() {
        return $this->roundsLost;
    }

    /**
     * Returns the number of rounds the player has played on this map
     *
     * @return int The number of rounds played
     */
    public function getRoundsPlayed() {
        return $this->roundsPlayed;
    }

    /**
     * Returns the number of rounds the player has won on this map
     *
     * @return int The number of rounds won
     */
    public function getRoundsWon() {
        return $this->roundsWon;
    }

    /**
     * Returns the percentage of rounds the player has won on this map
     *
     * @return float The percentage of rounds won
     */
    public function getRoundsWonPercentage() {
        return ($this->roundsPlayed > 0) ? ($this->roundsWon / $this->roundsPlayed) : 0;
    }
}
