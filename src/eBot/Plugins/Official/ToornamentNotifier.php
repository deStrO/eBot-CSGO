<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Plugins\Official;

use eBot\Plugins\Plugin;
use eTools\Utils\Logger;
use eBot\Exception\PluginException;

/**
 * Description of PluginMatchScoreNotifier
 *
 * @author jpardons
 */
class ToornamentNotifier implements Plugin
{

    private $url;
    private $key;

    public function init($config)
    {
        Logger::log("Init PluginMatchScoreNotifier");
        $this->url = $config["url"];
        $this->key = $config["key"];

        if ($this->url == "") {
            throw new PluginException("url null");
        }

        Logger::log("URL to perform: " . $this->url);
    }

    public function onEvent($event)
    {
        switch (get_class($event)) {
            case \eBot\Events\EventDispatcher::EVENT_ROUNDSCORED:
                if ($event->getMatch()->getIdentifier()) {
                    $url = str_replace("{MATCH_ID}", $event->getMatch()->getIdentifier(), $this->url);
                    $opts = array(
                        'http' => array(
                            'method' => "GET",
                            'header' => "Connection: Close\r\n" .
                                "X-Plugin-Key: " . $this->key
                        )
                    );

                    $context = stream_context_create($opts);
                    Logger::log($event->getMatch()->getId() . " - Perf $url");
                    file_get_contents($url, false, $context);
                }
                break;
        }
    }

    public function onEventAdded($name)
    {

    }

    public function onEventRemoved($name)
    {

    }

    public function onReload()
    {
        Logger::log("Reloading " . get_class($this));
    }

    public function onStart()
    {
        Logger::log("Starting " . get_class($this));
    }

    public function onEnd()
    {
        Logger::log("Ending " . get_class($this));
    }

    public function getEventList()
    {
        return array(\eBot\Events\EventDispatcher::EVENT_ROUNDSCORED);
    }

}

?>
