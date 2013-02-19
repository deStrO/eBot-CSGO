<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Manager;

use eTools\Utils\Singleton;
use eTools\Utils\Logger;
use eBot\Plugins\Plugin;
use eBot\Exception\PluginException;
use eBot\Events\EventDispatcher;

class PluginsManager extends Singleton {

    private $plugins = array();

    public function __construct() {
        Logger::log("Loading plugins");
        Logger::debug("Loading " . APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "plugins.ini");
        if (file_exists(APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "plugins.ini")) {
            $data = parse_ini_file(APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "plugins.ini", true);
            foreach ($data as $k => $d) {

                if (class_exists($k)) {
                    $plugin = $this->createPlugin($k);
                    if ($plugin instanceof Plugin) {
                        $this->init($plugin, $d);
                    } else {
                        Logger::error("Plugin doesn't implements interface Plugin");
                    }
                } else {
                    Logger::error("Can't load plugin $k");
                }
            }
        } else {
            Logger::error(APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "plugins.ini doesn't exists");
        }
    }

    public function createPlugin($name) {
        $plugin = unserialize(
                sprintf(
                        'O:%d:"%s":0:{}', strlen($name), $name
                )
        );

        return $plugin;
    }

    public function init(Plugin $plugin, $data) {
        try {
            Logger::log("Adding plugin " . get_class($plugin));
            Logger::debug("Calling init");
            $plugin->init($data);
            $this->plugins[get_class($plugin)] = $plugin;
        } catch (PluginException $ex) {
            Logger::error("Error while init plugin");
        }
    }

    public function startAll() {
        foreach ($this->plugins as $plugin) {
            try {
                Logger::debug("Starting plugin " . get_class($plugin));
                $plugin->onStart();

                Logger::debug("Attaching event");
                $events = $plugin->getEventList();
                if (is_array($events)) {
                    foreach ($events as $event) {
                        if (is_string($event)) {
                            EventDispatcher::getInstance()->addListener($plugin, $event);
                        }
                    }
                } else {
                    Logger::error("Event list is not an array");
                }
            } catch (\Exception $ex) {
                Logger::error("Error while starting " . get_class($plugin));
            }
        }

        if (count($this->plugins) > 0)
            Logger::log("Plugins started");
    }

    public function stopAll() {
        foreach ($this->plugins as $plugin) {
            try {
                Logger::debug("Starting plugin " . get_class($plugin));
                $plugin->stop();
            } catch (\Exception $ex) {
                Logger::error("Error while starting " . get_class($plugin));
            }
        }
    }

}

?>
