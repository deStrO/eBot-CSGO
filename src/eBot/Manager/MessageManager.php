<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Manager;

use eTools\Utils\Logger;
use eBot\Message\Message;

class MessageManager {

    protected static $instances = array();

    public static function createFromConfigFile() {
        Logger::debug("Loading " . APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "messages.ini");
        if (file_exists(APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "messages.ini")) {
            $data = parse_ini_file(APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "messages.ini", true);

            foreach ($data as $k => $game) {
                MessageManager::getInstance($k);
                foreach ($game["message"] as $message) {
                    if (class_exists($message)) {
                        MessageManager::getInstance($k)->addMessage(new $message());
                    }
                }

                Logger::log(MessageManager::getInstance($k)->count() . " messages loaded for $k ");
            }
        }
    }

    public static function & getInstance($name) {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new MessageManager($name);
        }
        return self::$instances[$name];
    }

    private $messages = array();
    private $name;

    public function __construct($name) {
        $this->name = $name;
        Logger::debug("Creating MessageManager $name");
    }

    public function addMessage(Message $message) {
        Logger::log("Adding message " . get_class($message) . " to " . $this->name);
        $this->messages[] = $message;
    }

    public function processMessage($data) {
        foreach ($this->messages as $message) {
            if ($message->match($data)) {
                return $message->process();
            }
        }

        return null;
    }

    public function count() {
        return count($this->messages);
    }

}

?>
