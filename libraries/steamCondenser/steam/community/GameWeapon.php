<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * An abstract class implementing basic functionality for classes representing
 * game weapons
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
abstract class GameWeapon {

    protected $kills;

    protected $id;

    protected $shots;

    /**
     * Creates a new game weapon instance with the data provided
     *
     * @param SimpleXMLElement $weaponData The data representing this weapon
     */
    public function __construct(SimpleXMLElement $weaponData) {
        $this->kills = (int) $weaponData->kills;
    }

    /**
     * Returns the average number of shots needed for a kill with this weapon
     *
     * @return float The average number of shots needed for a kill
     */
    public function getAvgShotsPerKill() {
        return $this->shots / $this->kills;
    }

    /**
     * Returns the unique identifier for this weapon
     *
     * @return int The identifier of this weapon
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the number of kills achieved with this weapon
     *
     * @return int The number of kills achieved
     */
    public function getKills() {
        return $this->kills;
    }

    /**
     * Returns the number of shots fired with this weapon
     *
     * @return int The number of shots fired
     */
    public function getShots() {
        return $this->shots;
    }

}
