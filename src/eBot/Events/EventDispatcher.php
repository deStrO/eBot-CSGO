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

class EventDispatcher extends Singleton {

    private $listeners = array();

    const EVENT_SAY = "eBot\Events\Event\Say";
    const EVENT_BOMB_DEFUSING = "eBot\Events\Event\BombDebusing";
    const EVENT_BOMB_PLANTING = "eBot\Events\Event\BombPlanting";
    const EVENT_KILL = "eBot\Events\Event\Kill";
    const EVENT_ROUNDSCORED = "eBot\Events\Event\RoundScored";
    const EVENT_MATCH_END = "eBot\Events\Event\MatchEnd";

    public function __construct() {
        $this->listeners[self::EVENT_SAY] = array();
        $this->listeners[self::EVENT_BOMB_DEFUSING] = array();
        $this->listeners[self::EVENT_BOMB_PLANTING] = array();
        $this->listeners[self::EVENT_KILL] = array();
        $this->listeners[self::EVENT_ROUNDSCORED] = array();
        $this->listeners[self::EVENT_MATCH_END] = array();

        /*$this->listeners["RoundScored"] = array();
        $this->listeners["RoundEnd"] = array();
        $this->listeners["RoundStart"] = array();
        $this->listeners["MatchStart"] = array();
        $this->listeners["MatchEnd"] = array();
        $this->listeners["SideEnd"] = array();
        $this->listeners["KnifeStart"] = array();
        $this->listeners["KnifeEnd"] = array();*/
    }

    public function addListener(Plugin $plugin, $name) {
        if (!isset($this->listeners[$name])) {
            Logger::error("Event name $name doesn't exists");
            return;
        }

        Logger::log("Add listener for " . get_class($plugin) . " to $name");
        $this->listeners[$name][] = $plugin;
    }

    public function dispatchEvent(Event $event) {
        Logger::debug("Dispatching event " . get_class($event));
        if (@$this->listeners[get_class($event)]) {
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

?>
