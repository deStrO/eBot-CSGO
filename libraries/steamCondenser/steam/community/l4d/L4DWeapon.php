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
 * Left4Dead
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class L4DWeapon extends AbstractL4DWeapon {

    /**
     * Creates a new instance of a weapon based on the given XML data
     *
     * @param SimpleXMLElement $weaponData The XML data for this weapon
     */
    public function __construct(SimpleXMLElement $weaponData) {
        parent::__construct($weaponData);

        $this->killPercentage = ((float) $weaponData->killpct) * 0.01;
    }
}
