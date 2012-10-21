<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/GameClass.php';

/**
 * Represents the stats for a Team Fortress 2 class for a specific user
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage communty
 */
class TF2Class extends GameClass {

    /**
     * @var int
     */
    private $maxBuildingsDestroyed;

    /**
     * @var int
     */
    private $maxCaptures;

    /**
     * @var int
     */
    private $maxDamage;

    /**
     * @var int
     */
    private $maxDefenses;

    /**
     * @var int
     */
    private $maxDominations;

    /**
     * @var int
     */
    private $maxKillAssists;

    /**
     * @var int
     */
    private $maxKills;

    /**
     * @var int
     */
    private $maxRevenges;

    /**
     * @var int
     */
    private $maxScore;

    /**
     * @var int
     */
    private $maxTimeAlive;

    /**
     * @var int
     */
    private $playTime;

    /**
     * Creates a new TF2 class instance based on the assigned XML data
     *
     * @param SimpleXMLElement $classData The XML data for this class
     */
    public function __construct(SimpleXMLElement $classData) {
        $this->name                  = (string) $classData->className;
        $this->maxBuildingsDestroyed = (int)    $classData->ibuildingsdestroyed;
        $this->maxCaptures           = (int)    $classData->ipointcaptures;
        $this->maxDamage             = (int)    $classData->idamagedealt;
        $this->maxDefenses           = (int)    $classData->ipointdefenses;
        $this->maxDominations        = (int)    $classData->idominations;
        $this->maxKillAssists        = (int)    $classData->ikillassists;
        $this->maxKills              = (int)    $classData->inumberofkills;
        $this->maxRevenges           = (int)    $classData->irevenge;
        $this->maxScore              = (int)    $classData->ipointsscored;
        $this->maxTimeAlive          = (int)    $classData->iplaytime;
        $this->playTime              = (int)    $classData->playtimeSeconds;
    }

    /**
     * Returns the maximum number of buildings the player has destroyed in a
     * single life with this class
     *
     * @return int Maximum number of buildings destroyed
     */
    public function getMaxBuildingsDestroyed() {
        return $this->maxBuildingsDestroyed;
    }

    /**
     * Returns the maximum number of points captured by the player in a single
     * life with this class
     *
     * @return int Maximum number of points captured
     */
    public function getMaxCaptures() {
        return $this->maxCaptures;
    }

    /**
     * Returns the maximum damage dealt by the player in a single life with
     * this class
     *
     * @return int Maximum damage dealt
     */
    public function getMaxDamage() {
        return $this->maxDamage;
    }

    /**
     * Returns the maximum number of defenses by the player in a single life
     * with this class
     *
     * @return int Maximum number of defenses
     */
    public function getMaxDefenses() {
        return $this->maxDefnses;
    }

    /**
     * Returns the maximum number of dominations by the player in a single life
     * with this class
     *
     * @return int Maximum number of dominations
     */
    public function getMaxDominations() {
        return $this->maxDominations;
    }

    /**
     * Returns the maximum number of times the the player assisted a teammate
     * with killing an enemy in a single life with this class
     *
     * @return int Maximum number of kill assists
     */
    public function getMaxKillAssists() {
        return $this->maxKillAssists;
    }

    /**
     * Returns the maximum number of enemies killed by the player in a single
     * life with this class
     *
     * @return int Maximum number of kills
     */
    public function getMaxKills() {
        return $this->maxKills;
    }

    /**
     * Returns the maximum number of revenges by the player in a single life
     * with this class
     *
     * @return int Maximum number of revenges
     */
    public function getMaxRevenges() {
        return $this->maxRevenges;
    }

    /**
     * Returns the maximum number score achieved by the player in a single life
     * with this class
     *
     * @return int Maximum score
     */
    public function getMaxScore() {
        return $this->maxScore;
    }

    /**
     * Returns the maximum lifetime by the player in a single life with this
     * class
     *
     * @return int Maximum lifetime
     */
    public function getMaxTimeAlive() {
        return $this->maxTimeAlive;
    }

}
