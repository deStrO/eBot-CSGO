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
 * Represents the stats for the Team Fortress 2 Engineer class for a specific
 * user
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class TF2Engineer extends TF2Class {

    /**
     * @var int
     */
    private $maxBuildingsBuilt;

    /**
     * @var int
     */
    private $maxSentryKills;

    /**
     * @var int
     */
    private $maxTeleports;

    /**
     * Creates a new instance of the Engineer class based on the given XML data
     *
     * @param SimpleXMLElement $classData The XML data for this Engineer
     */
    public function __construct(SimpleXMLElement $classData) {
        parent::__construct($classData);

        $this->maxBuildingsBuilt = (int) $classData->ibuildingsbuilt;
        $this->maxTeleports      = (int) $classData->inumteleports;
        $this->maxSentryKills    = (int) $classData->isentrykills;
    }

    /**
     * Returns the maximum number of buildings built by the player in a single
     * life as an Engineer
     *
     * @return int Maximum number of buildings built
     */
    public function getMaxBuildingsBuilt() {
        return $this->maxBuildingsBuilt;
    }

    /**
     * Returns the maximum number of enemies killed by sentry guns built by the
     * player in a single life as an Engineer
     *
     * @return int Maximum number of sentry kills
     */
    public function getMaxSentryKills() {
        return $this->maxSentryKills;
    }

    /**
     * Returns the maximum number of teammates teleported by teleporters built
     * by the player in a single life as an Engineer
     *
     * @return int Maximum number of teleports
     */
    public function getMaxTeleports() {
        return $this->maxTeleports;
    }

}
