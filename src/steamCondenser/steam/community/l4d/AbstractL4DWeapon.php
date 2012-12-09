<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/GameWeapon.php';

/**
 * This abstract class is a base class for weapons in Left4Dead and Left4Dead 2
 * as the weapon stats for both games are very similar
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
abstract class AbstractL4DWeapon extends GameWeapon {

    /**
     * @var string
     */
    protected $accuracy;

    /**
     * @var string
     */
    protected $headshotsPercentage;

    /**
     * @var string
     */
    protected $killPercentage;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $shots;

    /**
     * Creates a new instance of weapon from the given XML data and parses
     * common data for both, <var>L4DWeapon</var> and <var>L4D2Weapon</var>
     *
     * @param SimpleXMLElement $weaponData The XML data for this weapon
     */
    public function __construct(SimpleXMLElement $weaponData) {
        parent::__construct($weaponData);

        $this->accuracy            = ((float) $weaponData->accuracy) * 0.01;
        $this->headshotsPercentage = ((float) $weaponData->headshots) * 0.01;
        $this->id                  = $weaponData->getName();
        $this->shots               = (int)    $weaponData->shots;
    }

    /**
     * Returns the overall accuracy of the player with this weapon
     *
     * @return string The accuracy of the player with this weapon
     */
    public function getAccuracy() {
        return $this->accuracy;
    }

    /**
     * Returns the percentage of kills with this weapon that have been
     * headshots
     *
     * @return string The percentage of headshots with this weapon
     */
    public function getHeadshotsPercentage() {
        return $this->headshotsPercentage;
    }

    /**
     * Returns the ID of the weapon
     *
     * @return string The ID of the weapon
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the percentage of overall kills of the player that have been
     * achieved with this weapon
     *
     * @return string The percentage of kills with this weapon
     */
    public function getKillPercentage() {
        return $this->killPercentage;
    }

    /**
     * Returns the number of shots the player has fired with this weapon
     *
     * @return int The number of shots with this weapon
     */
    public function getShots() {
        return $this->shots;
    }
}
