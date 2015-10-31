<?php
/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eTools\Utils;

abstract class Singleton {

    protected static $instances = array();

	private static $override_instances = array();

	/**
	 * Returns a singleton object of a given class. Extend this class, then call \YourClass::getInstance() from anywhere
	 * in the application.
	 *
	 * If an override class is registered, that will be returned instead. Thi
	 *
	 * @return \stdClass A singleton of the requested class
	 */
    public static function getInstance() {
        $class = get_called_class();
		$args = func_get_args();

		// Check if an override is set, and replace the class name to load if it is
		if (isset(self::$override_instances[$class])) {
			$class = self::$override_instances[$class];
		}

		// Create a singleton instance of the class if it doesn't already exist
        if (!isset(self::$instances[$class])) {
			if(is_array($args) && sizeof($args) > 0) {
				$reflectionClass = new \ReflectionClass($class);
				self::$instances[$class] = $reflectionClass->newInstanceArgs($args);
			} else {
				self::$instances[$class] = new $class();
			}
        }

        return self::$instances[$class];
    }

	/**
	 * Configures an override class, that can later be returned by self::getInstance().
	 *
	 * @param string $oldClass The class name to replace
	 * @param string $newClass The class to replace this with
	 * @return void
	 */
	public static function set_override_instance($oldClass, $newClass) {
		self::$override_instances[$oldClass] = $newClass;
	}

	public static function clear_instances() {
		self::$instances = array();
	}

    protected function __construct() {
        
    }

    final protected function __clone() {
        
    }

}

?>
