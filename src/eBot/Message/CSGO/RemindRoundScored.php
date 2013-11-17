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
use eBot\Message\Type\RemindRoundScored as Object;

class RemindRoundScored extends Message {

    public function __construct() {
        parent::__construct('/^eBot triggered "Round_End_Reminder" Team "(?P<team>.*)" scored "\#SFUI_Notice_(?P<team_win>Terrorists_Win|CTs_Win|Target_Bombed|Target_Saved|Bomb_Defused)/');
    }

    public function process() {
        $o = new Object();
        $o->setTeam($this->datas["team"]);

        switch ($this->datas["team_win"]) {
            case "Target_Bombed":
                $o->setType("bombeexploded");
                $o->setTeamWin("T");
                break;
            case "Terrorists_Win":
                $o->setType("normal");
                $o->setTeamWin("T");
                break;
            case "Target_Saved":
                $o->setType("saved");
                $o->setTeamWin("CT");
                break;
            case "Bomb_Defused":
                $o->setType("bombdefused");
                $o->setTeamWin("CT");
                break;
            case "CTs_Win":
                $o->setType("normal");
                $o->setTeamWin("CT");
                break;
        }

        return $o;
    }

}

?>
