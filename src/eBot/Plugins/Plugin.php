<?php
/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Plugins;

interface Plugin {
	
	public function init($config);
	public function getEventList();
	public function onStart();
	public function onReload();
	public function onEnd();
	public function onEventAdded($name);
	public function onEventRemoved($name);
	public function onEvent($event);
	
}

?>
