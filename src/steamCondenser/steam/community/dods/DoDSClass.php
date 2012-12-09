<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/GameClass.php';

/**
 * Represents the stats for a Day of Defeat: Source class for a specific user
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class DoDSClass extends GameClass {

    /**
     * @var int
     */
    private $blocks;

    /**
     * @var int
     */
    private $bombsDefused;

    /**
     * @var int
     */
    private $bombsPlanted;

    /**
     * @var int
     */
    private $captures;

    /**
     * @var int
     */
    private $deaths;

    /**
     * @var int
     */
    private $dominations;

    /**
     * @var string
     */
    private $key;

    /**
     * @var int
     */
    private $kills;

    /**
     * @var int
     */
    private $roundsLost;

    /**
     * @var int
     */
    private $roundsWon;

    /**
     * @var int
     */
    private $revenges;

    /**
     * Creates a new instance of a Day of Defeat: Source class based on the
     * given XML data
     *
     * @param SimpleXMLElement $classData The XML data of the class
     */
    public function __construct(SimpleXMLElement $classData) {
        $this->blocks       = (int)    $classData->blocks;
        $this->bombsDefused = (int)    $classData->bombsdefused;
        $this->bombsPlanted = (int)    $classData->bombsplanted;
        $this->captures     = (int)    $classData->captures;
        $this->deaths       = (int)    $classData->deaths;
        $this->dominations  = (int)    $classData->dominations;
        $this->key          = (string) $classData['key'];
        $this->kills        = (int)    $classData->kills;
        $this->name         = (string) $classData->name;
        $this->playTime     = (int)    $classData->playtime;
        $this->roundsLost   = (int)    $classData->roundslost;
        $this->roundsWon    = (int)    $classData->roundswon;
        $this->revenges     = (int)    $classData->revenges;
    }

    /**
     * Returns the blocks achieved by the player with this class
     *
     * @return int The blocks achieved by the player
     */
    public function getBlocks() {
        return $this->blocks;
    }

    /**
     * Returns the bombs defused by the player with this class
     *
     * @return int The bombs defused by the player
     */
    public function getBombsDefuse() {
        return $this->bombsDefused;
    }

    /**
     * Returns the bombs planted by the player with this class
     *
     * @return int the bombs planted by the player
     */
    public function getBombsPlanted() {
        return $this->bombsPlanted;
    }

    /**
     * Returns the number of points captured by the player with this class
     *
     * @return int The number of points captured by the player
     */
    public function getCaptures() {
        return $this->captures;
    }

    /**
     * Returns the number of times the player died with this class
     *
     * @return int The number of deaths by the player
     */
    public function getDeaths() {
        return $this->deaths;
    }

    /**
     * Returns the dominations achieved by the player with this class
     *
     * @return int The dominations achieved by the player
     */
    public function getDominations() {
        return $this->dominations;
    }

    /**
     * Returns the ID of this class
     *
     * @return string The ID of this class
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * Returns the number of enemies killed by the player with this class
     *
     * @return int The number of enemies killed by the player
     */
    public function getKills() {
        return $this->kills;
    }

    /**
     * Returns the revenges achieved by the player with this class
     *
     * @return int The revenges achieved by the player
     */
    public function getRevenges() {
        return $this->revenges;
    }

    /**
     * Returns the number of rounds lost with this class
     *
     * @return int The number of rounds lost with this class
     */
    public function getRoundsLost() {
        return $this->roundsLost;
    }

    /**
     * Returns the number of rounds won with this class
     *
     * @return int The number of rounds won with this class
     */
    public function getRoundsWon() {
        return $this->roundsWon;
    }
}
