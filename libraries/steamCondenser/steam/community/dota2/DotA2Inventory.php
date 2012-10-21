<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/GameInventory.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/dota2/DotA2Item.php';

GameInventory::$cache['DotA2Inventory'] = array();

/**
 * Represents the inventory of a DotA 2 player
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class DotA2Inventory extends GameInventory {

    const APP_ID = 570;

    const ITEM_CLASS = 'DotA2Item';

    /**
     * Clears the inventory cache
     */
    public static function clearCache() {
        parent::$cache['DotA2Inventory'] = array();
    }

    /**
     * This checks the cache for an existing inventory. If it exists it is
     * returned. Otherwise a new inventory is created.
     *
     * @param string $steamId64 The 64bit Steam ID of the user
     * @param bool $fetchNow Whether the data should be fetched now
     * @param bool $bypassCache Whether the cache should be bypassed
     * @return DotA2Inventory The inventory created from the given options
     */
    public static function create($steamId64, $fetchNow = true, $bypassCache = false) {
        if(self::isCached($steamId64) && !$bypassCache) {
            $inventory = parent::$cache['DotA2Inventory'][$steamId64];
            if($fetchNow && !$inventory->isFetched()) {
                $inventory->fetch();
            }

            return $inventory;
        } else {
            return new DotA2Inventory($steamId64, $fetchNow);
        }
    }

    /**
     * Returns whether the requested inventory is already cached
     *
     * @param string $steamId64 The 64bit Steam ID of the user
     * @return bool Whether the inventory of the given user is already cached
     */
    public static function isCached($steamId64) {
        return array_key_exists($steamId64, parent::$cache['DotA2Inventory']);
    }

}
