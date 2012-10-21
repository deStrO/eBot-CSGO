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
require_once STEAM_CONDENSER_PATH . 'steam/community/l4d/L4DExplosive.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/l4d/L4DMap.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/l4d/L4DWeapon.php';

/**
 * This class represents the game statistics for a single user in Left4Dead
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class L4DStats extends AbstractL4DStats {

    /**
     * Creates a <var>L4DStats</var> object by calling the super constructor
     * with the game name <var>"l4d"</var>
     *
     * @param string $steamId The custom URL or 64bit Steam ID of the user
     */
    public function __construct($steamId) {
        parent::__construct($steamId, 'l4d');
    }

    /**
     * Returns an array of Survival statistics for this user like revived
     * teammates
     *
     * If the Survival statistics haven't been parsed already, parsing is done
     * now.
     *
     * @return array The stats for the Survival mode
     */
    public function getSurvivalStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->survivalStats)) {
            parent::getSurvivalStats();
            $this->survivalStats['maps'] = array();
            foreach($this->xmlData->stats->survival->maps->children() as $mapData) {
                $this->survivalStats['maps'][$mapData->getName()] = new L4DMap($mapData);
            }
        }

        return $this->survivalStats;
    }

    /**
     * Returns an array of <var>L4DWeapon</var> for this user containing all
     * Left4Dead weapons
     *
     * If the weapons haven't been parsed already, parsing is done now.
     *
     * @return array The weapon statistics
     */
    public function getWeaponStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->weaponStats)) {
          $this->weaponStats = array();
          foreach($this->xmlData->stats->weapons->children() as $weaponData) {
            $weaponName = $weaponData->getName();
            if($weaponName != 'molotov' && $weaponName != 'pipes') {
              $weapon = new L4DWeapon($weaponData);
            }
            else {
              $weapon = new L4DExplosive($weaponData);
            }

            $this->weaponStats[$weaponName] = $weapon;
          }
        }

        return $this->weaponStats;
    }
}
