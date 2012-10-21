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

/**
 * Provides basic functionality to represent an item in a game
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
abstract class GameItem {

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var int
     */
    private $backpackPosition;

    /**
     * @var string
     */
    private $className;

    /**
     * @var int
     */
    private $count;

    /**
     * @var int
     */
    private $defindex;

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $level;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $quality;

    /**
     * @var string
     */
    private $slot;

    /**
     * @var bool
     */
    private $tradeable;

    /**
     * @var string
     */
    private $type;

    /**
     * Creates a new instance of a GameItem with the given data
     *
     * @param GameInventory $inventory The inventory this item is contained in
     * @param array $itemData The data specifying this item
     * @throws WebApiException on Web API errors
     */
    public function __construct(GameInventory $inventory, $itemData) {
        $itemSchema    = $inventory->getItemSchema();
        $qualitySchema = $inventory->getQualitySchema();

        $this->defindex         = $itemData->defindex;
        $this->backpackPosition = $itemData->inventory & 0xffff;
        $this->className        = $itemSchema[$this->defindex]->item_class;
        $this->count            = $itemData->quantity;
        $this->id               = $itemData->id;
        $this->level            = $itemData->level;
        $this->name             = $itemSchema[$this->defindex]->item_name;
        $this->quality          = $qualitySchema[$itemData->quality];
        $this->slot             = $itemSchema[$this->defindex]->item_slot;
        $this->tradeable        = !($itemData->flag_cannot_trade == true);
        $this->type             = $itemSchema[$this->defindex]->item_type_name;

        if(@$itemSchema[$this->defindex]->attributes != null) {
            $this->attributes = $itemSchema[$this->defindex]->attributes;
        }
    }

    /**
     * Return the attributes of this item
     *
     * @return array The attributes of this item
     */
    public function getAttributes() {
        return $this->attributes;
    }

    /**
     * Returns the position of this item in the player's inventory
     *
     * @return int The position of this item in the player's inventory
     */
    public function getBackpackPosition() {
        return $this->backpackPosition;
    }

    /**
     * Returns the class of this item
     *
     * @return string The class of this item
     */
    public function getClassName() {
        return $this->className;
    }

    /**
     * Returns the number of items the player owns of this item
     *
     * @return int The quanitity of this item
     */
    public function getCount() {
        return $this->count;
    }

    /**
     * Returns the index where the item is defined in the schema
     *
     * @return int The schema index of this item
     */
    public function getDefIndex() {
        return $this->defindex;
    }

    /**
     * Returns the ID of this item
     *
     * @return int The ID of this item
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the level of this item
     *
     * @return int The level of this item
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * Returns the level of this item
     *
     * @return string The level of this item
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the quality of this item
     *
     * @return string The quality of this item
     */
    public function getQuality() {
        return $this->quality;
    }

    /**
     * Returns the slot where this item can be equipped in or <var>null</var>
     * if this item cannot be equipped
     *
     * @return string The slot where this item can be equipped in
     */
    public function getSlot() {
        return $this->slot;
    }

    /**
     * Returns the type of this item
     *
     * @return string The type of this item
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Returns whether this item is tradeable
     *
     * @return bool Whether this item is tradeable
     */
    public function isTradeable() {
        return $this->tradeable;
    }

}
