<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/GameItem.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/WebApi.php';

/**
 * Provides basic functionality to represent an inventory of player in a game
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
abstract class GameInventory {

    /**
     * @var array
     */
    private static $attributeSchemas = array();

    /**
     * @var array
     */
    public static $cache = array();

    /**
     * @var array
     */
    private static $itemSchemas = array();

    /**
     * @var array
     */
    private static $qualitySchemas = array();

    /**
     * @var string
     */
    private static $schemaLanguage = 'en';

    /**
     * @var int
     */
    private $fetchDate;

    /**
     * @var array
     */
    private $items;

    /**
     * @var string
     */
    protected $steamId64;

    /**
     * Creates a new inventory object for the given SteamID64. This calls
     * fetch() to update the data and create the TF2Item instances contained in
     * this players backpack
     *
     * @param string $steamId64 The 64bit Steam ID of the user
     * @param bool $fetchNow Whether the data should be fetched now
     */
    public function __construct($steamId64, $fetchNow = true) {
        $this->steamId64 = $steamId64;

        if($fetchNow) {
            $this->fetch();
        }

        $this->cache();
    }

    /**
     * Saves this inventory in the cache
     */
    public function cache() {
        $inventoryClass = get_class($this);
        if(!array_key_exists($this->steamId64, self::$cache[$inventoryClass])) {
            self::$cache[$inventoryClass][$this->steamId64] = $this;
        }
    }

    /**
     * Updates the contents of the backpack using Steam Web API
     */
    public function fetch() {
        $appId = $this->getAppId();
        $inventoryClass = new ReflectionClass(get_class($this));
        $itemClass = $inventoryClass->getConstant('ITEM_CLASS');
        $result = WebApi::getJSONData('IEconItems_' . $appId, 'GetPlayerItems', 1, array('SteamID' => $this->steamId64));

        $this->items = array();
        foreach($result->items as $itemData) {
            if($itemData != null) {
                $item = new $itemClass($this, $itemData);
                $this->items[$item->getBackpackPosition() - 1] = $item;
            }
        }

        $this->fetchDate = time();
    }

    /**
     * Returns the application ID of the game this inventory belongs to
     *
     * @return int The application ID of the game this inventory belongs to
     */
    private function getAppId() {
        $inventoryClass = new ReflectionClass(get_class($this));
        return $inventoryClass->getConstant('APP_ID');
    }

    /**
     * Returns the attribute schema
     *
     * The attribute schema is fetched first if not done already
     *
     * @return stdClass The attribute schema for the game this inventory
     *         belongs to
     * @see updateSchema()
     * @throws WebApiException on Web API errors
     */
    public function getAttributeSchema() {
        if(!array_key_exists($this->getAppId(), self::$attributeSchemas)) {
            $this->updateSchema();
        }

        return self::$attributeSchemas[$this->getAppId()];
    }

    /**
     * Returns the item at the given position in the backpack. The positions
     * range from 1 to 100 instead of the usual array indices (0 to 99).
     *
     * @param int $index The position of the item in the backpack
     * @return GameItem The item at the given position
     */
    public function getItem($index) {
        return $this->items[$index - 1];
    }

    /**
     * Returns the item schema
     *
     * The item schema is fetched first if not done already
     *
     * @return stdClass The item schema for the game this inventory belongs to
     * @see updateSchema()
     * @throws WebApiException on Web API errors
     */
    public function getItemSchema() {
        if(!array_key_exists($this->getAppId(), self::$itemSchemas)) {
            $this->updateSchema();
        }

        return self::$itemSchemas[$this->getAppId()];
    }

    /**
     * Returns an array of all items in this players inventory.
     *
     * @return array All items in the backpack
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * Returns the item quality schema
     *
     * The item schema is fetched first if not done already
     *
     * @return stdClass The item quality schema for the game this inventory
     *         belongs to
     * @see updateSchema()
     * @throws WebApiException on Web API errors
     */
    public function getQualitySchema() {
        if(!array_key_exists($this->getAppId(), self::$qualitySchemas)) {
            $this->updateSchema();
        }

        return self::$qualitySchemas[$this->getAppId()];
    }

    /**
     * Returns the 64bit SteamID of the player owning this inventory
     *
     * @return string The 64bit SteamID
     */
    public function getSteamId64() {
        return $this->steamId64;
    }

    /**
     * Returns whether the items contained in this inventory have been already
     * fetched
     *
     * @return bool Whether the contents backpack have been fetched
     */
    public function isFetched() {
        return !empty($this->fetchDate);
    }

    /**
     * Returns the number of items in the user's backpack
     *
     * @return int The number of items in the backpack
     */
    public function size() {
        return sizeof($this->items);
    }

    /**
     * Updates the item schema (this includes attributes and qualities) using
     * the "GetSchema" method of interface "IEconItems_{AppId}"
     *
     * @throws WebApiException on Web API errors
     */
    protected function updateSchema() {
        $params = array();
        if(self::$schemaLanguage != null) {
            $params['language'] = self::$schemaLanguage;
        }
        $result = WebApi::getJSONData('IEconItems_' . $this->getAppId(), 'GetSchema', 1, $params);

        self::$attributeSchemas[$this->getAppId()] = array();
        foreach($result->attributes as $attributeData) {
          self::$attributeSchemas[$this->getAppId()][$attributeData->name] = $attributeData;
        }

        self::$itemSchemas[$this->getAppId()] = array();
        foreach($result->items as $itemData) {
          self::$itemSchemas[$this->getAppId()][$itemData->defindex] = $itemData;
        }

        self::$qualitySchemas[$this->getAppId()] = array();
        foreach($result->qualities as $quality => $id) {
          self::$qualitySchemas[$this->getAppId()][$id] = $quality;
        }
    }

}
