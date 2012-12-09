<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2Class.php';

/**
 * Represents the stats for the Team Fortress 2 Medic class for a specific user
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class TF2Medic extends TF2Class {

    /**
     * @var int
     */
     private $maxHealthHealed;

    /**
     * @var int
     */
     private $maxUberCharges;

    /**
     * Creates a new instance of the Medic class based on the given XML data
     *
     * @param SimpleXMLElement $classData The XML data for this Medic
     */
    public function __construct(SimpleXMLElement $classData) {
        parent::__construct($classData);

        $this->maxUberCharges    = (int) $classData->inuminvulnerable;
        $this->maxHealthHealed   = (int) $classData->ihealthpointshealed;
    }

    /**
     * Returns the maximum health healed for teammates by the player in a
     * single life as a Medic
     *
     * @return Maximum health healed
     */
    public function getMaxHealthHealed() {
        return $this->maxHealthHealed;
    }

    /**
     * Returns the maximum number of ÜberCharges provided by the player in a
     * single life as a Medic
     *
     * @return Maximum number of ÜberCharges
     */
    public function getMaxUbercharges() {
        return $this->maxUbercharges;
    }
}
