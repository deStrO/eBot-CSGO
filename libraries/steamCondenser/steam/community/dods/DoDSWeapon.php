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
 * Represents the stats for a Day of Defeat: Source weapon for a specific user
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class DoDSWeapon extends GameWeapon {

    /**
     * @var int
     */
    private $headshots;

    /**
     * @var int
     */
    private $hits;

    /**
     * @var string
     */
    private $name;

    /**
     * Creates a new instance of a Day of Defeat: Source weapon based on the
     * given XML data
     *
     * @param SimpleXMLElement $weaponData The XML data of the class
     */
    public function __construct(SimpleXMLElement $weaponData) {
        parent::__construct($weaponData);

        $this->headshots = (int)    $weaponData->headshots;
        $this->id        = (string) $weaponData['key'];
        $this->name      = (string) $weaponData->name;
        $this->shots     = (int)    $weaponData->shotsfired;
        $this->hits      = (int)    $weaponData->shotshit;
    }

    /**
     * Returns the average number of hits needed for a kill with this weapon
     *
     * @return float The average number of hits needed for a kill
     */
    public function getAvgHitsPerKill() {
        return $this->hits / $this->kills;
    }

    /**
     * Returns the percentage of headshots relative to the shots hit with this
     * weapon
     *
     * @return float The percentage of headshots
     */
    public function getHeadshotPercentage() {
        return $this->headshots / $this->hits;
    }

    /**
     * Returns the number of headshots achieved with this weapon
     *
     * @return int The number of headshots achieved
     */
    public function getHeadshots() {
        return $this->headshots;
    }

    /**
     * Returns the percentage of hits relative to the shots fired with this
     * weapon
     *
     * @return float The percentage of hits
     */
    public function getHitPercentage() {
        return$this->hits / $this->shots;
    }

    /**
     * Returns the number of hits achieved with this weapon
     *
     * @return int The number of hits achieved
     */
    public function getHits() {
        return $this->hits;
    }

    /**
     * Returns the name of this weapon
     *
     * @return string The name of this weapon
     */
    public function getName() {
        return $this->name;
    }
}
