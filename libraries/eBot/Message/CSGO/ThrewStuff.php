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
use eBot\Message\Type\ThrewStuff as Object;

class ThrewStuff extends Message {

    public function __construct() {
        parent::__construct('/^"(?P<user_name>.+)[<](?P<user_id>\d+)[>][<](?P<steam_id>.*)[>][<](?P<user_team>CT|TERRORIST|Unassigned|Spectator)[>]" threw (?P<stuff>hegrenade|flashbang|smokegrenade|decoy|molotov) \[(?P<pos_x>[\-]?[0-9]+) (?P<pos_y>[\-]?[0-9]+) (?P<pos_z>[\-]?[0-9]+)\]/');
    }

    public function process() {
        $o = new Object();
        $o->setUserId($this->datas['user_id']);
        $o->setUserName($this->datas['user_name']);
        $o->setUserTeam($this->datas['user_team']);
        $o->setUserSteamid($this->datas['steam_id']);
        $o->setPosX($this->datas["pos_x"]);
        $o->setPosY($this->datas["pos_y"]);
        $o->setPosZ($this->datas["pos_z"]);
        $o->setStuff($this->datas["stuff"]);
        
        return $o;
    }

}

?>
