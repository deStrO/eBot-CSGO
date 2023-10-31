<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Events;

use eTools\Utils\Singleton;
use eTools\Utils\Logger;
use eBot\Plugins\Plugin;
use eBot\Events\Event;

class EventDispatcher extends Singleton
{
    private $listeners = [];

    const EVENT_SAY = "eBot\Events\Event\Say";
    const EVENT_BOMB_DEFUSING = "eBot\Events\Event\BombDebusing";
    const EVENT_BOMB_PLANTING = "eBot\Events\Event\BombPlanting";
    const EVENT_KILL = "eBot\Events\Event\Kill";
    const EVENT_ROUNDSCORED = "eBot\Events\Event\RoundScored";
    const EVENT_MATCH_END = "eBot\Events\Event\MatchEnd";

    public function addListener(Plugin $plugin, $name)
    {
        if (!class_exists($name)) {
            Logger::error("Event name $name doesn't exists");

            return;
        }

        if (!isset($this->listeners[$name])) {
            $this->listeners[$name] = [];
        }

        Logger::log("Add listener for " . get_class($plugin) . " to $name");
        $this->listeners[$name][] = $plugin;
    }

    public function dispatchEvent(Event $event)
    {
        Logger::debug("Dispatching event " . get_class($event));
        if (isset($this->listeners[get_class($event)])) {
            foreach ($this->listeners[get_class($event)] as $plugin) {
                try {
                    $plugin->onEvent($event);
                } catch (\Exception $ex) {
                    Logger::error("Error while executing " . get_class($event) . " on " . get_class($plugin));
                }
            }
        }
    }
}
