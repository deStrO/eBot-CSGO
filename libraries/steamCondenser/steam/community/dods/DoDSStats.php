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
require_once STEAM_CONDENSER_PATH . 'steam/community/dods/DoDSClass.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/dods/DoDSWeapon.php';

/**
 * The is class represents the game statistics for a single user in Day of
 * Defeat: Source
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class DoDSStats extends GameStats {

    private $classStats;

    private $weaponStats;

    /**
     * Creates a <var>DoDSStats</var> instance by calling the super constructor
     * with the game name <var>"DoD:S"</var>
     *
     * @param string $steamId The custom URL or 64bit Steam ID of the user
     */
    public function __construct($steamId) {
        parent::__construct($steamId, 'DoD:S');
    }

    /**
     * Returns an array of <var>DoDSClass</var> for this user containing all
     * DoD:S classes.
     *
     * If the classes haven't been parsed already, parsing is done now.
     *
     * @return array The class statistics for this user
     */
    public function getClassStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->classStats)) {
            $this->classStats = array();
            foreach($this->xmlData->stats->classes->children() as $classData) {
                $this->classStats[(string) $classData['key']] = new DoDSClass($classData);
            }
        }

        return $this->classStats;
    }

    /**
     * Returns an array of <var>DoDSWeapon</var> for this user containing all
     * DoD:S weapons.
     *
     * If the weapons haven't been parsed already, parsing is done now.
     *
     * @return array The weapon statistics for this user
     */
    public function getWeaponStats() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->weaponStats)) {
            foreach($this->xmlData->stats->weapons->children() as $classData) {
                $this->weaponStats[(string) $classData['key']] = new DoDSWeapon($classData);
            }
        }

        return $this->weaponStats;
    }
}
