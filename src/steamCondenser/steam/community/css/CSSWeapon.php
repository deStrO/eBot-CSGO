<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * Represents the stats for a Counter-Strike: Source weapon for a specific user
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class CSSWeapon {

    /**
     * @var bool
     */
    private $favorite;

    /**
     * @var int
     */
    private $hits;

    /**
     * @var int
     */
    private $kills;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $shots;

    /**
     * Creates a new instance of a Counter-Strike: Source weapon based on the
     * given XML data
     *
     * @param string $weaponName The name of the weapon
     * @param SimpleXMLElement $weaponsData The XML data of all weapons
     */
    public function __construct($weaponName, SimpleXMLElement $weaponsData) {
        $this->name = $weaponName;

        $this->favorite = ((string) $weaponsData->favorite) == $this->name;
        $this->kills    = (int) $weaponsData->{"{$this->name}_kills"};

        if($this->name != 'grenade' && $this->name != 'knife') {
            $this->hits  = (int) $weaponsData->{"{$this->name}_hits"};
            $this->shots = (int) $weaponsData->{"{$this->name}_shots"};
        }
    }

    /**
     * Returns whether this weapon is the favorite weapon of this player
     *
     * @return bool <var>true</var> if this is the favorite weapon
     */
    public function isFavorite() {
        return $this->favorite;
    }

    /**
     * Returns the accuracy of this player with this weapon
     *
     * @return float The accuracy with this weapon
     */
    public function getAccuracy() {
        return ($this->shots > 0) ? $this->hits / $this->shots : 0;
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
     * Returns the number of kills achieved with this weapon
     *
     * @return int The number of kills achieved
     */
    public function getKills() {
        return $this->kills;
    }

    /**
     * Returns the kill-shot-ratio of this player with this weapon
     *
     * @return float The kill-shot-ratio
     */
    public function getKsRatio() {
        return ($this->shots > 0) ? $this->kills / $this->shots : 0;
    }

    /**
     * Returns the name of this weapon
     *
     * @return string The name of this weapon
     */
    public function getName() {
        return $this->name;
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
