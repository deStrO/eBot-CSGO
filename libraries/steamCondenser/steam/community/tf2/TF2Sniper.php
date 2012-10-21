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
 * Represents the stats for the Team Fortress 2 Sniper class for a specific
 * user
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class TF2Sniper extends TF2Class {

    /**
     * @var int
     */
     private $maxHeadshots;

    /**
     * Creates a new instance of the Sniper class based on the given XML data
     *
     * @param SimpleXMLElement $classData The XML data for this Sniper
     */
    public function __construct(SimpleXMLElement $classData) {
        parent::__construct($classData);

        $this->maxHeadshots = (int) $classData->iheadshots;
    }

    /**
     * Returns the maximum number enemies killed with a headshot by the player
     * in single life as a Sniper
     *
     * @return int Maximum number of headshots
     */
    public function getMaxHeadshots() {
        return $this->maxHeadshots;
    }
}
