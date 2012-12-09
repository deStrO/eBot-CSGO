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
use eBot\Message\Type\JoinTeam as Object;

class SwitchTeam extends Message {

    public function __construct() {
        // "Opie<11><BOT>" switched from team <CT> to <TERRORIST>
        parent::__construct('/^"(?P<user_name>.+)[<](?P<user_id>\d+)[>][<](?P<steam_id>.*)[>]" switched from team [<](?P<user_team>CT|TERRORIST|Unassigned|Spectator)[>] to [<](?P<new_team>CT|TERRORIST|Unassigned|Spectator)[>]/');
    }

    public function process() {
        $o = new Object();
        $o->setUserId($this->datas['user_id']);
        $o->setUserName($this->datas['user_name']);
        $o->setUserTeam($this->datas['user_team']);
        $o->setUserSteamid($this->datas['steam_id']);
        $o->setJoinTeam($this->datas['new_team']);

        return $o;
    }

}

?>
