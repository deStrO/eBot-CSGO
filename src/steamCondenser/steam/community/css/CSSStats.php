<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/css/CSSMap.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/css/CSSWeapon.php';

/**
 * The is class represents the game statistics for a single user in
 * Counter-Strike: Source
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class CSSStats extends GameStats {

    /**
     * @var array The names of the maps in Counter-Strike: Source
     */
    protected static $MAPS = array( 'cs_assault', 'cs_compound',
           'cs_havana', 'cs_italy', 'cs_militia', 'cs_office', 'de_aztec',
           'de_cbble', 'de_chateau', 'de_dust', 'de_dust2', 'de_inferno',
           'de_nuke', 'de_piranesi', 'de_port', 'de_prodigy', 'de_tides',
           'de_train' );

    /**
     * @var array The names of the weapons in Counter-Strike: Source
     */
    protected static $WEAPONS = array( 'deagle', 'usp', 'glock', 'p228',
            'elite', 'fiveseven', 'awp', 'ak47', 'm4a1', 'aug', 'sg552',
            'sg550', 'galil', 'famas', 'scout', 'g3sg1', 'p90', 'mp5navy',
            'tmp', 'mac10', 'ump45', 'm3', 'xm1014', 'm249', 'knife',
            'grenade' );

    private $lastMatchStats;

    private $totalStats;

    /**
     * Creates a <var>CSSStats</var> instance by calling the super constructor
     * with the game name <var>"cs:s"</var>
     *
     * @param string $steamId The custom URL or 64bit Steam ID of the user
     */
    public function __construct($steamId) {
        parent::__construct($steamId, 'cs:s');

        if($this->isPublic()) {
            $statsData = $this->xmlData->stats;
            $this->lastMatchStats = array();
            $this->totalStats     = array();

            $this->lastMatchStats['costPerKill'] = (float) $statsData->lastmatch->costkill;
            $this->lastMatchStats['ctWins'] = (int) $statsData->lastmatch->ct_wins;
            $this->lastMatchStats['damage'] = (int) $statsData->lastmatch->dmg;
            $this->lastMatchStats['deaths'] = (int) $statsData->lastmatch->deaths;
            $this->lastMatchStats['dominations'] = (int) $statsData->lastmatch->dominations;
            $this->lastMatchStats['favoriteWeaponId'] = (int) $statsData->lastmatch->favwpnid;
            $this->lastMatchStats['kills'] = (int) $statsData->lastmatch->kills;
            $this->lastMatchStats['maxPlayers'] = (int) $statsData->lastmatch->max_players;
            $this->lastMatchStats['money'] = (int) $statsData->lastmatch->money;
            $this->lastMatchStats['revenges'] = (int) $statsData->lastmatch->revenges;
            $this->lastMatchStats['stars'] = (int) $statsData->lastmatch->stars;
            $this->lastMatchStats['tWins'] = (int) $statsData->lastmatch->t_wins;
            $this->lastMatchStats['wins'] = (int) $statsData->lastmatch->wins;
            $this->totalStats['blindKills'] = (int) $statsData->lifetime->blindkills;
            $this->totalStats['bombsDefused'] = (int) $statsData->lifetime->bombsdefused;
            $this->totalStats['bombsPlanted'] = (int) $statsData->lifetime->bombsplanted;
            $this->totalStats['damage'] = (int) $statsData->lifetime->dmg;
            $this->totalStats['deaths'] = (int) $statsData->summary->deaths;
            $this->totalStats['dominationOverkills'] = (int) $statsData->lifetime->dominationoverkills;
            $this->totalStats['dominations'] = (int) $statsData->lifetime->dominations;
            $this->totalStats['earnedMoney'] = (int) $statsData->lifetime->money;
            $this->totalStats['enemyWeaponKills'] = (int) $statsData->lifetime->enemywpnkills;
            $this->totalStats['headshots'] = (int) $statsData->lifetime->headshots;
            $this->totalStats['hits'] = (int) $statsData->summary->shotshit;
            $this->totalStats['hostagesRescued'] = (int) $statsData->lifetime->hostagesrescued;
            $this->totalStats['kills'] = (int) $statsData->summary->kills;
            $this->totalStats['knifeKills'] = (int) $statsData->lifetime->knifekills;
            $this->totalStats['logosSprayed'] = (int) $statsData->lifetime->decals;
            $this->totalStats['nightvisionDamage'] = (int) $statsData->lifetime->nvgdmg;
            $this->totalStats['pistolRoundsWon'] = (int) $statsData->lifetime->pistolrounds;
            $this->totalStats['revenges'] = (int) $statsData->lifetime->revenges;
            $this->totalStats['roundsPlayed'] = (int) $statsData->summary->rounds;
            $this->totalStats['roundsWon'] = (int) $statsData->summary->wins;
            $this->totalStats['secondsPlayed'] = (int) $statsData->summary->timeplayed;
            $this->totalStats['shots'] = (int) $statsData->summary->shots;
            $this->totalStats['stars'] = (int) $statsData->summary->stars;
            $this->totalStats['weaponsDonated'] = (int) $statsData->lifetime->wpndonated;
            $this->totalStats['windowsBroken'] = (int) $statsData->lifetime->winbroken;
            $this->totalStats['zoomedSniperKills'] = (int) $statsData->lifetime->zsniperkills;

            $this->lastMatchStats['kdratio'] = ($this->totalStats['deaths'] > 0) ? $this->lastMatchStats['kills'] / $this->lastMatchStats['deaths'] : 0;
            $this->totalStats['accuracy'] = ($this->totalStats['shots'] > 0) ? $this->totalStats['hits'] / $this->totalStats['shots'] : 0;
            $this->totalStats['kdratio'] = ($this->totalStats['deaths'] > 0) ? $this->totalStats['kills'] / $this->totalStats['deaths'] : 0;
            $this->totalStats['roundsLost'] = $this->totalStats['roundsPlayed'] - $this->totalStats['roundsWon'];
        }
    }

    /**
     * Returns statistics about the last match the player played
     *
     * @return array The stats of the last match
     */
    public function getLastMatchStats() {
        return $this->lastMatchStats;
    }

    /**
     * Returns a map of <var>CSSMap</var> for this user containing all CS:S
     * maps.
     *
     * If the maps haven't been parsed already, parsing is done now.
     *
     * @return array The map statistics for this user
    */
    public function getMapStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if($this->mapStats == null) {
            $this->mapStats = array();
            $mapsData = $this->xmlData->stats->maps;

            foreach(self::$MAPS as $mapName) {
                $this->mapStats[$mapName] = new CSSMap($mapName, $mapsData);
            }
        }

        return $this->mapStats;
    }

    /**
     * Returns overall statistics of this player
     *
     * @return array The overall statistics
     */
    public function getTotalStats() {
        return $this->totalStats;
    }

    /**
     * Returns a map of <var>CSSWeapon</var> for this user containing all CS:S
     * weapons.
     *
     * If the weapons haven't been parsed already, parsing is done now.
     *
     * @return array The weapon statistics for this user
    */
    public function getWeaponStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if($this->weaponStats == null) {
            $this->weaponStats = array();
            $weaponData = $this->xmlData->stats->weapons;

            foreach(self::$WEAPONS as $weaponName) {
                $this->weaponStats[$weaponName] = new CSSWeapon($weaponName, $weaponData);
            }
        }

        return $this->weaponStats;
    }

}
