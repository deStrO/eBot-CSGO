<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/GameStats.php';

/**
 * This abstract class is a base class for statistics for Left4Dead and
 * Left4Dead 2. As both games have more or less the same statistics available
 * in the Steam Community the code for both is pretty much the same.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
abstract class AbstractL4DStats extends GameStats {

    /**
     * @var array The names of the special infected in Left4Dead
     */
    protected static $SPECIAL_INFECTED = array('boomer', 'hunter', 'smoker', 'tank');

    /**
     * @var array
     */
    protected $favorites;

    /**
     * @var array
     */
    protected $lifetimeStats;

    /**
     * @var array
     */
    protected $mostRecentGame;

    /**
     * @var array
     */
    protected $survivalStats;

    /**
     * @var array
     */
    protected $teamplayStats;

    /**
     * @var array
     */
    protected $versusStats;

    /**
     * @var array
     */
    protected $weaponStats;

    /**
     * Creates a new instance of statistics for both, Left4Dead and Left4Dead 2
     * parsing basic common data
     *
     * @param string $steamId The custom URL or 64bit Steam ID of the user
     * @param string $gameName The name of the game
     */
    public function __construct($steamId, $gameName) {
        parent::__construct($steamId, $gameName);

        if($this->isPublic() && !empty($this->xmlData->stats->mostrecentgame)) {
            $this->mostRecentGame['difficulty'] = (string) $this->xmlData->stats->mostrecentgame->difficulty;
            $this->mostRecentGame['escaped']    = (bool)   $this->xmlData->stats->mostrecentgame->bEscaped;
            $this->mostRecentGame['movie']      = (string) $this->xmlData->stats->mostrecentgame->movie;
            $this->mostRecentGame['timePlayed'] = (string) $this->xmlData->stats->mostrecentgame->time;
        }
    }

    /**
     * Returns an array of favorites for this user like weapons and character
     *
     * If the favorites haven't been parsed already, parsing is done now.
     *
     * @return array The favorites of this user
     */
    public function getFavorites() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->favorites)) {
            $this->favorites = array();
            $this->favorites['campaign']                = (string) $this->xmlData->stats->favorites->campaign;
            $this->favorites['campaignPercentage']      = (int)    $this->xmlData->stats->favorites->campaignpct;
            $this->favorites['character']               = (string) $this->xmlData->stats->favorites->character;
            $this->favorites['characterPercentage']     = (int)    $this->xmlData->stats->favorites->characterpct;
            $this->favorites['level1Weapon']            = (string) $this->xmlData->stats->favorites->weapon1;
            $this->favorites['level1Weapon1Percentage'] = (int)    $this->xmlData->stats->favorites->weapon1pct;
            $this->favorites['level2Weapon']            = (string) $this->xmlData->stats->favorites->weapon2;
            $this->favorites['level2Weapon1Percentage'] = (int)    $this->xmlData->stats->favorites->weapon2pct;
        }

        return $this->favorites;
    }

    /**
     * Returns an array of lifetime statistics for this user like the time
     * played
     *
     * If the lifetime statistics haven't been parsed already, parsing is done
     * now.
     *
     * @return array The lifetime statistics for this user
     */
    public function getLifetimeStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->lifetimeStats)) {
            $this->lifetimeStats = array();
            $this->lifetimeStats['finalesSurvived']           = (int)    $this->xmlData->stats->lifetime->finales;
            $this->lifetimeStats['gamesPlayed']               = (int)    $this->xmlData->stats->lifetime->gamesplayed;
            $this->lifetimeStats['finalesSurvivedPercentage'] = $this->lifetimeStats['finalesSurvived'] / $this->lifetimeStats['gamesPlayed'];
            $this->lifetimeStats['infectedKilled']            = (int)    $this->xmlData->stats->lifetime->infectedkilled;
            $this->lifetimeStats['killsPerHour']              = (float)  $this->xmlData->stats->lifetime->killsperhour;
            $this->lifetimeStats['avgKitsShared']             = (float)  $this->xmlData->stats->lifetime->kitsshared;
            $this->lifetimeStats['avgKitsUsed']               = (float)  $this->xmlData->stats->lifetime->kitsused;
            $this->lifetimeStats['avgPillsShared']            = (float)  $this->xmlData->stats->lifetime->pillsshared;
            $this->lifetimeStats['avgPillsUsed']              = (float)  $this->xmlData->stats->lifetime->pillused;
            $this->lifetimeStats['timePlayed']                = (string) $this->xmlData->stats->lifetime->timeplayed;
        }

        return $this->lifetimeStats;
    }

    /**
     * Returns an array of Survival statistics for this user like revived
     * teammates
     *
     * If the Survival statistics haven't been parsed already, parsing is done
     * now.
     *
     * @return array The Survival statistics for this user
     */
    public function getSurvivalStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->survivalStats)) {
          $this->survivalStats = array();
          $this->survivalStats['goldMedals']   = (int)   $this->xmlData->stats->survival->goldmedals;
          $this->survivalStats['silverMedals'] = (int)   $this->xmlData->stats->survival->silvermedals;
          $this->survivalStats['bronzeMedals'] = (int)   $this->xmlData->stats->survival->bronzemedals;
          $this->survivalStats['roundsPlayed'] = (int)   $this->xmlData->stats->survival->roundsplayed;
          $this->survivalStats['bestTime']     = (float) $this->xmlData->stats->survival->besttime;
        }

        return $this->survivalStats;
    }

    /**
     * Returns an array of teamplay statistics for this user like revived
     * teammates
     *
     * If the teamplay statistics haven't been parsed already, parsing is done
     * now.
     *
     * @return array The teamplay statistics for this
     */
    public function getTeamplayStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->teamplayStats)) {
          $this->teamplayStats = array();
          $this->teamplayStats['revived']                    = (int)    $this->xmlData->stats->teamplay->revived;
          $this->teamplayStats['mostRevivedDifficulty']      = (string) $this->xmlData->stats->teamplay->reviveddiff;
          $this->teamplayStats['avgRevived']                 = (float)  $this->xmlData->stats->teamplay->revivedavg;
          $this->teamplayStats['avgWasRevived']              = (float)  $this->xmlData->stats->teamplay->wasrevivedavg;
          $this->teamplayStats['protected']                  = (int)    $this->xmlData->stats->teamplay->protected;
          $this->teamplayStats['mostProtectedDifficulty']    = (string) $this->xmlData->stats->teamplay->protecteddiff;
          $this->teamplayStats['avgProtected']               = (float)  $this->xmlData->stats->teamplay->protectedavg;
          $this->teamplayStats['avgWasProtected']            = (float)  $this->xmlData->stats->teamplay->wasprotectedavg;
          $this->teamplayStats['friendlyFireDamage']         = (int)    $this->xmlData->stats->teamplay->ffdamage;
          $this->teamplayStats['mostFriendlyFireDifficulty'] = (string) $this->xmlData->stats->teamplay->ffdamagediff;
          $this->teamplayStats['avgFriendlyFireDamage']      = (float)  $this->xmlData->stats->teamplay->ffdamageavg;
        }

        return $this->teamplayStats;
    }

    /**
     * Returns an array of Versus statistics for this user like percentage of
     * rounds won
     *
     * If the Versus statistics haven't been parsed already, parsing is done
     * now.
     *
     * @return array The Versus statistics for this user
     */
    public function getVersusStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->versusStats)) {
            $this->versusStats = array();
            $this->versusStats['gamesPlayed']               = (int)    $this->xmlData->stats->versus->gamesplayed;
            $this->versusStats['gamesCompleted']            = (int)    $this->xmlData->stats->versus->gamescompleted;
            $this->versusStats['finalesSurvived']           = (int)    $this->xmlData->stats->versus->finales;
            $this->versusStats['finalesSurvivedPercentage'] = ($this->versusStats['gamesPlayed']) ? $this->versusStats['finalesSurvived'] / $this->versusStats['gamesPlayed'] : 0;
            $this->versusStats['points']                    = (int)    $this->xmlData->stats->versus->points;
            $this->versusStats['mostPointsInfected']        = (string) $this->xmlData->stats->versus->pointas;
            $this->versusStats['gamesWon']                  = (int)    $this->xmlData->stats->versus->gameswon;
            $this->versusStats['gamesLost']                 = (int)    $this->xmlData->stats->versus->gameslost;
            $this->versusStats['highestSurvivorScore']      = (int)    $this->xmlData->stats->versus->survivorscore;

            foreach($this->SPECIAL_INFECTED() as $infected) {
              $this->versusStats[$infected] = array();
              $this->versusStats[$infected]['specialAttacks'] = (int)   $this->xmlData->stats->versus->{$infected . 'special'};
              $this->versusStats[$infected]['mostDamage']     = (int)   $this->xmlData->stats->versus->{$infected . 'dmg'};
              $this->versusStats[$infected]['avgLifespan']    = (float) $this->xmlData->stats->versus->{$infected . 'lifespan'};
            }
        }

        return $this->versusStats;
    }

    /**
     * Returns the names of the special infected in Left4Dead
     *
     * Hacky workaround for PHP not allowing arrays as class constants
     *
     * @return array The names of the special infected in Left4Dead
     */
    protected function SPECIAL_INFECTED() {
        return self::$SPECIAL_INFECTED;
    }

}
