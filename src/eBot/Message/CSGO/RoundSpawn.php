<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Message\CSGO;

use eBot\Message\Message;
use eBot\Message\Type\RoundSpawn as Object;

class RoundSpawn extends Message {

    public function __construct() {
        parent::__construct('/^eBot triggered "Round_Spawn"/');
    }

    public function process() {
        $o = new Object();
        $o->setTime(time());

        return $o;
    }

}

?>
