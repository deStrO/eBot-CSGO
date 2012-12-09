<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/SteamId.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/l4d/L4DMap.php';

/**
 * This class holds statistical information about a map played by a player in
 * Survival mode of Left4Dead 2
 *
 * The basic information provided is more or less the same for Left4Dead and
 * Left4Dead 2, but parsing has to be done differently.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class L4D2Map extends L4DMap {

    /**
     * @var array The names of the special infected in Left4Dead 2
     */
    protected static $SPECIAL_INFECTED = array('boomer', 'charger', 'hunter', 'jockey', 'smoker', 'spitter', 'tank');

    /**
     * @var array The items available in Left4Dead 2
     */
    private static $ITEMS = array('adrenaline', 'defibs', 'medkits', 'pills');

    /**
     * @var array
     */
    private $items;

    /**
     * @var array
     */
    private $kills;

    /**
     * @var bool
     */
    private $played;

    /**
     * @var array
     */
    private $teammates;

    /**
     * Creates a new instance of a map based on the given XML data
     *
     * The map statistics for the Survival mode of Left4Dead 2 hold much more
     * information than those for Left4Dead, e.g. the teammates and items are
     * listed.
     *
     * @param SimpleXMLElement $mapData The XML data for this map
     */
    public function __construct(SimpleXMLElement $mapData) {
        $this->bestTime    = (float)  $mapData->besttimeseconds;
        preg_match('#http://steamcommunity.com/public/images/gamestats/550/(.*)\.jpg#', (string) $mapData->img, $id);
        $this->id          = $id[1];
        $this->name        = (string) $mapData->name;
        $this->played      = ((int)   $mapData->hasPlayed == 1);

        if($this->played) {
            $this->bestTime = (float) $mapData->besttimemilliseconds / 1000;

            $this->teammates = array();
            foreach($mapData->teammates->children() as $teammate) {
                $this->teammates[] = new SteamId((string) $teammate, false);
            }

            $this->items = array();
            foreach(self::$ITEMS as $item) {
                $this->items[$item] = (int) $mapData->{"item_$item"};
            }

            $this->kills = array();
            foreach(self::$INFECTED as $infected) {
                $this->kills[$infected] = (int) $mapData->{"kills_$infected"};
            }

            switch((string) $mapData->medal) {
                case 'gold':
                    $this->medal = self::GOLD;
                    break;
                case 'silver':
                    $this->medal = self::SILVER;
                    break;
                case 'bronze':
                    $this->medal = self::BRONZE;
                    break;
                default:
                    $this->medal = self::NONE;
            }
        }
    }

    /**
     * Returns statistics about the items used by the player on this map
     *
     * @return array The items used by the player
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * Returns the number of special infected killed by the player grouped by
     * the names of the special infected
     *
     * @return array The special infected killed by the player
     */
    public function getKills() {
        return $this->kills;
    }

    /**
     * Returns the SteamIDs of the teammates of the player in his best game on
     * this map
     *
     * @return array The SteamIDs of the teammates in the best game
     */
    public function getTeammates() {
        return $this->teammates;
    }

    /**
     * Returns whether the player has already played this map
     *
     * @return bool <var>true</var> if the player has already played this map
     */
    public function isPlayed() {
        return $this->played;
    }

}
