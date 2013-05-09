<?php
/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Events;

abstract class Event {

	public function setOption($option, $value) {
		$this->$option = $value;
	}

	public function getOption($option) {
		return $this->$option;
	}

	/**
	 * magical __call method
	 */
	public function __call($name, $arguments) {
		if (preg_match("/^set([A-Z][a-zA-Z0-9]+)$/", $name, $match)) {
			$option = $this->lowercaseFirst($match[1]);
			return $this->setOption($option, $arguments[0]);
		} elseif (preg_match("/^get([A-Z][a-zA-Z0-9]+)$/", $name, $match)) {
			$option = $this->lowercaseFirst($match[1]);
			return $this->getOption($option);
		} else {
			return null;
		}
	}

	private function lowercaseFirst($aText) {
		//PHP 5.3
		if (function_exists('lcfirst'))
			return lcfirst($aText);
		//PHP 5.2
		else
			return strtolower(substr($aText, 0, 1)) . substr($aText, 1);
	}

}

?>
