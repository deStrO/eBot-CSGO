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
 * This class represents the game statistics for a single user in Defense Grid:
 * The Awakening
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class DefenseGridStats extends GameStats {

    /**
     * @var array
     */
    private $alienStats;

    /**
     * @var int
     */
    private $bronzeMedals;

    /**
     * @var float
     */
    private $damage;

    /**
     * @var float
     */
    private $damageCampaign;

    /**
     * @var float
     */
    private $damageChallenge;

    /**
     * @var int
     */
    private $encountered;

    /**
     * @var int
     */
    private $goldMedals;

    /**
     * @var float
     */
    private $heatDamage;

    /**
     * @var int
     */
    private $interest;

    /**
     * @var int
     */
    private $killed;

    /**
     * @var int
     */
    private $killedCampaign;

    /**
     * @var int
     */
    private $killedChallenge;

    /**
     * @var int
     */
    private $levelsPlayed;

    /**
     * @var int
     */
    private $levelsPlayedCampaign;

    /**
     * @var int
     */
    private $levelsPlayedChallenge;

    /**
     * @var int
     */
    private $levelsWon;

    /**
     * @var int
     */
    private $levelsWonCampaign;

    /**
     * @var int
     */
    private $levelsWonChallenge;

    /**
     * @var int
     */
    private $silverMedals;

    /**
     * @var float
     */
    private $orbitalLaserDamage;

    /**
     * @var int
     */
    private $orbitalLaserFired;

    /**
     * @var int
     */
    private $resources;

    /**
     * @var float
     */
    private $timePlayed;

    /**
     * @var array
     */
    private $towerStats;

    /**
     * Creates a <var>DefenseGridStats</var> object by calling the super
     * constructor with the game name <var>"defensegrid:awakening"</var>
     *
     * @param string $steamId The custom URL or the 64bit Steam ID of the user
     */
    public function __construct($steamId) {
        parent::__construct($steamId, 'defensegrid:awakening');

        if($this->isPublic()) {
            $generalData = $this->xmlData->stats->general;

            $this->bronzeMedals          = (int) $generalData->bronze_medals_won->value;
            $this->silverMedals          = (int) $generalData->silver_medals_won->value;
            $this->goldMedals            = (int) $generalData->gold_medals_won->value;
            $this->levelsPlayed          = (int) $generalData->levels_played_total->value;
            $this->levelsPlayedCampaign  = (int) $generalData->levels_played_campaign->value;
            $this->levelsPlayedChallenge = (int) $generalData->levels_played_challenge->value;
            $this->levelsWon             = (int) $generalData->levels_won_total->value;
            $this->levelsWonCampaign     = (int) $generalData->levels_won_campaign->value;
            $this->levelsWonChallenge    = (int) $generalData->levels_won_challenge->value;
            $this->encountered           = (int) $generalData->total_aliens_encountered->value;
            $this->killed                = (int) $generalData->total_aliens_killed->value;
            $this->killedCampaign        = (int) $generalData->total_aliens_killed_campaign->value;
            $this->killedChallenge       = (int) $generalData->total_aliens_killed_challenge->value;
            $this->resources             = (int) $generalData->resources_recovered->value;
            $this->heatDamage            = (float) $generalData->heatdamage->value;
            $this->timePlayed            = (float) $generalData->time_played->value;
            $this->interest              = (float) $generalData->interest_gained->value;
            $this->damage                = (float) $generalData->tower_damage_total->value;
            $this->damageCampaign        = (float) $generalData->tower_damage_total_campaign->value;
            $this->damageChallenge       = (float) $generalData->tower_damage_total_challenge->value;
            $this->orbitalLaserFired     = (int) $this->xmlData->stats->orbitallaser->fired->value;
            $this->orbitalLaserDamage    = (float) $this->xmlData->stats->orbitallaser->damage->value;
        }
    }

    /**
     * Returns stats about the aliens encountered by the player
     *
     * The array returned uses the names of the aliens as keys. Every value of
     * the array is an array containing the number of aliens encountered as the
     * first element and the number of aliens killed as the second element.
     *
     * @return array Stats about the aliens encountered
     */
    public function getAlienStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->alienStats)) {
            $alienData = $this->xmlData->stats->aliens;
            $this->alienStats = array();
            $aliens = array('bulwark', 'crasher', 'dart', 'decoy', 'drone',
                'grunt', 'juggernaut', 'manta', 'racer', 'rumbler', 'seeker',
                'spire', 'stealth', 'swarmer', 'turtle', 'walker');

            foreach($aliens as $alien) {
                $this->alienStats[$alien] = array(
                    (int) $alienData->$alien->encountered->value,
                    (int) $alienData->$alien->killed->value
                );
            }
        }

        return $this->alienStats;
    }

    /**
     * Returns the bronze medals won by this player
     *
     * @return int Bronze medals won
     */
    public function getBronzeMedals() {
        return $this->bronzeMedals;
    }

    /**
     * Returns the damage done by this player
     *
     * @return float Damage done
     */
    public function getDamage() {
        return $this->damage;
    }

    /**
     * Returns the damage done during the campaign by this player
     *
     * @return float Damage done during the campaign
     */
    public function getDamageCampaign() {
        return $this->damageCampaign;
    }

    /**
     * Returns the damage done during challenges by this player
     *
     * @return float Damage done during challenges
     */
    public function getDamageChallenge() {
        return $this->damageChallenge;
    }

    /**
     * Returns the aliens encountered by this player
     *
     * @return int Aliens encountered
     */
    public function getEncountered() {
        return $this->encountered;
    }

    /**
     * Returns the gold medals won by this player
     *
     * @return int Gold medals won
     */
    public function getGoldMedals() {
        return $this->goldMedals;
    }

    /**
     * Returns the heat damage done by this player
     *
     * @return float Heat damage done
     */
    public function getHeatDamage() {
        return $this->heatDamage;
    }

    /**
     * Returns the interest gained by the player
     *
     * @return int Interest gained
     */
    public function getInterest() {
        return $this->interest;
    }

    /**
     * Returns the aliens killed by the player
     *
     * @return int Aliens killed
     */
    public function getKilled() {
        return $this->killed;
    }

    /**
     * Returns the aliens killed during the campaign by the player
     *
     * @return int Aliens killed during the campaign
     */
    public function getKilledCampaign() {
        return $this->killedCampaign;
    }

    /**
     * Returns the aliens killed during challenges by the player
     *
     * @return int Aliens killed during challenges
     */
    public function getKilledChallenge() {
        return $this->killedChallenge;
    }

    /**
     * Returns the number of levels played by the player
     *
     * @return int Number of levels played
     */
    public function getLevelsPlayed() {
        return $this->levelsPlayed;
    }

    /**
     * Returns the number of levels played during the campaign by the player
     *
     * @return int Number of levels played during the campaign
     */
    public function getLevelsPlayedCampaign() {
        return $this->levelsPlayedCampaign;
    }

    /**
     * Returns the number of levels played during challenges by the player
     *
     * @return int Number of levels played during challenges
     */
    public function getLevelsPlayedChallenge() {
        return $this->levelsPlayedChallenge;
    }

    /**
     * Returns the number of levels won by the player
     *
     * @return int Number of levels won
     */
    public function getLevelsWon() {
        return $this->levelsWon;
    }

    /**
     * Returns the number of levels won during the campaign by the player
     *
     * @return int Number of levels during the campaign won
     */
    public function getLevelsWonCampaign() {
        return $this->levelsWonCampaign;
    }

    /**
     * Returns the number of levels won during challenges by the player
     *
     * @return int Number of levels during challenges won
     */
    public function getLevelsWonChallenge() {
        return $this->levelsWonChallenge;
    }

    /**
     * Returns the damage dealt by the orbital laser
     *
     * @return float Damage dealt by the orbital laser
     */
    public function getOrbitalLaserDamage() {
        return $this->orbitalLaserDamage;
    }

    /**
     * Returns the number of times the orbital lasers has been fired by the
     * player
     *
     * @return int Number of times the orbital laser has been fired
     */
    public function getOrbitalLaserFired() {
        return $this->orbitalLaserFired;
    }

    /**
     * Returns the amount of resources harvested by the player
     *
     * @return int Resources harvested by the player
     */
    public function getResources() {
        return $this->resources;
    }

    /**
     * Returns the silver medals won by this player
     *
     * @return int Silver medals won
     */
    public function getSilverMedals() {
        return $this->silverMedals;
    }

    /**
     * Returns the time played in seconds by the player
     *
     * @return float Time played
     */
    public function getTimePlayed() {
        return $this->timePlayed;
    }

    /**
     * Returns stats about the towers built by the player
     *
     * The array returned uses the names of the towers as keys. Every value of
     * the array is another array using the keys 1 to 3 for different tower
     * levels.
     * The values of these arrays is an array containing the number of towers
     * built as the first element and the damage dealt by this specific tower
     * type as the second element.
     *
     * The Command tower uses the resources gained as second element.
     * The Temporal tower doesn't have a second element.
     *
     * @return array Stats about the towers built
     */
    public function getTowerStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->towerStats)) {
            $towerData = $this->xmlData->stats->towers;
            $this->towerStats = array();
            $towers = array('cannon', 'flak', 'gun', 'inferno', 'laser',
                'meteor', 'missile', 'tesla');

            foreach($towers as $tower) {
                $this->towerStats[$tower] = array();
                for($i = 1; $i <= 3; $i++) {
                    $built = $towerData->xpath("{$tower}[@level=$i]/built/value");
                    $damage = $towerData->xpath("{$tower}[@level=$i]/damage/value");
                    $this->towerStats[$tower][$i] = array(
                        (int) $built[0],
                        (float) $damage[0]
                    );
                }
            }

            $this->towerStats['command'] = array();
            for($i = 1; $i <= 3; $i++) {
                $built = $towerData->xpath("command[@level=$i]/built/value");
                $resources = $towerData->xpath("command[@level=$i]/resource/value");
                $this->towerStats['command'][$i] = array(
                    (int) $built[0],
                    (float) $resources[0]
                );
            }

            $this->towerStats['temporal'] = array();
            for($i = 1; $i <= 3; $i++) {
                $built = $towerData->xpath("temporal[@level=$i]/built/value");
                $this->towerStats['temporal'][$i] = array(
                    (int) $built[0]
                );
            }
        }

        return $this->towerStats;
    }
}
