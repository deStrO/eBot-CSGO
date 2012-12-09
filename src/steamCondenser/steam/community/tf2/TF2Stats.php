<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/GameStats.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2BetaInventory.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2ClassFactory.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2Inventory.php';

/**
 * This class represents the game statistics for a single user in Team Fortress
 * 2
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class TF2Stats extends GameStats {

    /**
     * @var int
     */
    private $accumulatedPoints;

    /**
     * @var array
     */
    private $classStats;

    /**
     * @var TF2Inventory
     */
    private $inventory;

    /**
     * @var int
     */
    private $totalPlaytime;

    /**
     * Creates a new <var>TF2Stats</var> instance by calling the super
     * constructor with the game name <var>"tf2"</var>
     *
     * @param string $steamId The custom URL or 64bit Steam ID of the user
     * @param bool $beta if <var>true</var>, creates stats for the public TF2
     *        beta
     */
    public function __construct($steamId, $beta = false) {
        parent::__construct($steamId, ($beta ? '520' : 'tf2'));

        if($this->isPublic()) {
            if(!empty($this->xmlData->stats->accumulatedPoints)) {
                $this->accumulatedPoints = (int) $this->xmlData->stats->accumulatedPoints;
            }

            if(!empty($this->xmlData->stats->secondsPlayedAllClassesLifetime)) {
                $this->totalPlaytime = (int) $this->xmlData->stats->secondsPlayedAllClassesLifetime;
            }
        }
    }

    /**
     * Returns the total points this player has achieved in his career
     *
     * @return int This player's accumulated points
     */
    public function getAccumulatedPoints() {
        return $this->accumulatedPoints;
    }

    /**
     * Returns the statistics for all Team Fortress 2 classes for this user
     *
     * If the classes haven't been parsed already, parsing is done now.
     *
     * @return array An array storing individual stats for each Team Fortress 2
     *         class
     */
    public function getClassStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->classStats)) {
            foreach($this->xmlData->stats->classData as $classData) {
                $this->classStats[$classData->className] = TF2ClassFactory::getTF2Class($classData);
            }
        }

        return $this->classStats;
    }

    /**
     * Returns the current Team Fortress 2 inventory (a.k.a. backpack) of this
     * player
     *
     * @return TF2Inventory This player's TF2 backpack
     */
    public function getInventory() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->inventory)) {
            $this->inventory = TF2Inventory::create($this->user->getSteamId64());
        }

        return $this->inventory;
    }

    /**
     * Returns the accumulated number of seconds this player has spent playing
     * as a TF2 class
     *
     * @return int Total seconds played as a TF2 class
     */
    public function getTotalPlaytime() {
        return $this->totalPlaytime;
    }
}
