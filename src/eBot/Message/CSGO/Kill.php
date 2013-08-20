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
use eBot\Message\Type\Kill as Object;

class Kill extends Message {

    public function __construct() {
        parent::__construct('/^"(?P<user_name>.+)[<](?P<user_id>\d+)[>][<](?P<steam_id>.*)[>][<](?P<user_team>CT|TERRORIST|Unassigned|Spectator)[>]" \[(?P<killer_x>[\-]?[0-9]+) (?P<killer_y>[\-]?[0-9]+) (?P<killer_z>[\-]?[0-9]+)\] killed "(?P<killed_user_name>.+)[<](?P<killed_user_id>\d+)[>][<](?P<killed_steam_id>.*)[>][<](?P<killed_user_team>CT|TERRORIST|Unassigned|Spectator)[>]" \[(?P<killed_x>[\-]?[0-9]+) (?P<killed_y>[\-]?[0-9]+) (?P<killed_z>[\-]?[0-9]+)\] with "(?P<weapon>[a-zA-Z0-9_]+)"(?P<headshot>.*)/');
    }

    public function process() {
        $o = new Object();
        $o->setUserId($this->datas['user_id']);
        $o->setUserName($this->datas['user_name']);
        $o->setUserTeam($this->datas['user_team']);
        $o->setUserSteamid($this->datas['steam_id']);
        $o->setKillerPosX($this->datas["killer_x"]);
        $o->setKillerPosY($this->datas["killer_y"]);
        $o->setKillerPosZ($this->datas["killer_z"]);

        $o->setKilledUserId($this->datas['killed_user_id']);
        $o->setKilledUserName($this->datas['killed_user_name']);
        $o->setKilledUserTeam($this->datas['killed_user_team']);
        $o->setKilledUserSteamid($this->datas['killed_steam_id']);
        $o->setKilledPosX($this->datas["killed_x"]);
        $o->setKilledPosY($this->datas["killed_y"]);
        $o->setKilledPosZ($this->datas["killed_z"]);
        
        $o->setWeapon($this->datas['weapon']);
        $o->setHeadshot(preg_match("!headshot!", $this->datas['headshot']));

        return $o;
    }

}

?>
