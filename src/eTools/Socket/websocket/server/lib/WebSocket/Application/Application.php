<?php

namespace WebSocket\Application;

/**
 * WebSocket Server Application
 *
 * @author Nico Kaiser <nico@kaiser.me>
 */
abstract class Application
{
    protected static $instances = array();

    protected function __construct() { }

    final private function __clone() { }

    final public static function getInstance() {
        $calledClassName = get_called_class();
        if (!isset(self::$instances[$calledClassName])) {
            self::$instances[$calledClassName] = new $calledClassName();
        }

        return self::$instances[$calledClassName];
    }
    abstract public function onConnect($connection);

	abstract public function onDisconnect($connection);

	abstract public function onData($data, $client);
}