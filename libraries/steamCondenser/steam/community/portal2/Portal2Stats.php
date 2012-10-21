<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/GameStats.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/portal2/Portal2Inventory.php';

/**
 * This class represents the game statistics for a single user in Portal 2
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class Portal2Stats extends GameStats {

    /**
     * @var Portal2Inventory
     */
    private $inventory;

    /**
     * Creates a <var>Portal2Stats</var> object by calling the super
     * constructor with the game name <var>"portal2"</var>
     *
     * @param string $steamId The custom URL or 64bit Steam ID of the user
     */
    public function __construct($steamId) {
        parent::__construct($steamId, 'portal2');
    }

    /**
     * Returns the current Portal 2 inventory (a.k.a. Robot Enrichment) of this
     * player
     *
     * @return Portal2Inventory This player's Portal 2 backpack
     */
    public function getInventory() {
        if(!$this->isPublic()) {
            return null;
        }

        if(empty($this->inventory)) {
            $this->inventory = Portal2Inventory::create($this->user->getSteamId64());
        }

        return $this->inventory;
    }
}
