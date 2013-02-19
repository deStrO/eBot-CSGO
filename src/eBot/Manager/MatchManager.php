<?php

namespace eBot\Manager;

use eTools\Utils\Singleton;
use eTools\Task\Taskable;
use eTools\Task\Task;
use eTools\Task\TaskManager;
use eTools\Utils\Logger;
use eBot\Match\Match;
use eBot\Exception\Match_Exception;

class MatchManager extends Singleton implements Taskable {

    const VERSION = "1.1";
    const CHECK_NEW_MATCH = "check";

    private $matchs = array();
    private $busyServers = array();

    public function __construct() {
        Logger::log("Creating MatchManager version " . self::VERSION);
        TaskManager::getInstance()->addTask(new Task($this, self::CHECK_NEW_MATCH, microtime(true) + 0.2), true);
    }

    public function sendPub() {
        foreach ($this->matchs as $k => $match) {
            if ($match->getStatus() == Match::STATUS_END_MATCH) {
                $this->matchs[$k]->destruct();
                unset($this->matchs[$k]);
            } else {
                if (is_object($match)) {
                    if ($match->getNeedDel()) {
                        $this->matchs[$k]->destruct();
                        unset($this->matchs[$k]);
                    } else {
                        $match->sendRotateMessage();
                    }
                } else {
                    $this->matchs[$k]->destruct();
                    unset($this->matchs[$k]);
                }
            }
        }
    }

    private function check() {
        Logger::debug("Checking for new match (current matchs: " . count($this->matchs) . ")");

//        $sql = mysql_query("SELECT m.id as match_id, m.team_a as team_a, m.team_b as team_b, s.id as server_id, s.ip as server_ip, s.rcon as server_rcon  FROM `matchs` m LEFT JOIN `servers` s ON s.id = m.server_id WHERE m.`status` >= " . Match::STATUS_STARTING . " AND m.`status` < " . Match::STATUS_END_MATCH . " AND m.`enable` = 1") or die(mysql_error());
        $sql = mysql_query("SELECT m.team_a_name as team_a_name, m.team_b_name as team_b_name, m.id as match_id, t_a.name as team_a, t_b.name as team_b, s.id as server_id, s.ip as server_ip, s.rcon as server_rcon FROM `matchs` m LEFT JOIN `servers` s ON s.id = m.server_id LEFT JOIN `teams` t_a ON t_a.id = m.team_a LEFT JOIN `teams` t_b ON t_b.id = m.team_b WHERE m.`status` >= " . Match::STATUS_STARTING . " AND m.`status` < " . Match::STATUS_END_MATCH . " AND m.`enable` = 1") or die(mysql_error());
        while ($req = mysql_fetch_assoc($sql)) {
            if (!@$this->matchs[$req['server_ip']]) {
                try {
                    $teamA = $this->getTeamDetails($req['team_a'], 'a', $req);
                    $teamB = $this->getTeamDetails($req['team_a'], 'a', $req);
                    Logger::log("New match detected - " . $teamA['name'] . " vs " . $teamB['name'] . " on " . $req['server_ip']);
                    $this->newMatch($req["match_id"], $req['server_ip'], $req['server_rcon']);
                } catch (MatchException $ex) {
                    Logger::error("Error while creating the match");
                    mysql_query("UPDATE `matchs` SET enable=0 WHERE id = '" . $req['match_id'] . "'") or die(mysql_error());
                } catch (\Exception $ex) {
                    if ($ex->getMessage() == "SERVER_BUSY") {
                        Logger::error($req["server_ip"] . " is busy for " . (time() - $this->busyServers[$req['server_ip']]));
                    } elseif ($ex->getMessage() == "MATCH_ALREADY_PLAY_ON_THIS_SERVER") {
                        Logger::error("A match is already playing on " . $req["server_ip"]);
                    }
                }
            }
        }

        TaskManager::getInstance()->addTask(new Task($this, self::CHECK_NEW_MATCH, microtime(true) + 3), true);
    }

    private function busyIp($ip) {
        if (\eBot\Config\Config::getInstance()->getDelay_busy_server() > 0) {
            $this->busyServers[$ip] = time() + \eBot\Config\Config::getInstance()->getDelay_busy_server();
            Logger::log("Busying $ip for " . \eBot\Config\Config::getInstance()->getDelay_busy_server() . " seconds");
        }
    }

    public function delayServer($ip, $delay = null) {
        if (!@$this->busyServers[$ip]) {
            if ($delay == null) {
                $delay = \eBot\Config\Config::getInstance()->getDelay_busy_server();
            }
            $this->busyServers[$ip] = time() + $delay;
            Logger::log("Delay $ip for $delay seconds");
        }
    }

    private function newMatch($match_id, $ip, $rcon) {
        if (@$this->busyServers[$ip]) {
            if (time() > $this->busyServers[$ip]) {
                unset($this->busyServers[$ip]);
            }
        }

        if (!@$this->busyServers[$ip]) {
            if (!@$this->matchs[$ip]) {
                $this->matchs[$ip] = new Match($match_id, $ip, $rcon);
            } else {
                throw new \Exception("MATCH_ALREADY_PLAY_ON_THIS_SERVER");
            }
        } else {
            throw new \Exception("SERVER_BUSY");
        }
    }

    public function taskExecute($name) {
        if ($name == "check") {
            $this->check();
        }
    }

    public function getMatch($ip) {
        if (@$this->matchs[$ip]) {
            return $this->matchs[$ip];
        } else {
            return null;
        }
    }

    private function getTeamDetails($id, $t, $data) {
        if (is_numeric($id) && $id > 0) {
            $ds = mysql_fetch_array(mysql_query("SELECT * FROM `teams` WHERE `id` = '$id'"));
            return $ds;
        } else {
            if ($t == "a") {
                return array("name" => $data['team_a_name']);
            } elseif ($t == "b") {
                return array("name" => $data['team_b_name']);
            }
        }
    }

}

?>
