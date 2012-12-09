<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011, Nicholas Hastings
 *               2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * The GameLeaderboard class represents a single entry in a leaderboard
 *
 * @author     Nicholas Hastings
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class GameLeaderboardEntry {

    /**
     * @var SteamId
     */
    protected $steamId;

    /**
     * @var int
     */
    protected $score;

    /**
     * @var int
     */
    protected $rank;

    /**
     * @var GameLeaderboard
     */
    protected $leaderboard;

    /**
     * Creates new entry instance for the given XML data and leaderboard
     *
     * @param SimpleXMLElement $entryData The XML data of the leaderboard of
     *        the leaderboard entry
     * @param GameLeaderboard $leaderboard The leaderboard this entry belongs
     *        to
     */
    public function __construct(SimpleXMLElement $entryData, GameLeaderboard $leaderboard) {
        $this->steamId     = SteamId::create((string) $entryData->steamid, false);
        $this->score       = (int)    $entryData->score;
        $this->rank        = (int)    $entryData->rank;
        $this->leaderboard = $leaderboard;
    }

    /**
     * Returns the Steam ID of this entry's player
     *
     * @return SteamId The Steam ID of the player
     */
    public function getSteamId() {
        return $this->steamId;
    }

    /**
     * Returns the score of this entry
     *
     * @return int The score of this player
     */
    public function getScore() {
        return $this->score;
    }

    /**
     * Returns the rank where this entry is listed in the leaderboard
     *
     * @return int The rank of this entry
     */
    public function getRank() {
        return $this->rank;
    }

    /**
     * Returns the leaderboard this entry belongs to
     *
     * @return GameLeaderboard The leaderboard of this entry
     */
    public function getLeaderboard() {
        return $this->leaderboard;
    }
}
