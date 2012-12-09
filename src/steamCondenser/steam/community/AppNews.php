<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/WebApi.php';

/**
 * This class represents Steam news and can be used to load a list of current
 * news about specific games
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class AppNews {

    /**
     * @var int
     */
    private $appId;

    /**
     * @var string
     */
    private $author;

    /**
     * @var string
     */
    private $contents;

    /**
     * @var int
     */
    private $date;

    /**
     * @var bool
     */
    private $external;

    /**
     * @var string
     */
    private $feedLabel;

    /**
     * @var string
     */
    private $feedName;

    /**
     * @var string
     */
    private $gid;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $url;

    /**
     * Loads the news for the given game with the given restrictions
     *
     * @param int $appId The unique Steam Application ID of the game (e.g. 440
     *        for Team Fortress 2). See
     *        http://developer.valvesoftware.com/wiki/Steam_Application_IDs for
     *        all application IDs
     * @param int $count The maximum number of news to load (default: 5).
     *        There's no reliable way to load all news. Use really a really
     *        great number instead
     * @param int $maxLength The maximum content length of the news (default:
     *        null). If a maximum length is defined, the content of the news
     *        will only be at most <var>maxLength</var> characters long plus an
     *        ellipsis
     * @return array An array of news items for the specified game with the
     *         given options
     */
    public static function getNewsForApp($appId, $count = 5, $maxLength = null) {
        $params = array('appid' => $appId, 'count' => $count,
                        'maxlength' => $maxLength);
        $data = json_decode(WebApi::getJSON('ISteamNews', 'GetNewsForApp', 2, $params));

        $newsItems = array();
        foreach($data->appnews->newsitems as $newsData) {
            $newsItems[] = new AppNews($appId, $newsData);
        }

        return $newsItems;
    }

    /**
     * Creates a new instance of an AppNews news item with the given data
     *
     * @param int $appId The unique Steam Application ID of the game (e.g. 440
     *        for Team Fortress 2). See
     *        http://developer.valvesoftware.com/wiki/Steam_Application_IDs for
     *        all application IDs
     * @param stdClass $newsData The news data extracted from JSON
     */
    private function __construct($appId, $newsData) {
        $this->appId     = $appId;
        $this->author    = $newsData->author;
        $this->contents  = trim($newsData->contents);
        $this->date      = (int) $newsData->date;
        $this->external  = (bool) $newsData->is_external_url;
        $this->feedLabel = $newsData->feedlabel;
        $this->feedName  = $newsData->feedname;
        $this->gid       = $newsData->gid;
        $this->title     = $newsData->title;
        $this->url       = $newsData->url;
    }

    /**
     * Returns the Steam Application ID of the game this news belongs to
     *
     * @return int The application ID of the game this news belongs to
     */
    public function getAppId() {
        return $this->appId;
    }

    /**
     * Returns the author of this news item
     *
     * @return string The author of this news
     */
    public function getAuthor() {
        return $this->author;
    }

    /**
     * Returns the content of this news item
     *
     * This might contain HTML code.
     *
     * <strong>Note:</strong> Depending on the setting for the maximum length
     * of a news (see {@link #getNewsForApp}, the contents might be truncated.
     *
     * @return string The content of this news
     */
    public function getContents() {
        return $this->contents;
    }

    /**
     * Returns the date this news item has been published
     *
     * @return int The date this news has been published
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Returns the name of the feed this news item belongs to
     *
     * @return string The name of the feed this news belongs to
     */
    public function getFeedLabel() {
        return $this->feedLabel;
    }

    /**
     * Returns the symbolic name of the feed this news item belongs to
     *
     * @return string The symbolic name of the feed this news belongs to
     */
    public function getFeedName() {
        return $this->feedName;
    }

    /**
     * Returns a unique identifier for this news
     *
     * @return string A unique identifier for this news
     */
    public function getGid() {
        return $this->gid;
    }

    /**
     * Returns the title of this news item
     *
     * @return string The title of this news
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Returns the URL of the original news
     *
     * This is a direct link to the news on the Steam website or a redirecting
     * link to the external post.
     *
     * @return string The URL of the original news
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Returns whether this news item originates from a source other than Steam
     * itself (e.g. an external blog)
     *
     * @return boolean <var>true</var> if this news item is from an external
     *         source
     */
    public function isExternal() {
        return $this->external;
    }

}
