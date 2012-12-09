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
 * This class represents the statistics of a single explosive weapon for a user
 * in Left4Dead
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class L4DExplosive extends GameWeapon {

    /**
     * Creates a new instance of an explosivve based on the given XML data
     *
     * @param SimpleXMLElement $weaponData The XML data of this explosive
     */
    public function __construct(SimpleXMLElement $weaponData) {
        parent::__construct($weaponData);

        $this->id    = $weaponData->getName();
        $this->shots = (int) $weaponData->thrown;
    }

    /**
     * Returns the average number of killed zombies for one shot of this
     * explosive
     *
     * @return float The average number of kills per shot
     */
    public function getAvgKillsPerShot() {
        return 1 / $this->getAvgShotsPerKill();
    }
}
