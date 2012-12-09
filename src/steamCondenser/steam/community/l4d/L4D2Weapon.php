<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/l4d/AbstractL4DWeapon.php';

/**
 * This class represents the statistics of a single weapon for a user in
 * Left4Dead 2
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class L4D2Weapon extends AbstractL4DWeapon {

    /**
     * @var int
     */
    private $damage;

    /**
     * @var string
     */
    private $weaponGroup;

    /**
     * Creates a new instance of a weapon based on the given XML data
     *
     * @param SimpleXMLElement $weaponData The XML data of this weapon
     */
    public function __construct(SimpleXMLElement $weaponData) {
        parent::__construct($weaponData);

        $this->damage         = (int)    $weaponData->damage;
        $this->killPercentage = ((float) $weaponData->pctkills) * 0.01;
        $this->weaponGroup    = $weaponData['group'];

    }

    /**
     * Returns the amount of damage done by the player with this weapon
     *
     * @return int The damage done by this weapon
     */
    public function getDamage() {
        return $this->damage;
    }

    /**
     * Returns the weapon group this weapon belongs to
     *
     * @return string The group this weapon belongs to
     */
    public function getWeaponGroup() {
        return $this->weaponGroup;
    }
}
