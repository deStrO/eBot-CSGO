<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/SteamId.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/XMLData.php';

/**
 * The SteamGroup class represents a group in the Steam Community
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class SteamGroup extends XMLData {

    /**
     * @var array
     */
    private static $steamGroups = array();

    /**
     * @var String
     */
    private $customUrl;

    /**
     * @var int
     */
    private $fetchTime;

    /**
     * @var int
     */
    private $groupId64;

    /**
     * @var array
     */
    private $members;

    /**
     * Returns whether the requested group is already cached
     *
     * @param string $id The custom URL of the group specified by the group
     *        admin or the 64bit group ID
     * @return bool <var>true</var> if this group is already cached
     */
    public static function isCached($id) {
        return array_key_exists(strtolower($id), self::$steamGroups);
    }

    /**
     * Clears the group cache
     */
    public static function clearCache() {
        self::$steamGroups = array();
    }

    /**
     * Creates a new <var>SteamGroup</var> instance or gets an existing one
     * from the cache for the group with the given ID
     *
     * @param string $id The custom URL of the group specified by the group
     *        admin or the 64bit group ID
     * @param bool $fetch if <var>true</var> the groups's data is loaded into
     *        the object
     * @param bool $bypassCache If <var>true</var> an already cached instance
     *        for this group will be ignored and a new one will be created
     * @return SteamGroup The <var>SteamGroup</var> instance of the requested
     *         group
     */
    public static function create($id, $fetch = true, $bypassCache = false) {
        $id = strtolower($id);
        if(self::isCached($id) && !$bypassCache) {
            $group = self::$steamGroups[$id];
            if($fetch && !$group->isFetched()) {
                $group->fetchMembers();
            }
            return $group;
        } else {
            return new SteamGroup($id, $fetch);
        }
    }

    /**
     * Creates a new <var>SteamGroup</var> instance for the group with the
     * given ID
     *
     * @param string $id The custom URL of the group specified by the group
     *        admin or the 64bit group ID
     * @param bool $fetch if <var>true</var> the groups's data is loaded into
     *        the object
     */
    public function __construct($id, $fetch = true) {
        if(is_numeric($id)) {
            $this->groupId64 = $id;
        } else {
            $this->customUrl = $id;
        }

        $this->fetched = false;
        $this->members = array();

        if($fetch) {
            $this->fetchMembers();
        }

        $this->cache();
    }

    /**
     * Saves this SteamGroup in the cache
     *
     * @return <var>false</var> if this group is already cached
     */
    public function cache() {
        if(!array_key_exists($this->groupId64, self::$steamGroups)) {
            self::$steamGroups[$this->groupId64] = $this;
            if(!empty($this->customUrl) &&
               !array_key_exists($this->customUrl, self::$steamGroups)) {
               self::$steamGroups[$this->customUrl] = $this;
            }
        }
    }

    /**
     * Loads the members of this group
     *
     * This might take several HTTP requests as the Steam Community splits this
     * data over several XML documents if the group has lots of members.
     */
    public function fetchMembers() {
        if(empty($this->memberCount) || sizeof($this->members) == $this->memberCount) {
            $page = 0;
        } else {
            $page = 1;
        }

        do {
            $totalPages = $this->fetchPage(++$page);
        } while($page < $totalPages);

        $this->fetchTime = time();
    }

    /**
     * Returns the base URL for this group's page
     *
     * This URL is different for groups having a custom URL.
     *
     * @return string The base URL for this group
     */
    public function getBaseUrl() {
        if(empty($this->customUrl)) {
            return "http://steamcommunity.com/gid/{$this->groupId64}";
        } else {
            return "http://steamcommunity.com/groups/{$this->customUrl}";
        }
    }

    /**
     * Returns the custom URL of this group
     *
     * The custom URL is a admin specified unique string that can be used
     * instead of the 64bit SteamID as an identifier for a group.
     *
     * @return string The custom URL of this group
     */
    public function getCustomUrl() {
        return $this->customUrl;
    }

    /**
     * Returns the time this group has been fetched
     *
     * @return int The timestamp of the last fetch time
     */
    public function getFetchTime() {
        return $this->fetchTime;
    }

    /**
     * Returns this group's 64bit SteamID
     *
     * @return int This group's 64bit SteamID
     */
    public function getGroupId64() {
        return $this->groupId64;
    }

    /**
     * Returns the number of members this group has
     *
     * If the members have already been fetched the size of the member array is
     * returned. Otherwise the group size is separately fetched without needing
     * multiple requests for big groups.
     *
     * @return int The number of this group's members
     * @see #fetchPage()
     */
    public function getMemberCount() {
        if(empty($this->memberCount)) {
            $totalPages = $this->fetchPage(1);
            if($totalPages == 1) {
                $this->fetchTime = time();
            }
        }

        return $this->memberCount;
    }

    /**
     * Returns the members of this group
     *
     * If the members haven't been fetched yet, this is done now.
     *
     * @return array The Steam ID's of the members of this group
     * @see #fetchMembers()
     */
    public function getMembers() {
        if(sizeof($this->members) != $this->memberCount) {
            $this->fetchMembers();
        }

        return $this->members;
    }

    /**
     * Returns whether the data for this group has already been fetched
     *
     * @return bool <var>true</var> if the group's members have been fetched
     */
    public function isFetched() {
        return !empty($this->fetchTime);
    }

    /**
     * Fetches a specific page of the member listing of this group
     *
     * @param int $page The member page to fetch
     * @return int The total number of pages of this group's member listing
     * @see #fetchMembers()
     */
    private function fetchPage($page) {
        $url = "{$this->getBaseUrl()}/memberslistxml?p=$page";
        $memberData = $this->getData($url);

        if($page == 1) {
            $this->groupId64 = (string) $memberData->groupID64;
        }
        $this->memberCount = (int) $memberData->memberCount;
        $totalPages = (int) $memberData->totalPages;

        foreach($memberData->members->steamID64 as $member) {
            array_push($this->members, SteamId::create($member, false));
        }

        return $totalPages;
    }

}
