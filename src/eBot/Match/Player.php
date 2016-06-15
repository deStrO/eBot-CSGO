<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Match;

use eTools\Utils\Logger;

class Player {

    private $id = 0;
    private $match_id = 0;
    private $map_id = 0;
    private $save_mode = null;
    private $steamid = "";
    private $ip = "";
    private $online = true;
    private $kill = 0;
    private $assist = 0;
    private $death = 0;
    private $hs = 0;
    private $bombe = 0;
    private $defuse = 0;
    private $tk = 0;
    private $point = 0;
    private $name = "";
    private $mysql_id = 0;
    private $currentSide = "";
    private $killRound = 0;
    private $alive = true;
    private $firstKill = 0;
    private $v1 = 0;
    private $v2 = 0;
    private $v3 = 0;
    private $v4 = 0;
    private $v5 = 0;
    private $k1 = 0;
    private $k2 = 0;
    private $k3 = 0;
    private $k4 = 0;
    private $k5 = 0;
    private $firstSide = "";
    private $checkBDD = false;
    private $gotFirstKill = false;

    public function __construct($match_id, $map_id, $steamid) {
        $this->map_id = $map_id;
        $this->match_id = $match_id;
        $this->steamid = $steamid;

        Logger::debug("Creating object Player $match_id $map_id $steamid");

        $sql = \mysql_query("SELECT * FROM players WHERE match_id='" . $this->match_id . "' AND map_id='" . $this->map_id . "' AND steamid = '" . $this->steamid . "'") or dir(mysql_error());
        $req = \mysql_fetch_array($sql);
        if ($req) {
            Logger::log("Restoring player " . $this->steamid . " from match " . $this->match_id);
            $this->mysql_id = $req['id'];
            $this->firstSide = $req['first_side'];
            $this->currentSide = $req['current_side'];
            $this->name = $req['pseudo'];
            $this->kill = $req['nb_kill'];
            $this->assist = $req['assist'];
            $this->death = $req['death'];
            $this->point = $req['point'];
            $this->hs = $req['hs'];
            $this->defuse = $req['defuse'];
            $this->bombe = $req['bombe'];
            $this->tk = $req['tk'];
            $this->v1 = $req['nb1'];
            $this->v2 = $req['nb2'];
            $this->v3 = $req['nb3'];
            $this->v4 = $req['nb4'];
            $this->v5 = $req['nb5'];
            $this->k1 = $req['nb1kill'];
            $this->k2 = $req['nb2kill'];
            $this->k3 = $req['nb3kill'];
            $this->k4 = $req['nb4kill'];
            $this->k5 = $req['nb5kill'];
            $this->firstKill = $req['firstkill'];
        } else {
            Logger::log("Creating players " . $this->steamid . " on match " . $this->match_id);
            \mysql_query("INSERT INTO `players` (`match_id`,`map_id`,`steamid`,`first_side`,`created_at`, `updated_at`) VALUES ('{$this->match_id}','{$this->map_id}', '{$this->steamid}', 'other', NOW(), NOW())") or die(mysql_error());
            $this->mysql_id = \mysql_insert_id();
        }
    }

    private $team = null;

    public function setTeam($team, $teamDefault = null) {
        if ($this->team == null) {
            if (($teamDefault != null) && ($teamDefault == "a") || ($teamDefault == "b")) {
                Logger::debug("Already got the team");
                $this->team = $teamDefault;
            } else {
                if ($team == "CT") {
                    $this->team = "a";
                } elseif ($team == "TERRORIST") {
                    $this->team = "b";
                } elseif ($team == "ct") {
                    $this->team = "a";
                } elseif ($team == "t") {
                    $this->team = "b";
                } else {
                    $this->team = null;
                }
            }

            if ($this->team != null) {
                mysql_query("UPDATE `players` SET team = '{$this->team}', updated_at = NOW() WHERE id='{$this->mysql_id}'");
            } else {
                mysql_query("UPDATE `players` SET team = 'other' WHERE id='{$this->mysql_id}'");
            }
        }
    }

    public function roundStart() {
        $this->killRound = 0;
        $this->alive = true;
        $this->gotFirstKill = false;

        // Snapshotting player
    }

    public function getId() {
        return $this->mysql_id;
    }

    public function setOnline($online) {
        $this->online = $online;
    }

    public function inc($var, $nb = 1) {
        $this->$var += $nb;
    }

    public function deinc($var, $nb = 1) {
        $this->$var -= $nb;
    }

    public function __set($name, $val) {
        $this->$name = $val;
    }

    public function __get($name) {
        return $this->$name;
    }

    public function set($name, $val) {
        $this->$name = $val;
    }

    public function get($name) {
        return $this->$name;
    }

    public function setIp($ip) {
        $this->ip = $ip;
        Logger::debug("Setting $ip to " . $this->steamid . " (players #" . $this->mysql_id . ")");
        mysql_query("UPDATE `player` SET ip='{$ip}' WHERE id='{$this->mysql_id}'");
    }

    public function setCurrentTeam($team, $teamDefault = null) {
        if ($team == "CT") {
            $this->currentSide = "ct";
        } elseif ($team == "TERRORIST") {
            $this->currentSide = "t";
        } elseif ($team == "ct") {
            $this->currentSide = "ct";
        } elseif ($team == "t") {
            $this->currentSide = "t";
        } else {
            $this->currentSide = "other";
        }

        $this->setTeam($this->currentSide, $teamDefault);
    }

    public function setUserName($name) {
        if ($this->name != $name) {
            if ($this->name == "") {
                Logger::log("Setting nickname to $name");
            } else {
                Logger::log("Changing nickname from {$this->name} to $name");
            }
            $this->name = $name;
        }
    }

    public function getSteamid() {
        return $this->steamid;
    }

    public function save() {
        mysql_query("UPDATE `players` SET pseudo='" . \mysql_real_escape_string($this->name) . "', current_side='" . $this->currentSide . "' WHERE id='{$this->mysql_id}'") or Logger::error(mysql_error());
    }

    public function saveScore() {
        $query = "UPDATE players SET
                    nb_kill = '" . $this->kill . "',
                    death = '" . $this->death . "',
                    assist = '" . $this->assist . "',
                    hs = '" . $this->hs . "',
                    defuse = '" . $this->defuse . "',
                    bombe = '" . $this->bombe . "',
                    point = '" . $this->point . "',
                    tk = '" . $this->tk . "', 
                    firstkill='" . $this->firstKill . "',
                    updated_at = NOW()
                      
                 WHERE id='" . $this->mysql_id . "'";

        if (!mysql_query($query)) {
            Logger::error(mysql_error());
        }
    }

    public function saveKillRound() {
        if ($this->killRound > 0) {
            if ($this->killRound <= 5) {
                mysql_query("UPDATE players SET nb" . $this->killRound . "kill = nb" . $this->killRound . "kill + 1 WHERE id='" . $this->mysql_id . "'");
                $k = "k" . $this->killRound;
                $this->inc($k);
            }
        }
    }

    public function snapshot($round) {
        @\mysql_query("DELETE FROM players_snapshot WHERE player_id = '" . $this->mysql_id . "' AND round_id='" . $round . "'");

        \mysql_query("INSERT INTO players_snapshot 
            (`player_id`,`nb_kill`,`death`,`assist`,`point`,`hs`,`defuse`,`bombe`,`tk`,`nb1`,`nb2`,`nb3`,`nb4`,`nb5`,`nb1kill`,`nb2kill`,`nb3kill`,`nb4kill`,`nb5kill`,`firstkill`,`round_id`,`created_at`,`updated_at`)
            VALUES
            ({$this->mysql_id}, {$this->kill}, {$this->death}, {$this->assist}, {$this->point}, {$this->hs}, {$this->defuse}, {$this->bombe}, {$this->tk}, {$this->v1}, {$this->v2}, {$this->v3}, {$this->v4}, {$this->v5}, {$this->k1}, {$this->k2}, {$this->k3}, {$this->k4}, {$this->k5}, {$this->firstKill}, {$round}, NOW(), NOW())") or Logger::error("Error while snapshoting");
    }

    public function restoreSnapshot($round) {
        $sql = \mysql_query("SELECT * FROM players_snapshot WHERE player_id ='" . $this->mysql_id . "' AND round_id='" . $round . "' ") or dir(mysql_error());
        $req = \mysql_fetch_array($sql);
        if ($req) {
            Logger::log("Restoring player " . $this->steamid . " from match " . $this->match_id . " for round " . $round);
            $this->kill = $req['nb_kill'];
            $this->assist = $req['assist'];
            $this->death = $req['death'];
            $this->point = $req['point'];
            $this->hs = $req['hs'];
            $this->defuse = $req['defuse'];
            $this->bombe = $req['bombe'];
            $this->tk = $req['tk'];
            $this->v1 = $req['nb1'];
            $this->v2 = $req['nb2'];
            $this->v3 = $req['nb3'];
            $this->v4 = $req['nb4'];
            $this->v5 = $req['nb5'];
            $this->k1 = $req['nb1kill'];
            $this->k2 = $req['nb2kill'];
            $this->k3 = $req['nb3kill'];
            $this->k4 = $req['nb4kill'];
            $this->k5 = $req['nb5kill'];
            $this->firstKill = $req['firstkill'];
        } else {
            Logger::log("Snapshot not found for " . $this->steamid . " from match " . $this->match_id . " for round " . $round);
        }
    }

}

?>
