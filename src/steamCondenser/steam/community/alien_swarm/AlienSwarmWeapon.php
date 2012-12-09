<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/GameWeapon.php';

/**
 * This class holds statistical information about weapons used by a player
 * in Alien Swarm
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class AlienSwarmWeapon extends GameWeapon {

    private $accuracy;

    private $damage;

    private $friendlyFire;

    private $name;

    /**
     * Creates a new weapon instance based on the assigned weapon XML data
     *
     * @param SimpleXMLElement $weaponData The data representing this weapon
     */
    public function __construct(SimpleXMLElement $weaponData) {
        parent::__construct($weaponData);

        $this->accuracy     = (float) $weaponData->accuracy;
        $this->damage       = (int) $weaponData->damage;
        $this->friendlyFire = (int) $weaponData->friendlyfire;
        $this->name         = (string) $weaponData->name;
        $this->shots        = (int) $weaponData->shotsfired;
    }

    /**
     * Returns the accuracy of the player with this weapon
     *
     * @return The accuracy of the player with this weapon
     */
    public function getAccuracy() {
        return $this->accuracy;
    }

    /**
     * Returns the damage achieved with this weapon
     *
     * @return The damage achieved with this weapon
     */
    public function getDamage() {
        return $this->damage;
    }

    /**
     * Returns the damage dealt to team mates with this weapon
     *
     * @return The damage dealt to team mates with this weapon
     */
    public function getFriendlyFire() {
        return $this->friendlyFire;
    }

    /**
     * Returns the name of this weapon
     *
     * @return The name of this weapon
     */
    public function getName() {
        return $this->name;
    }

}
