<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/l4d/AbstractL4DStats.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/l4d/L4D2Map.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/l4d/L4D2Weapon.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/l4d/L4DExplosive.php';

/**
 * This class represents the game statistics for a single user in Left4Dead 2
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class L4D2Stats extends AbstractL4DStats {

    /**
     * @var array The names of the special infected in Left4Dead 2
     */
    protected static $SPECIAL_INFECTED = array('boomer', 'charger', 'hunter', 'jockey', 'smoker', 'spitter', 'tank');

    /**
     * @var array
     */
    private $damagePercentages;

    /**
     * @var array
     */
    private $scavengeStats;

    /**
     * Creates a <var>L4D2Stats</var> object by calling the super constructor
     * with the game name <var>"l4d2"</var>
     *
     * @param string $steamId The custom URL or 64bit Steam ID of the user
     */
    public function __construct($steamId) {
        parent::__construct($steamId, 'l4d2');

        $this->damagePercentages = array(
            'melee' => (float) $this->xmlData->stats->weapons->meleePctDmg,
            'pistols' => (float) $this->xmlData->stats->weapons->pistolsPctDmg,
            'rifles' => (float) $this->xmlData->stats->weapons->bulletsPctDmg,
            'shotguns' => (float) $this->xmlData->stats->weapons->shellsPctDmg
        );
    }

    /**
     * Returns an array of lifetime statistics for this user like the time
     * played
     *
     * If the lifetime statistics haven't been parsed already, parsing is done
     * now.
     *
     * There are only a few additional lifetime statistics for Left4Dead 2
     * which are not generated for Left4Dead, so this calls
     * <var>AbstractL4DStats#getLifetimeStats()</var> first and adds some
     * additional stats.
     *
     * @return array The lifetime statistics of the player in Left4Dead 2
     */
    public function getLifetimeStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->lifetimeStats)) {
            parent::getLifetimeStats();
            $this->lifetimeStats['avgAdrenalineShared']   = (float) $this->xmlData->stats->lifetime->adrenalineshared;
            $this->lifetimeStats['avgAdrenalineUsed']     = (float) $this->xmlData->stats->lifetime->adrenalineused;
            $this->lifetimeStats['avgDefibrillatorsUsed'] = (float) $this->xmlData->stats->lifetime->defibrillatorsused;
        }

        return $this->lifetimeStats;
    }

    /**
     * Returns the percentage of damage done by this player with each weapon
     * type
     *
     * Available weapon types are <var>"melee"</var>, <var>"pistols"</var>,
     * <var>"rifles"</var> and <var>"shotguns"</var>.
     *
     * @return float The percentages of damage done with each weapon type
     */
    public function getDamagePercentage() {
        return $this->DamagePercentage;
    }

    /**
     * Returns the percentage of damage done by this player with pistols
     *
     * @return float The percentage of damage done with pistols
     */
    public function getPistolDamagePercentage() {
        return $this->pistolDamagePercentage;
    }

    /**
     * Returns the percentage of damage done by this player with rifles
     *
     * @return float The percentage of damage done with rifles
     */
    public function getRifleDamagePercentage() {
        return $this->rifleDamagePercentage;
    }

    /**
     * Returns an array of Scavenge statistics for this user like the number of
     * Scavenge rounds played
     *
     * If the Scavenge statistics haven't been parsed already, parsing is done
     * now.
     *
     * @return array The Scavenge statistics of the player
     */
    public function getScavengeStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->scavengeStats)) {
            $this->scavengeStats = array();
            $this->scavengeStats['avgCansPerRound'] = (float) $this->xmlData->stats->scavenge->avgcansperround;
            $this->scavengeStats['perfectRounds']   = (int)   $this->xmlData->stats->scavenge->perfect16canrounds;
            $this->scavengeStats['roundsLost']      = (int)   $this->xmlData->stats->scavenge->roundslost;
            $this->scavengeStats['roundsPlayed']    = (int)   $this->xmlData->stats->scavenge->roundsplayed;
            $this->scavengeStats['roundsWon']       = (int)   $this->xmlData->stats->scavenge->roundswon;
            $this->scavengeStats['totalCans']       = (int)   $this->xmlData->stats->scavenge->totalcans;

            $this->scavengeStats['maps'] = array();
            foreach($this->xmlData->stats->scavenge->mapstats->children() as $mapData) {
                $map_id = (string) $mapData->name;
                $this->scavengeStats['maps'][$map_id] = array();
                $this->scavengeStats['maps'][$map_id]['avgRoundScore']     = (int)    $mapData->avgscoreperround;
                $this->scavengeStats['maps'][$map_id]['highestGameScore']  = (int)    $mapData->highgamescore;
                $this->scavengeStats['maps'][$map_id]['highestRoundScore'] = (int)    $mapData->avgscoreperround;
                $this->scavengeStats['maps'][$map_id]['name']              = (string) $mapData->fullname;
                $this->scavengeStats['maps'][$map_id]['roundsPlayed']      = (int)    $mapData->roundsplayed;
                $this->scavengeStats['maps'][$map_id]['roundsWon']         = (int)    $mapData->roundswon;
            }

            $this->scavengeStats['infected'] = array();
            foreach($this->xmlData->stats->scavenge->infectedstats->children() as $infectedData) {
                $infectedId = (string) $infectedData->name;
                $this->scavengeStats['infected'][$infectedId] = array();
                $this->scavengeStats['infected'][$infectedId]['maxDamagePerLife']    = (int) $infectedData->maxdmg1life;
                $this->scavengeStats['infected'][$infectedId]['maxPoursInterrupted'] = (int) $infectedData->maxpoursinterrupted;
                $this->scavengeStats['infected'][$infectedId]['specialAttacks']      = (int) $infectedData->specialattacks;
            }
        }

        return $this->scavengeStats;
    }

    /**
     * Returns the percentage of damage done by this player with shotguns
     *
     * @return float The percentage of damage done with shotguns
     */
    public function getShotgunDamagePercentage() {
        return $this->shotgunDamagePercentage;
    }

    /**
     * Returns an array of Survival statistics for this user like revived
     * teammates
     *
     * If the Survival statistics haven't been parsed already, parsing is done
     * now.
     *
     * The XML layout for the Survival statistics for Left4Dead 2 differs a bit
     * from Left4Dead's Survival statistics. So we have to use a different way
     * of parsing for the maps and we use a different map class
     * (<var>L4D2Map</var>) which holds the additional information provided in
     * Left4Dead 2's statistics.
     *
     * @return array The Survival statistics of the player
     */
    public function getSurvivalStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->survivalStats)) {
            parent::getSurvivalStats();
            $this->survivalStats['maps'] = array();
            foreach($this->xmlData->stats->survival->maps->children() as $mapData) {
                $map = new L4D2Map($mapData);
                $this->survivalStats['maps'][$map->getId()] = $map;
            }
        }

        return $this->survivalStats;
    }

    /**
     * Returns an array of <var>L4D2Weapon</var> for this user containing all
     * Left4Dead 2 weapons
     *
     * If the weapons haven't been parsed already, parsing is done now.
     *
     * @return array The weapon statistics for this player
     */
    public function getWeaponStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->weaponStats)) {
          $this->weaponStats = array();
          foreach($this->xmlData->stats->weapons->children() as $weaponData) {
            if(empty($weaponData)) {
                continue;
            }

            $weaponName = $weaponData->getName();
            if(!in_array($weaponName, array('bilejars', 'molotov', 'pipes'))) {
              $weapon = new L4D2Weapon($weaponData);
            }
            else {
              $weapon = new L4DExplosive($weaponData);
            }

            $this->weaponStats[$weaponName] = $weapon;
          }
        }

        return $this->weaponStats;
    }

    /**
     * Returns the names of the special infected in Left4Dead 2
     *
     * Hacky workaround for PHP not allowing arrays as class constants
     *
     * @return array The names of the special infected in Left4Dead 2
     */
    protected function SPECIAL_INFECTED() {
        return self::$SPECIAL_INFECTED;
    }

}
