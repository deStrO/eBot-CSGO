<?php
/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Message;

abstract class Message {
	
	protected $regex;
	protected $datas;
	
	public function __construct($regex) {
		$this->regex = $regex;
	}
	
	function match($data) {
		if (preg_match($this->regex, $data, $match)) {
			$this->datas = $match;
			return true;
		}
		
		return false;
	}
	
	abstract function process();
}

?>
