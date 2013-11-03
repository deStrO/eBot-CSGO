<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

/*
  L 08/25/2013 - 13:15:38: Team "CT" scored "3" with "1" players
  L 08/25/2013 - 13:15:38: Team "TERRORIST" scored "0" with "1" players
 */


namespace eBot\Message\CSGO;

use eBot\Message\Message;
use eBot\Message\Type\TeamScored as Object;

class TeamScored extends Message {

    public function __construct() {
        parent::__construct('/^Team "(?P<team>CT|TERRORIST)" scored "(?P<score>\d+)" with "(?P<players>\d+)" players/');
    }

    public function process() {
        $o = new Object();
        $o->setTeam($this->datas["team"] == "CT" ? "ct": "t");
        $o->setScore($this->datas['score']);
        $o->setPlayers($this->datas['players']);       

        return $o;
    }

}

?>
