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
use eBot\Exception\MatchException;
use eBot\Match\Map;
use eTools\Task\TaskManager;
use eTools\Task\Task;
use eTools\Task\Taskable;
use eTools\Rcon\CSGO as Rcon;

class Match implements Taskable {

    const STATUS_NOT_STARTED = 0;
    const STATUS_STARTING = 1;
    const STATUS_WU_KNIFE = 2;
    const STATUS_KNIFE = 3;
    const STATUS_END_KNIFE = 4;
    const STATUS_WU_1_SIDE = 5;
    const STATUS_FIRST_SIDE = 6;
    const STATUS_WU_2_SIDE = 7;
    const STATUS_SECOND_SIDE = 8;
    const STATUS_WU_OT_1_SIDE = 9;
    const STATUS_OT_FIRST_SIDE = 10;
    const STATUS_WU_OT_2_SIDE = 11;
    const STATUS_OT_SECOND_SIDE = 12;
    const STATUS_END_MATCH = 13;
    const STATUS_ARCHIVE = 14;
    const TASK_ENGAGE_MAP = "engageMap";
    const CHANGE_HOSTNAME = "changeHostname";
    const TEST_RCON = "testRcon";
    const REINIT_RCON = "rconReinit";
    const SET_LIVE = "setLive";
    const STOP_RECORD = "stopRecord";
    const TASK_DELAY_READY = "delayReady";
    const TASK_SEND_TEAM_NAMES = "sendTeamNames";

    // Variable calculable (pas en BDD)
    private $players = array();
    private $players_old = array();
    private $rcon = null;
    private $lastMessage = 0;
    private $match = null;
    private $nbRS = 0;
    private $message = 0;
    private $pluginCsay = false;
    private $pluginESL = false;
    private $pluginSwitch = false;
    private $pluginPrintPlayers = false;
    private $waitForRestart = false;
    private $flood = array();
    private $gameBombPlanter = null;
    private $gameBombDefuser = null;
    private $enable = true;
    private $userToEnter;
    private $nbLast = array("nb_max_ct" => 0, "nb_max_t" => 0, "nb_ct" => 0, "nb_ct" => 0);
    private $winKnife = "";
    private $needDel = false;
    private $firstFrag = false;
    private $rsKnife = false;
    private $password;
    private $passwordChanged = false;
    private $updatedHeatmap = false;
    // Variable en BDD obligatoire
    private $match_id;
    private $server_ip;
    private $season_id;
    private $score = array("team_a" => 0, "team_b" => 0);
    private $nbRound = 0;
    private $nbOT = 0;
    private $scoreSide = array();
    private $scoreJoueurSide = array();
    private $config_full_score = false;
    private $config_ot = false;
    private $ot_startmoney;
    private $ot_maxround;
    private $config_switch_auto = false;
    private $config_kniferound = false;
    private $config_streamer = false;
    private $streamerReady = false;
    private $rules;
    private $maxRound = 15;
    private $oldMaxround = 15;
    private $maps = array();
    private $matchData = array();
    private $currentMap = null;
    private $messageManager;
    private $teamAName;
    private $teamBName;
    private $teamAFlag;
    private $teamBFlag;
    private $rconPassword;
    private $isPaused;
    private $backupFile;
    private $timeRound = null;
    private $roundEndEvent;
    private $websocket = null;
    private $pause = array("ct" => false, "t" => false);
    private $unpause = array("ct" => false, "t" => false);
    private $continue = array("ct" => false, "t" => false);
    private $side = array("team_a" => "ct", "team_b" => "t");
    private $ready = array("ct" => false, "t" => false);
    private $stop = array("ct" => false, "t" => false);
    private $playMap = array("ct" => "", "t" => "");
    private $timeEngageMap = 0;
    private $mapIsEngaged = false;
    private $waitRoundStartRecord = false;
    private $forceRoundStartRecord = false;
    private $delay_ready_inprogress = false;
    private $delay_ready_countdown = 10;
    private $delay_ready_abort = false;
    private $roundRestartEvent = false;

    public function __construct($match_id, $server_ip, $rcon) {
        Logger::debug("Registring MessageManager");
        $this->messageManager = \eBot\Manager\MessageManager::getInstance("CSGO");

        Logger::debug("Creating match #" . $match_id . " on $server_ip");

        $this->match_id = $match_id;
        $this->server_ip = $server_ip;

        $query = \mysql_query("SELECT * FROM `matchs` WHERE id = '" . $match_id . "'");
        if (!$query) {
            throw new MatchException();
        }
        $this->matchData = \mysql_fetch_assoc($query);

        // SETTING TEAMNAME AND FLAG
        $teama_details = $this->getTeamDetails($this->matchData["team_a"], "a");
        $this->teamAName = $teama_details['name'];
        $this->teamAFlag = $teama_details['flag'];

        $teamb_details = $this->getTeamDetails($this->matchData["team_b"], "b");
        $this->teamBName = $teamb_details['name'];
        $this->teamBFlag = $teamb_details['flag'];

        $this->season_id = $this->matchData["season_id"];

        $this->addMatchLog("----------- Creating log file -----------", false, false);
        $this->addMatchLog("- Match Parameter", false, false);
        $this->addMatchLog("- Match ID: " . $this->match_id, false, false);
        $this->addMatchLog("- Teams: " . $this->teamAName . " - " . $this->teamBName, false, false);
        $this->addMatchLog("- MaxRound: " . $this->matchData["max_round"], false, false);
        $this->addMatchLog("- Overtime: " . ($this->matchData["config_ot"]) ? "yes (money: " . $this->matchData['overtime_startmoney'] . ", round: " . $this->matchData['overtime_max_round'] . ")" : "no", false, false);

        // Get Websocket
        $this->websocket['match'] = \eBot\Application\Application::getInstance()->getWebSocket('match');
        $this->websocket['livemap'] = \eBot\Application\Application::getInstance()->getWebSocket('livemap');
        $this->websocket['logger'] = \eBot\Application\Application::getInstance()->getWebSocket('logger');

        $ip = explode(":", $this->server_ip);
        try {
            $this->rcon = new Rcon($ip[0], $ip[1], $rcon);
            $this->rconPassword = $rcon;
            Logger::log("RCON init ok");
            $this->rcon->send("log on; logaddress_del " . \eBot\Config\Config::getInstance()->getBot_ip() . ":" . \eBot\Config\Config::getInstance()->getBot_port() . ";logaddress_add " . \eBot\Config\Config::getInstance()->getBot_ip() . ":" . \eBot\Config\Config::getInstance()->getBot_port());
            $this->addMatchLog("- RCON connection OK", true, false);
        } catch (\Exception $ex) {
            $this->needDel = true;
            if (!is_numeric(\eBot\Manager\MatchManager::getInstance()->getRetry($this->match_id))) {
                if ($this->status == 1) {
                    \mysql_query("UPDATE `matchs` SET `enable` = 0, `auto_start` = 0, `status` = 0 WHERE `id` = '" . $this->match_id . "'");
                } else {
                    \mysql_query("UPDATE `matchs` SET `enable` = 0, `auto_start` = 0 WHERE `id` = '" . $this->match_id . "'");
                }
            } else {
                if (\eBot\Manager\MatchManager::getInstance()->getRetry($this->match_id) > 3) {
                    if ($this->status == 1) {
                        \mysql_query("UPDATE `matchs` SET `enable` = 0, `auto_start` = 0, `status` = 0 WHERE `id` = '" . $this->match_id . "'");
                    } else {
                        \mysql_query("UPDATE `matchs` SET `enable` = 0, `auto_start` = 0 WHERE `id` = '" . $this->match_id . "'");
                    }
                } else {
                    $this->addLog("Next retry (" . \eBot\Manager\MatchManager::getInstance()->getRetry($this->match_id) . "/3)");
                    \eBot\Manager\MatchManager::getInstance()->setRetry($this->match_id, \eBot\Manager\MatchManager::getInstance()->getRetry($this->match_id) + 1);
                    \eBot\Manager\MatchManager::getInstance()->delayServer($this->server_ip, 5);
                }
            }
            $this->websocket['match']->sendData(json_encode(array('message' => 'button', 'content' => 'stop', 'id' => $this->match_id)));
            Logger::error("Rcon failed - " . $ex->getMessage());
            Logger::error("Match destructed.");
            $this->addMatchLog("RCON Failed - " . $ex->getMessage(), false, false);
            throw new MatchException();
        }
        TaskManager::getInstance()->addTask(new Task($this, self::TEST_RCON, microtime(true) + 10));

        // CSay Detection
        try {
            $text = $this->rcon->send("csay_version");
            if (preg_match('!"csay_version" = "(.*)"!', $text, $match)) {
                $this->addLog("CSay version " . $match[1]);
                $this->pluginCsay = true;
                $this->pluginPrintPlayers = true;
                $this->pluginSwitch = true;
                $this->addMatchLog("- CSay version " . $match[1], false, false);
            }
        } catch (\Exception $ex) {
            Logger::error("Error while getting plugins information");
        }
        // ESL Plugin Detection
        try {
            $text = $this->rcon->send("esl_version");
            if (preg_match('!"esl_version" = "(.*)"!', $text, $match)) {
                $this->addLog("ESL Plugin version " . $match[1]);
                $this->pluginESL = true;
                $this->addMatchLog("- ESL Plugin version " . $match[1], false, false);
            }
        } catch (\Exception $ex) {
            Logger::error("Error while getting plugins information");
        }

        $this->config_full_score = $this->matchData["config_full_score"];
        $this->config_kniferound = $this->matchData["config_knife_round"];
        $this->config_switch_auto = $this->matchData["config_switch_auto"];
        $this->config_ot = $this->matchData["config_ot"];
        if ($this->config_ot) {
            $this->ot_startmoney = $this->matchData["overtime_startmoney"];
            $this->ot_maxround = $this->matchData["overtime_max_round"];
        }
        $this->config_streamer = $this->matchData["config_streamer"];

        $this->maxRound = $this->matchData["max_round"];
        $this->oldMaxround = $this->maxRound;
        $this->rules = $this->matchData["rules"];

        $this->status = $this->matchData["status"];

        $this->isPaused = $this->matchData['is_paused'] == 1;

        Logger::debug("Match config loaded - Printing configuration");
        $this->addLog("Match configuration" .
                " :: Full Score: " . (($this->config_full_score) ? "yes" : "no") .
                " :: Switch Auto: " . (($this->config_switch_auto) ? "yes" : "no") .
                " :: Over Time: " . (($this->config_ot) ? "yes" : "no") .
                " :: KnifeRound: " . (($this->config_kniferound) ? "yes" : "no"));

        $this->addMatchLog("- Match configuration" .
                " :: Full Score: " . (($this->config_full_score) ? "yes" : "no") .
                " :: Switch Auto: " . (($this->config_switch_auto) ? "yes" : "no") .
                " :: Over Time: " . (($this->config_ot) ? "yes" : "no") .
                " :: KnifeRound: " . (($this->config_kniferound) ? "yes" : "no"), false, true);

        $this->addLog("MaxRound: " . $this->maxRound . " :: Rules: " . $this->rules);

        // Map Start

        Logger::debug("Loading maps");
        $query = \mysql_query("SELECT * FROM `maps` WHERE match_id = '" . $match_id . "'");
        if (!$query) {
            throw new MatchException();
        }

        while ($data = \mysql_fetch_assoc($query)) {
            $this->maps[$data["id"]] = new Map($data);
            $this->maps[$data["id"]]->setNbMaxRound($this->maxRound);
        }

        // Fixing for maxround in OT
        if ($this->getStatus() >= self::STATUS_WU_OT_1_SIDE) {
            $this->maxRound = $this->ot_maxround;
        }

        if ($this->matchData["current_map"] == null) {
            Logger::debug("No map is currently playing, picking one (based on default)");
            foreach ($this->maps as &$map) {
                if (($map->getStatus() == Map::STATUS_NOT_STARTED) && ($map->getMapsFor() == "default")) {
                    $this->currentMap = $map;
                    $this->matchData["current_map"] = $map->getMapId();
                    Logger::debug("Map #" . $map->getMapId() . " selected");
                    mysql_query("UPDATE `matchs` SET current_map='" . $map->getMapId() . "' WHERE id='" . $this->match_id . "'");
                    break;
                }
            }
        } else {
            if ($this->maps[$this->matchData["current_map"]]) {
                $this->currentMap = $this->maps[$this->matchData["current_map"]];
            } else {
                $this->addLog("Can't find the map #" . $this->matchData["current_map"], Logger::ERROR);
                throw new MatchException();
            }
        }

        if ($this->currentMap == null) {
            $this->addLog("No map found, exiting matchs", Logger::ERROR);
            mysql_query("UPDATE `matchs` SET enable='0', status='" . self::STATUS_END_MATCH . "' WHERE id='" . $this->match_id . "'");
            throw new MatchException();
        }

        $this->addLog("Maps selected: #" . $this->currentMap->getMapId() . " - " . $this->currentMap->getMapName() . " - " . $this->currentMap->getStatusText());

        if ($this->currentMap->getMapName() != "tba") {
            $this->mapIsEngaged = true;
            $this->addLog("Setting map engaged");
        } else {
            $this->mapIsEngaged = false;
            $this->addLog("Setting veto mode");
        }

        if ($this->getStatus() == self::STATUS_STARTING) {
            if (($this->currentMap->getStatus() == Map::STATUS_NOT_STARTED) || ($this->currentMap->getStatus() == Map::STATUS_STARTING)) {
                if ($this->config_kniferound) {
                    Logger::debug("Setting need knife round on map");
                    $this->currentMap->setNeedKnifeRound(true);
                }
            }
            if ($this->currentMap->getMapName() != "tba") {
                Logger::debug("Schedule task for first map");
                TaskManager::getInstance()->addTask(new Task($this, self::TASK_ENGAGE_MAP, microtime(true) + 1));
            } else {
                if ($this->config_kniferound) {
                    $this->setStatus(self::STATUS_WU_KNIFE, true);
                    $this->currentMap->setStatus(Map::STATUS_WU_KNIFE, true);
                } else {
                    $this->setStatus(self::STATUS_WU_1_SIDE, true);
                    $this->currentMap->setStatus(Map::STATUS_WU_1_SIDE, true);
                }
            }
        } else {
            if (($this->currentMap->getStatus() == Map::STATUS_NOT_STARTED) || ($this->currentMap->getStatus() == Map::STATUS_STARTING)) {
                if ($this->currentMap->getMapName() != "tba") {
                    Logger::debug("Current map is not started/starting, engaging map");
                    TaskManager::getInstance()->addTask(new Task($this, self::TASK_ENGAGE_MAP, microtime(true) + 1));
                } else {
                    if ($this->config_kniferound) {
                        $this->setStatus(self::STATUS_WU_KNIFE, true);
                        $this->currentMap->setStatus(Map::STATUS_WU_KNIFE, true);
                    } else {
                        $this->setStatus(self::STATUS_WU_1_SIDE, true);
                        $this->currentMap->setStatus(Map::STATUS_WU_1_SIDE, true);
                    }
                }
            } else {
                Logger::debug("Restore score");
            }
        }

        TaskManager::getInstance()->addTask(new Task($this, self::CHANGE_HOSTNAME, microtime(true) + 5));

        // Setting side for maps
        if ($this->currentMap->getCurrentSide() == "ct") {
            $this->side['team_a'] = "ct";
            $this->side['team_b'] = "t";
        } else {
            $this->side['team_a'] = "t";
            $this->side['team_b'] = "ct";
        }

        $this->websocket['match']->sendData(json_encode(array('message' => 'teams', 'teamA' => $this->side['team_a'], 'teamB' => $this->side['team_b'], 'id' => $this->match_id)));

        // Calculating scores
        $this->currentMap->calculScores();

        $this->score["team_a"] = $this->currentMap->getScore1();
        $this->score["team_b"] = $this->currentMap->getScore2();

        @mysql_query("UPDATE `matchs` SET score_a = '" . $this->score["team_a"] . "', score_b ='" . $this->score["team_b"] . "' WHERE id='" . $this->match_id . "'");

        // Setting nb OverTime
        $this->nbOT = $this->currentMap->getNbOt();

        // This case happens only when the bot shutdown and restart at this status
        if ($this->currentMap->getStatus() == Map::STATUS_END_KNIFE) {
            $this->addLog("Setting round to knife round, because was waiting end knife");
            $this->currentMap->setStatus(Map::STATUS_WU_KNIFE, true);
            $this->setStatus(Map::STATUS_WU_KNIFE, true);
        }

        // Getting all players
        $this->recupStatus();

        // Setting server password
        if ($this->matchData["config_password"] != "") {
            $this->rcon->send("sv_password \"" . $this->matchData["config_password"] . "\"");
        }

        $this->addMatchLog("----------- End match loading -----------", false, false);

        if ($this->getStatus() <= self::STATUS_WU_1_SIDE) {
            $this->sendTeamNames();
        }

        if ($this->matchData["ingame_enable"] != null && !$this->matchData["ingame_enable"]) {
            $this->addLog("Setting match not enabled");
            $this->enable = false;
        }

        if ($this->getStatus() > self::STATUS_WU_1_SIDE) {
            $this->streamerReady = true;
        }

        // Setting nbMaxRound for sided score
        if ($this->getStatus() > self::STATUS_WU_OT_1_SIDE && $this->config_ot) {
            $this->currentMap->setNbMaxRound($this->ot_maxround);
        }

        // Sending roundbackup format file
        $this->rcon->send("mp_backup_round_file \"ebot_" . $this->match_id . "\"");

        TaskManager::getInstance()->addTask(new Task($this, self::CHANGE_HOSTNAME, microtime(true) + 60));
    }

    private function recupStatus($eraseAll = false) {
        if ($eraseAll) {
            unset($this->players);
            $this->players = array();
            $this->addLog("Deleting all player in BDD");
            mysql_query("DELETE FROM players WHERE map_id='" . $this->currentMap->getMapId() . "'");
        }

        if ($this->pluginPrintPlayers) {
            $this->addLog("Getting status");
            $text = $this->rcon->send("steamu_printPlayer");
            $texts = explode("\n", trim($text));
            foreach ($texts as $v) {
                if (preg_match('!#(\d+) "(.*)" (.*) (\d+) (\d+).(\d+).(\d+).(\d+)!', $v, $arr)) {
                    $ip = $arr[5] . "." . $arr[6] . "." . $arr[7] . "." . $arr[8];
                    switch ($arr[4]) {
                        case 0:
                            $team = "";
                            break;
                        case 1:
                            $team = "SPECTATOR";
                            break;
                        case 2:
                            $team = "TERRORIST";
                            break;
                        case 3:
                            $team = "CT";
                            break;
                    }

                    $this->userToEnter[$arr[1]] = $ip;
                    $this->processPlayer($arr[1], $arr[2], $team, $arr[3]);
                }
            }
        }
    }

    public function getNbRound() {
        return $this->score["team_a"] + $this->score["team_b"] + 1;
    }

    public function getStatusText($normal = true) {
        if ($normal)
            $round = "Round #";
        else
            $round = "#";
        switch ($this->getStatus()) {
            case self::STATUS_NOT_STARTED:
                return "Not started";
            case self::STATUS_STARTING:
                return "Starting";
            case self::STATUS_WU_KNIFE:
                return "Warmup Knife";
            case self::STATUS_KNIFE:
                return "Knife Round";
            case self::STATUS_END_KNIFE:
                return "Waiting choose team - Knife Round";
            case self::STATUS_WU_1_SIDE:
                return "Warmup first side";
            case self::STATUS_FIRST_SIDE:
                return "First side - " . $round . $this->getNbRound();
            case self::STATUS_WU_2_SIDE:
                return "Warmup second side";
            case self::STATUS_SECOND_SIDE:
                return "Second side - " . $round . $this->getNbRound();
            case self::STATUS_WU_OT_1_SIDE:
                return "Warmup first side OverTime";
            case self::STATUS_OT_FIRST_SIDE:
                return "First side OverTime - " . $round . $this->getNbRound();
            case self::STATUS_WU_OT_2_SIDE:
                return "Warmup second side OverTime";
            case self::STATUS_OT_SECOND_SIDE:
                return "Second side OverTime - " . $round . $this->getNbRound();
            case self::STATUS_END_MATCH:
                return "Finished";
        }
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($newStatus, $save = false) {
        $this->status = $newStatus;
        $this->websocket['match']->sendData(json_encode(array('message' => 'status', 'content' => $this->getStatusText(false), 'id' => $this->match_id)));
        $this->websocket['match']->sendData(json_encode(array('message' => 'button', 'content' => $this->getStatus(), 'id' => $this->match_id)));
        if ($save) {
            $this->message = 0;
            Logger::debug("Updating status to " . $this->getStatusText() . " in database");
            if ($newStatus == self::STATUS_END_MATCH)
                $setDisable = ", enable = '0'";
            mysql_query("UPDATE `matchs` SET status='" . $newStatus . "' " . $setDisable . " WHERE id='" . $this->match_id . "'");
        }
    }

    private function getHostname() {
        return "eBot :: " . $this->teamAName . " vs " . $this->teamBName;
    }

    private function getStreamerReady() {
        if ($this->config_streamer == "1")
            return $this->streamerReady;
        else
            return false;
    }

    private function getTeamDetails($id, $t) {
        if (is_numeric($id) && $id > 0) {
            $ds = mysql_fetch_array(mysql_query("SELECT * FROM `teams` WHERE `id` = '$id'"));
            return $ds;
        } else {
            if ($t == "a") {
                return array("name" => $this->matchData['team_a_name'], "flag" => $this->matchData['team_a_flag']);
            } elseif ($t == "b") {
                return array("name" => $this->matchData['team_b_name'], "flag" => $this->matchData['team_b_flag']);
            }
        }
    }

    public function taskExecute($name) {
        if ($name == self::SET_LIVE) {
            $this->addLog("Setting live");
            $this->enable = true;
        } elseif ($name == self::TASK_ENGAGE_MAP) {
            $tvTimeRemaining = $this->rcon->send("tv_time_remaining");
            if (preg_match('/(?<time>\d+\.\d+) seconds/', $tvTimeRemaining, $preg)) {
                TaskManager::getInstance()->addTask(new Task($this, self::TASK_ENGAGE_MAP, microtime(true) + floatval($preg['time']) + 1));
                $this->addLog("Waiting till GOTV broadcast is finished! Mapchange in " . (intval($preg['time']) + 1) . " seconds");
            } else {
                $this->engageMap();
            }
        } elseif ($name == self::CHANGE_HOSTNAME) {
            if ($this->rcon->getState()) {
                $this->rcon->send('hostname "' . $this->getHostname() . '"');
            } else {
                TaskManager::getInstance()->addTask(new Task($this, self::CHANGE_HOSTNAME, microtime(true) + 5));
            }
        } elseif ($name == self::TEST_RCON) {
            if ($this->rcon->getState()) {
                if (!$this->rcon->send('echo eBot')) {
                    $ip = explode(":", $this->server_ip);
                    try {
                        $this->rcon = new Rcon($ip[0], $ip[1], $this->rconPassword);
                        $this->rcon->send("echo eBot");
                    } catch (\Exception $ex) {
                        Logger::error("Reinit rcon failed - " . $ex->getMessage());
                        Logger::error("Trying to rengage in 10 seconds");
                        $this->addMatchLog("RCON Connection failed, trying to engage the match in 10 seconds - " . $ex->getMessage(), true);

                        \eBot\Manager\MatchManager::getInstance()->setRetry($this->match_id, \eBot\Manager\MatchManager::getInstance()->getRetry($this->match_id) + 1);
                        \eBot\Manager\MatchManager::getInstance()->delayServer($this->server_ip, 10);
                        $this->needDel = true;
                    }
                }
            }
            TaskManager::getInstance()->addTask(new Task($this, self::TEST_RCON, microtime(true) + 90));
        } elseif ($name == self::REINIT_RCON) {
            $ip = explode(":", $this->server_ip);
            try {
                $this->rcon = new Rcon($ip[0], $ip[1], $this->rconPassword);
                $this->rcon->send("echo eBot");
            } catch (\Exception $ex) {
                Logger::error("Reinit rcon failed - " . $ex->getMessage());
                Logger::error("Trying to rengage in 10 seconds");
                $this->addMatchLog("RCON Connection failed, trying to engage the match in 10 seconds - " . $ex->getMessage(), true);

                \eBot\Manager\MatchManager::getInstance()->delayServer($this->server_ip, 10);
                $this->needDel = true;
            }
        } elseif ($name == self::STOP_RECORD) {
            $this->needDelTask = false;
            $this->addLog("Stopping record & push");
            $this->rcon->send("tv_stoprecord");
            if (\eBot\Config\Config::getInstance()->getDemoDownload())
                $this->rcon->send('csay_tv_demo_push "' . $this->currentRecordName . '.dem" "http://' . \eBot\Config\Config::getInstance()->getBot_ip() . ':' . \eBot\Config\Config::getInstance()->getBot_port() . '/upload"');
            $this->currentRecordName = "";
            $this->rcon->send("exec server.cfg;");
        } elseif ($name == self::TASK_DELAY_READY) {
            if (\eBot\Config\Config::getInstance()->getDelayReady()) {
                if ($this->delay_ready_countdown > 0 && !$this->delay_ready_abort && $this->ready['ct'] && $this->ready['t']) {
                    $this->say("Match starts in " . $this->delay_ready_countdown . " seconds. !abort to stop countdown.");
                    $this->delay_ready_countdown--;
                    TaskManager::getInstance()->addTask(new Task($this, self::TASK_DELAY_READY, microtime(true) + 1));
                } elseif ($this->delay_ready_countdown == 0 && !$this->delay_ready_abort && $this->ready['ct'] && $this->ready['t']) {
                    $this->delay_ready_abort = false;
                    $this->delay_ready_countdown = 10;
                    $this->delay_ready_inprogress = false;
                    $this->startMatch(true);
                }
            }
        } elseif ($name == self::TASK_SEND_TEAM_NAMES) {
            $this->sendTeamNames();
        }
    }

    /**
     * Engaging the first map
     */
    private function engageMap() {
        $this->timeEngageMap = 0;
        if ($this->currentMap == null) {
            $this->addLog("Can't engage the map, map is null");
            return;
        }

        if ((($this->currentMap->getStatus() == Map::STATUS_STARTING) || ($this->currentMap->getStatus() == Map::STATUS_NOT_STARTED)) OR !$this->mapIsEngaged) {
            $this->addLog("Engaging the first map");

            // Warmup
            $this->rcon->send("mp_warmuptime 1");
            $this->rcon->send("mp_warmup_pausetimer 1; mp_halftime_duration 5;");
            if ($this->config_full_score) {
                $this->rcon->send("mp_match_can_clinch 0;");
            }

            // Changing map
            $this->addLog("Changing map to " . $this->currentMap->getMapName());
            if (\eBot\Config\Config::getInstance()->getWorkshop() && \eBot\Config\Config::getInstance()->getWorkshopByMap($this->currentMap->getMapName()))
                $this->rcon->send("changelevel workshop/" . \eBot\Config\Config::getInstance()->getWorkshopByMap($this->currentMap->getMapName()) . "/" . $this->currentMap->getMapName());
            else
                $this->rcon->send("changelevel " . $this->currentMap->getMapName());

            if ($this->config_kniferound) {
                $this->setStatus(self::STATUS_WU_KNIFE, true);
                $this->currentMap->setStatus(Map::STATUS_WU_KNIFE, true);
            } else {
                $this->setStatus(self::STATUS_WU_1_SIDE, true);
                $this->currentMap->setStatus(Map::STATUS_WU_1_SIDE, true);
            }

            $this->mapIsEngaged = true;

            $this->websocket['match']->sendData(json_encode(array('message' => 'currentMap', 'mapname' => $this->currentMap->getMapName(), 'id' => $this->match_id)));

            TaskManager::getInstance()->addTask(new Task($this, self::CHANGE_HOSTNAME, microtime(true) + 3));
        } else {
            $this->setStatus($this->currentMap->getStatus(), true);
            Logger::error("Map already engaged");
        }
    }

    private function isWarmupRound() {
        return ($this->getStatus() == self::STATUS_WU_1_SIDE) || ($this->getStatus() == self::STATUS_WU_2_SIDE) || ($this->getStatus() == self::STATUS_WU_KNIFE) || ($this->getStatus() == self::STATUS_WU_OT_1_SIDE) || ($this->getStatus() == self::STATUS_WU_OT_2_SIDE);
    }

    private function isMatchRound() {
        return ($this->getStatus() == self::STATUS_KNIFE) || ($this->getStatus() == self::STATUS_FIRST_SIDE) || ($this->getStatus() == self::STATUS_SECOND_SIDE) || ($this->getStatus() == self::STATUS_OT_FIRST_SIDE) || ($this->getStatus() == self::STATUS_OT_SECOND_SIDE);
    }

    public function sendRotateMessage() {
        // This is to send to players the status
        if ($this->isMatchRound() && !$this->enable) {
            if (time() - $this->lastMessage >= 8) {
                $this->say("\001Match is paused, write !continue to continue the match");
                $teamA = strtoupper($this->side['team_a']);
                $teamB = strtoupper($this->side['team_b']);

                if ($this->continue[$this->side['team_a']])
                    $teamA = "\004$teamA\001";
                if ($this->continue[$this->side['team_b']])
                    $teamB = "\004$teamB\001";
                $this->lastMessage = time();

                $message = "\003Waiting to continue the match - \005" . $this->teamAName . " \001($teamA\001) \001VS \001($teamB\001) \005" . $this->teamBName;
                $this->say($message);
            }
        }

        if ($this->isMatchRound() || $this->delay_ready_inprogress)
            return;

        if ($this->matchData["enable"] == 1) {
            if (time() - $this->lastMessage >= 8) {
                if ($this->getStatus() == self::STATUS_STARTING && $this->timeEngageMap > 0) {
                    $time = ceil(microtime(true) - $this->timeEngageMap);
                    $this->lastMessage = time();
                    $this->say("Waiting till GOTV Broadcast is finished.");
                    $this->say("The next map will start in #lightgreen" . $time . "#default seconds!");
                } else {
                    // Récupération du SIDE de l'équipe
                    $teamA = strtoupper($this->side['team_a']);
                    $teamB = strtoupper($this->side['team_b']);

                    if ($this->ready[$this->side['team_a']])
                        $teamA = "\004$teamA\001";
                    if ($this->ready[$this->side['team_b']])
                        $teamB = "\004$teamB\001";

                    $message = "";
                    // Récupération du texte
                    switch ($this->getStatus()) {
                        case self::STATUS_WU_KNIFE: $message = "Warmup Knife Round";
                            break;
                        case self::STATUS_END_KNIFE: $message = "Waiting for team select from knife winner (!stay/!switch))";
                            break;
                        case self::STATUS_WU_1_SIDE: $message = "Warmup First Side";
                            break;
                        case self::STATUS_WU_2_SIDE: $message = "Warmup Second Side";
                            break;
                        case self::STATUS_WU_OT_1_SIDE: $message = "Warmup First Side OverTime";
                            break;
                        case self::STATUS_WU_OT_2_SIDE: $message = "Warmup Second Side OverTime";
                            break;
                    }
                    if ($this->mapIsEngaged && ($this->streamerReady || !$this->config_streamer)) {
                        $messages [] = "\003Please write \006!ready \003when your team is ready !";
                        $messages [] = "\003Available commands: !help, !rules, !ready, !notready";
                    } elseif ($this->mapIsEngaged && (!$this->streamerReady || $this->config_streamer)) {
                        $messages [] = "\003Streamers are not ready yet !";
                    } else {
                        $messages [] = "\003Please write \006!map mapname \003to select the map!";
                        $maps = \eBot\Config\Config::getInstance()->getMaps();
                        foreach ($maps as $map) {
                            $mapmessage .= "$map, ";
                        }
                        $messages [] = "\003" . substr($mapmessage, 0, -2);
                    }

                    if ($message)
                        $messages [] = "\003$message - \005" . $this->teamAName . " \001($teamA\001) \001VS \001($teamB\001) \005" . $this->teamBName;

                    $adverts = \eBot\Config\Config::getInstance()->getAdvertising($this->season_id);

                    for ($i = 0; $i < count($adverts['season_id']); $i++) {
                        $messages [] = "\003" . $adverts['message'][$i];
                        if ($message)
                            $messages [] = "\003$message - \005" . $this->teamAName . " \001($teamA\001) \001VS \001($teamB\001) \005" . $this->teamBName;
                    }

                    $message = $messages[$this->message++ % count($messages)];
                    $this->lastMessage = time();
                    $this->say($message);
                }
            }
        }
    }

    public function say($message) {
        /**
         * \001 white
         * \002 red
         * \003 white
         * \004 green
         * \005 lightgreen
         * \006 lightgreen2
         * \007 lightred
         */
        $message = str_replace("#default", "\001", $message);
        $message = str_replace("#green", "\004", $message);
        $message = str_replace("#lightgreen2", "\006", $message);
        $message = str_replace("#lightgreen", "\005", $message);
        $message = str_replace("#red", "\002", $message);
        $message = str_replace("#lightred", "\007", $message);

        // temporary fix because of ugly chatcolor after last csgo update
        $message = str_replace("\003", "\001", $message);

        try {
            if (!$this->pluginCsay) {
                $message = str_replace(array("\001", "\002", "\003", "\004", "\005", "\006", "\007"), array("", "", "", "", "", "", ""), $message);
                $message = str_replace(";", ",", $message);
                $this->rcon->send('say eBot: ' . addslashes($message) . '');
            } else {
                $message = str_replace('"', '\\"', $message);
                $this->rcon->send('csay_all "' . "e\004Bot\001: " . $message . '"');
            }
        } catch (\Exception $ex) {
            Logger::error("Say failed - " . $ex->getMessage());
        }
    }

    public function destruct() {
        $this->websocket['logger']->sendData('removeMatch_' . $this->match_id);
        TaskManager::getInstance()->removeAllTaskForObject($this);
        unset($this->rcon);
        $this->addLog("Destructing match " . $this->match_id);
    }

    public function getNeedDel() {
        return $this->needDel;
    }

    public function getNeedDelTask() {
        return $this->needDelTask;
    }

    /**
     * Process a message from log stream
     * @param String $message
     */
    public function processMessage($message) {
        if ($this->getStatus() >= self::STATUS_END_MATCH)
            return;

        $message = $this->messageManager->processMessage($message);

        if ($message != null) {
            switch (get_class($message)) {
                case "eBot\Message\Type\BombDefusing":
                    return $this->processBombDefusing($message);
                case "eBot\Message\Type\BombPlanting":
                    return $this->processBombPlanting($message);
                case "eBot\Message\Type\ChangeMap":
                    return $this->processChangeMap($message);
                case "eBot\Message\Type\ChangeName":
                    return $this->processChangeName($message);
                case "eBot\Message\Type\Connected":
                    return $this->processConnected($message);
                case "eBot\Message\Type\Disconnected":
                    return $this->processDisconnected($message);
                case "eBot\Message\Type\EnteredTheGame":
                    return $this->processEnteredTheGame($message);
                case "eBot\Message\Type\GotTheBomb":
                    return $this->processGotTheBomb($message);
                case "eBot\Message\Type\JoinTeam":
                    return $this->processJoinTeam($message);
                case "eBot\Message\Type\Kill":
                    return $this->processKill($message);
                case "eBot\Message\Type\KillAssist":
                    return $this->processKillAssist($message);
                case "eBot\Message\Type\RoundRestart":
                    return $this->processRoundRestart($message);
                case "eBot\Message\Type\RoundScored":
                    return $this->processRoundScored($message);
                case "eBot\Message\Type\RemindRoundScored":
                    return $this->processRemindRoundScored($message);
                case "eBot\Message\Type\RoundStart":
                    return $this->processRoundStart($message);
                case "eBot\Message\Type\RoundSpawn":
                    return $this->processRoundSpawn($message);
                case "eBot\Message\Type\Say":
                    return $this->processSay($message);
                case "eBot\Message\Type\ThrewStuff":
                    return $this->processThrewStuff($message);
                case "eBot\Message\Type\Purchased":
                    return $this->processPurchased($message);
                case "eBot\Message\Type\TeamScored":
                    return $this->processTeamScored($message);
                case "eBot\Message\Type\RoundEnd":
                    return $this->processRoundEnd($message);
                default:
                    $this->addLog("Message non traité: " . get_class($message));
                    break;
            }
        }
    }

    private $tempScoreA = null;
    private $tempScoreB = null;

    private function processTeamScored(\eBot\Message\Type\TeamScored $message) {
        /* if (!$this->roundEndEvent) {
          if ($message->team == $this->side['team_a']) {
          $this->tempScoreA = $message->score;
          $this->addLog("Score for " . $this->teamAName . ": " . $this->tempScoreA);
          } else {
          $this->tempScoreB = $message->score;
          $this->addLog("Score for " . $this->teamBName . ": " . $this->tempScoreB);
          }
          } */
    }

    private function processRoundEnd(\eBot\Message\Type\RoundEnd $message) {
        if (!$this->roundEndEvent) {
            $this->addLog("RoundEnd catched, but no RoundScored");
            $lastRoundEnds = $this->rcon->send("ebot_get_last_roundend");
            $lastRoundEnds = explode("\n", $lastRoundEnds);

            $message = new \eBot\Message\CSGO\RoundScored();
            $data = trim(str_replace("#", "", $lastRoundEnds[0]));
            $data = str_replace("scored", "triggered", $data);
            $this->addLog($data);
            if ($message->match($data)) {
                $this->processRoundScored($message->process());
            }
        }
    }

    private function processChangeMap(\eBot\Message\Type\ChangeMap $message) {
        Logger::debug("Processing Change Map");
		
		if (preg_match("!CRC!", $message->maps)) {
			$this->addLog("Wrong map name ".$message->maps);
			return;
		}
		
		if ($this->currentMap->getMapName() == "tba" || $this->getStatus() > 2 || strpos($message->maps,$this->currentMap->getMapName()) !== false  ) {
			$this->addLog("Loading maps " . $message->maps);
			$this->addMatchLog("Loading maps " . $message->maps);
			$ip = explode(":", $this->server_ip);
			try {
				$this->rcon = new Rcon($ip[0], $ip[1], $this->rconPassword);
				$this->rcon->send("echo eBot;");

				if ($this->matchData["config_password"] != "") {
					$this->rcon->send("sv_password \"" . $this->matchData["config_password"] . "\"");
				}

				$this->rcon->send("mp_warmup_pausetimer 1");

				if ($this->config_ot) {
					$this->rcon->send("mp_overtime_enable 1");
					$this->rcon->send("mp_overtime_maxrounds " . ($this->ot_maxround * 2));
					$this->rcon->send("mp_overtime_startmoney " . $this->ot_startmoney);
					$this->rcon->send("mp_overtime_halftime_pausetimer 1");
				}

				$this->sendTeamNames();
			} catch (\Exception $ex) {
				Logger::error("Reinit rcon failed - " . $ex->getMessage());
				TaskManager::getInstance()->addTask(new Task($this, self::REINIT_RCON, microtime(true) + 1));
			}
		} else {
			$this->addLog("Wrong map loaded " . $message->maps. " need ".$this->currentMap->getMapName());
			$this->addMatchLog("Wrong map loaded " . $message->maps. " need ".$this->currentMap->getMapName());
			$ip = explode(":", $this->server_ip);
			try {
				$this->rcon = new Rcon($ip[0], $ip[1], $this->rconPassword);
				$this->rcon->send("echo eBot;");
				$this->rcon->send("changelevel ".$this->currentMap->getMapName());
			} catch (\Exception $ex) {
				Logger::error("Reinit rcon failed - " . $ex->getMessage());
				TaskManager::getInstance()->addTask(new Task($this, self::REINIT_RCON, microtime(true) + 1));
			}
		}
    }

    /**
     * Processing message for planting bomb.
     * Setting the gameBombPlanter to the user
     * @param \eBot\Message\Type\BombPlanting $message
     */
    private function processBombPlanting(\eBot\Message\Type\BombPlanting $message) {
        Logger::debug("Processing Bomb Planting");

        // Getting the player who planted the bomb
        $user = $this->processPlayer($message->getUserId(), $message->getUserName(), $message->getUserTeam(), $message->getUserSteamid());
        $this->gameBombPlanter = $user;

        $this->addLog($message->getUserName() . " planted the bomb");
        $this->addMatchLog($this->getColoredUserNameHTML($message->getUserName(), $message->getUserTeam()) . " planted the bomb");

        // Dispatching events
        $event = new \eBot\Events\Event\BombPlanting();
        $event->setMatch($this);
        $event->setUserId($message->getUserId());
        $event->setUserName($message->getUserName());
        $event->setUserTeam($message->getUserTeam());
        $event->setUserSteamid($message->getUserSteamid());
        \eBot\Events\EventDispatcher::getInstance()->dispatchEvent($event);

        // Round TimeLine
        $text = addslashes(serialize(array("id" => $user->getId(), "name" => $message->getUserName())));
        \mysql_query("
                    INSERT INTO `round`
                    (`match_id`,`map_id`,`event_name`,`event_text`,`event_time`,`round_id`,`created_at`,`updated_at`)
                        VALUES
                    ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', 'bomb_planting', '" . $text . "', '" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                        ");
    }

    /**
     * Processing message for defusing bomb.
     * Setting the gameBombDefuser to the user
     * @param \eBot\Message\Type\BombDefusing $message
     */
    private function processBombDefusing(\eBot\Message\Type\BombDefusing $message) {
        Logger::debug("Processing Bomb Defusing");
        // Getting the player who is defusing the bomb
        $user = $this->processPlayer($message->getUserId(), $message->getUserName(), $message->getUserTeam(), $message->getUserSteamid());
        $this->gameBombDefuser = $user;

        $this->addLog($message->getUserName() . " is defusing bomb");
        $this->addMatchLog($this->getColoredUserNameHTML($message->getUserName(), $message->getUserTeam()) . " is defusing bomb");

        // Dispatching events
        $event = new \eBot\Events\Event\BombDefusing();
        $event->setMatch($this);
        $event->setUserId($message->getUserId());
        $event->setUserName($message->getUserName());
        $event->setUserTeam($message->getUserTeam());
        $event->setUserSteamid($message->getUserSteamid());
        \eBot\Events\EventDispatcher::getInstance()->dispatchEvent($event);

        // Round TimeLine
        $text = addslashes(serialize(array("id" => $user->getId(), "name" => $message->getUserName())));
        \mysql_query("
                    INSERT INTO `round`
                    (`match_id`,`map_id`,`event_name`,`event_text`,`event_time`,`round_id`,`created_at`,`updated_at`)
                        VALUES
                    ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', 'bomb_defusing', '" . $text . "', '" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                        ");
    }

    private function processThrewStuff(\eBot\Message\Type\ThrewStuff $message) {
        Logger::debug("Processing ThrewStuff Message");

        if (!$this->waitForRestart && $this->enable && in_array($this->getStatus(), array(self::STATUS_FIRST_SIDE, self::STATUS_SECOND_SIDE, self::STATUS_OT_FIRST_SIDE, self::STATUS_OT_SECOND_SIDE))) {
            $user = $this->processPlayer($message->getUserId(), $message->getUserName(), $message->getUserTeam(), $message->getUserSteamid());

            \mysql_query("INSERT INTO `players_heatmap` (`match_id`,`map_id`,`event_name`,`event_x`,`event_y`,`event_z`,`player_name`,`player_id`,`player_team`,`round_id`,`round_time`, `created_at`,`updated_at`)
                VALUES
                (" . $this->match_id . ", " . $this->currentMap->getMapId() . ", '" . $message->stuff . "', '" . $message->posX . "', '" . $message->posY . "', '" . $message->posZ . "', '" . addslashes($message->userName) . "', '" . $user->getId() . "', '" . $message->userTeam . "', '" . $this->getNbRound() . "', '" . $this->getRoundTime() . "', NOW(), NOW())
                ");

            $this->addLog($message->userName . " (" . $message->userTeam . ") threw " . $message->stuff . " at [" . $message->posX . " " . $message->posY . " " . $message->posZ . "]");
        }
    }

    private function processPurchased(\eBot\Message\Type\Purchased $message) {
        Logger::debug("Processing Purchased Message");
        if (!$this->waitForRestart && $this->enable && in_array($this->getStatus(), array(self::STATUS_FIRST_SIDE, self::STATUS_SECOND_SIDE, self::STATUS_OT_FIRST_SIDE, self::STATUS_OT_SECOND_SIDE))) {
            $user = $this->processPlayer($message->getUserId(), $message->getUserName(), $message->getUserTeam(), $message->getUserSteamid());

            $text = \addslashes(\serialize(array("item" => $message->object, "player" => $user->getId(), "playerName" => $user->get("name"))));

            \mysql_query("
                        INSERT INTO `round`
                        (`match_id`,`map_id`,`event_name`,`event_text`,`event_time`,`round_id`,`created_at`,`updated_at`)
                            VALUES
                        ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', 'purchased', '$text', '" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                            ");

//          $this->addLog($message->userName . " (" . $message->userTeam . ") purchased " . $message->object);
        }
    }

    /**
     * Processing say message
     * Made action with the message
     * Dispatch a Say event
     * @param \eBot\Message\Type\Say $message
     */
    private function processSay(\eBot\Message\Type\Say $message) {
        Logger::debug("Processing Say Message");

        $user = $this->processPlayer($message->getUserId(), $message->getUserName(), $message->getUserTeam(), $message->getUserSteamid());

        $text = trim($message->getText());
        if (preg_match('/\!map (?<mapname>.*)/i', $text, $preg)) {
            if (!$this->mapIsEngaged && (( $this->getStatus() == self::STATUS_WU_KNIFE && $this->config_kniferound ) || ( $this->getStatus() == self::STATUS_WU_1_SIDE && !$this->config_kniferound ))) {
                $this->addLog($message->getUserName() . " (" . $message->getUserTeam() . ") wants to play " . $preg['mapname']);
                Logger::log($message->getUserName() . " (" . $message->getUserTeam() . ") wants to play " . $preg['mapname']);
                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->teamAName : $this->teamBName;
                    $maps = \eBot\Config\Config::getInstance()->getMaps();
                    if (in_array($preg['mapname'], $maps)) {
                        $this->playMap['ct'] = $preg['mapname'];
                        $this->say($team . " (CT) \003wants to play \004" . $preg['mapname']);
                    } else {
                        $this->say($preg['mapname'] . " was not found! Available maps are:");
                        foreach ($maps as $map) {
                            $mapmessage .= "$map, ";
                        }
                        $this->say(substr($mapmessage, 0, -2));
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->teamAName : $this->teamBName;

                    $maps = \eBot\Config\Config::getInstance()->getMaps();
                    if (in_array($preg['mapname'], $maps)) {
                        $this->playMap['t'] = $preg['mapname'];
                        $this->say($team . " (T) \003wants to play \004" . $preg['mapname']);
                    } else {
                        $this->say($preg['mapname'] . " was not found! Available maps are:");
                        foreach ($maps as $map) {
                            $mapmessage .= "$map, ";
                        }
                        $this->say(substr($mapmessage, 0, -2));
                    }
                }

                $this->setMatchMap($preg['mapname']);
            } else {
                $this->addLog("Veto isn't enabled");
            }
        } elseif ($text == "!stats") {
            $this->addLog($message->getUserName() . " ask his stats");

            if ($user) {
                $this->rcon->send("csay_to_player " . $message->userId . " \"e\004Bot\001: \001stats pour \003" . $message->userName . "\"");
                if ($user->get("death") == 0) {
                    $ratio = $user->get("kill");
                } else {
                    $ratio = round($user->get("kill") / $user->get("death"), 2);
                }

                if ($user->get("kill") == 0) {
                    $ratiohs = 0;
                } else {
                    $ratiohs = round(($user->get("hs") / $user->get("kill")) * 100, 2);
                }

                $this->rcon->send("csay_to_player " . $message->userId . " \" \005Kill: \004" . $user->get("kill") . " \001- \005HS: \004" . $user->get("hs") . "\"");
                $this->rcon->send("csay_to_player " . $message->userId . " \" \005Death: \004" . $user->get("death") . " \001- \005Score: \004" . $user->get("point") . "\"");
                $this->rcon->send("csay_to_player " . $message->userId . " \" \005Ratio K/D: \004" . $ratio . " \001- \005HS%: \004" . $ratiohs . "\"");
            } else {
                $this->rcon->send("csay_to_player " . $message->userId . " \"e\004Bot\001: pas de stats pour le moment pour \003" . $message->userName . "\"");
            }
        } elseif ($text == "!morestats") {
            $this->addLog($message->getUserName() . " ask more stats");

            if ($user) {
                $this->rcon->send('csay_to_player ' . $message->userId . " \"e\004Bot\001: stats pour \003" . $message->userName . "\"");

                $stats = array();
                for ($i = 1; $i <= 5; $i++) {
                    if ($user->get("v$i") > 0) {
                        $stats[] = array("name" => "1v$i", "val" => $user->get("v$i"));
                    }

                    if ($user->get("k$i") > 0) {
                        $stats[] = array("name" => $i . " kill/round", "val" => $user->get("k$i"));
                    }
                }

                if ($user->get("bombe") > 0) {
                    $stats[] = array("name" => "Bombe", "val" => $user->get("bombe"));
                }

                if ($user->get("defuse") > 0) {
                    $stats[] = array("name" => "Defuse", "val" => $user->get("defuse"));
                }

                if ($user->get("tk") > 0) {
                    $stats[] = array("name" => "TK", "val" => $user->get("tk"));
                }

                if ($user->get("firstKill") > 0) {
                    $stats[] = array("name" => "First Kill", "val" => $user->get("firstKill"));
                }

                $messageText = "";
                $count = 0;
                $doit = true;
                foreach ($stats as $v) {
                    $count++;
                    $doit = false;
                    if ($messageText == "")
                        $messageText = " \005" . $v["name"] . ": \004" . $v["val"];
                    else
                        $messageText .= " \001- \005" . $v["name"] . ": \004" . $v["val"];

                    if ($count == 2) {
                        $this->rcon->send('csay_to_player ' . $message->userId . ' "' . $messageText . '"');
                        $messageText = "";
                        $count = 0;
                    }
                }

                if ($count > 0) {
                    $this->rcon->send('csay_to_player ' . $message->userId . ' "' . $messageText . '"');
                }

                if ($doit) {
                    $this->rcon->send('csay_to_player ' . $message->userId . " \"e\004Bot\001: Pas de stats pour le moment\"");
                }
            } else {
                $this->rcon->send('csay_to_player ' . $message->userId . " \"e\004Bot\001: Pas de stats pour le moment pour \005" . $message->userName . '"');
            }
        } elseif ($text == "!rules") {
            if ($this->pluginCsay) {
                $this->addLog($message->getUserName() . " ask rules");

                $this->rcon->send("csay_to_player " . $message->userId . " \"e\004Bot\001: Full Score: \005" . (($this->config_full_score) ? "yes" : "no") . " \001:: Switch Auto: \005" . (($this->config_switch_auto) ? "yes" : "no") . "\"");
                $this->rcon->send("csay_to_player " . $message->userId . " \"e\004Bot\001: Over Time: \005" . (($this->config_ot) ? "yes" : "no") . " \001:: MaxRound: \005" . $this->maxRound . "\"");
            }
        } elseif ($text == "!help") {
            if ($this->pluginCsay) {
                $this->addLog($message->getUserName() . " ask help");
                $this->rcon->send("csay_to_player " . $message->userId . " \"e\004Bot\001: commands available: !help, !status, !stats, !morestats, !score, !ready, !notready, !stop, !restart (for knife round), !stay, !switch\"");
            }
        } elseif ($text == "!restart") {
            $this->addLog($message->getUserName() . " say restart");
            if (($this->getStatus() == self::STATUS_KNIFE) || ($this->getStatus() == self::STATUS_END_KNIFE)) {
                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->teamAName : $this->teamBName;

                    if (!$this->restart['ct']) {
                        $this->restart['ct'] = true;
                        $this->say($team . " (CT) \003want to restart the knife");
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->teamAName : $this->teamBName;

                    if (!$this->restart['t']) {
                        $this->restart['t'] = true;
                        $this->say($team . " (T) \003want to restart the knife");
                    }
                }

                if ($this->restart["ct"] && $this->restart["t"]) {
                    $this->ready["ct"] = true;
                    $this->ready["t"] = true;

                    $this->setStatus(self::STATUS_WU_KNIFE);
                    $this->currentMap->setStatus(Map::STATUS_WU_KNIFE);

                    $this->restart["ct"] = false;
                    $this->restart["ct"] = false;

                    $this->addMatchLog("Restarting knife");
                    $this->addLog("Restarting knife");
                    $this->startMatch(true);
                }
            }
        } elseif (($text == "!stop"  || $text == ".stop") && !\eBot\Config\Config::getInstance()->getConfigStopDisabled()) {
            if ($this->isMatchRound() && $this->enable) {
                $this->addLog($message->getUserName() . " (" . $message->getUserTeam() . ") say stop");

                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->teamAName : $this->teamBName;

                    if (!$this->stop['ct']) {
                        $this->stop['ct'] = true;
                        $this->say($team . " (CT) \003want to stop");
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->teamAName : $this->teamBName;

                    if (!$this->stop['t']) {
                        $this->stop['t'] = true;
                        $this->say($team . " (T) \003want to stop");
                    }
                }

                $this->stopMatch();
            } else {
                if (!$this->enable) {
                    $this->addLog("Can't stop, it's already stop");
                }
            }
        } elseif ($text == "!continue" || $text == ".continue") {
            if ($this->isMatchRound() && !$this->enable) {
                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->teamAName : $this->teamBName;

                    if (!$this->continue['ct']) {
                        $this->continue['ct'] = true;
                        $this->say($team . " (CT) \003want to go live");
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->teamAName : $this->teamBName;

                    if (!$this->continue['t']) {
                        $this->continue['t'] = true;
                        $this->say($team . " (T) \003want to go live");
                    }
                }

                $this->continueMatch();
            }
        } elseif ($text == "!ready" || $text == ".ready") {
            if ($this->isWarmupRound() && $this->mapIsEngaged) {
                if ($this->config_streamer && !$this->getStreamerReady()) {
                    $this->say("\002Streamers are not ready yet.");
                    $this->say("\001Please wait, till they are ready.");
                } else {
                    $this->addLog($message->getUserName() . " (" . $message->getUserTeam() . ") say ready");

                    if ($message->getUserTeam() == "CT") {
                        if (($this->getStatus() == self::STATUS_WU_2_SIDE) || ($this->getStatus() == self::STATUS_WU_OT_2_SIDE)) {
                            $team = ($this->side['team_a'] == "ct") ? $this->teamBName : $this->teamAName;
                        } else {
                            $team = ($this->side['team_a'] == "ct") ? $this->teamAName : $this->teamBName;
                        }

                        if (!$this->ready['ct']) {
                            $this->ready['ct'] = true;
                            $this->say($team . " (CT) \003is now \004ready");
                        } else {
                            $this->say($team . " (CT) \003is already \004ready");
                        }
                    } elseif ($message->getUserTeam() == "TERRORIST") {
                        if (($this->getStatus() == self::STATUS_WU_2_SIDE) || ($this->getStatus() == self::STATUS_WU_OT_2_SIDE)) {
                            $team = ($this->side['team_a'] == "t") ? $this->teamBName : $this->teamAName;
                        } else {
                            $team = ($this->side['team_a'] == "t") ? $this->teamAName : $this->teamBName;
                        }


                        if (!$this->ready['t']) {
                            $this->ready['t'] = true;
                            $this->say($team . " (T) \003is now \004ready");
                        } else {
                            $this->say($team . " (T) \003is already \004ready");
                        }
                    }

                    $this->startMatch();
                }
            }
        } elseif ($text == "!pause" || $text == ".pause") {
            if ($this->isMatchRound() && !$this->isPaused && $this->enable) {
                $this->addLog($message->getUserName() . " (" . $message->getUserTeam() . ") say pause");

                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->teamAName : $this->teamBName;

                    if (!$this->pause['ct']) {
                        $this->pause['ct'] = true;
                        if (\eBot\Config\Config::getInstance()->getPauseMethod() == "instantConfirm")
                            $this->say($team . " (CT) \003want to pause, write !pause to confirm");
                        elseif (\eBot\Config\Config::getInstance()->getPauseMethod() == "instantNoConfirm")
                            $this->say($team . " (CT) \003 will be paused now!");
                        else
                            $this->say($team . " (CT) \003want to pause, match will be paused next freezetime.");
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->teamAName : $this->teamBName;

                    if (!$this->pause['t']) {
                        $this->pause['t'] = true;
                        if (\eBot\Config\Config::getInstance()->getPauseMethod() == "instantConfirm")
                            $this->say($team . " (T) \003want to pause, write !pause to confirm");
                        elseif (\eBot\Config\Config::getInstance()->getPauseMethod() == "instantNoConfirm")
                            $this->say($team . " (T) \003 will be paused now!");
                        else
                            $this->say($team . " (T) \003want to pause, match will be paused next freezetime.");
                    }
                }
                $this->pauseMatch();
            }
        } elseif ($text == "!unpause" || $text == ".unpause") {
            if ($this->isMatchRound() && $this->isPaused && $this->enable) {
                $this->addLog($message->getUserName() . " (" . $message->getUserTeam() . ") say pause");

                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->teamAName : $this->teamBName;

                    if (!$this->unpause['ct']) {
                        $this->unpause['ct'] = true;
                        $this->say($team . " (CT) \003want to remove pause, write !unpause to confirm");
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->teamAName : $this->teamBName;

                    if (!$this->unpause['t']) {
                        $this->unpause['t'] = true;
                        $this->say($team . " (T) \003want to remove pause, write !unpause to confirm");
                    }
                }

                $this->unpauseMatch();
            }
        } elseif (($this->getStatus() == self::STATUS_END_KNIFE) && ($text == "!stay" || $text == ".stay")) {
            if ($message->getUserTeam() == $this->winKnife) {
                $this->addLog($message->getUserName() . " want to stay, going to warmup");

                $this->setStatus(self::STATUS_WU_1_SIDE, true);
                $this->currentMap->setStatus(Map::STATUS_WU_1_SIDE, true);

                $this->rcon->send("mp_do_warmup_period 1");
                $this->rcon->send("mp_warmuptime 30");
                $this->rcon->send("mp_warmup_pausetimer 1");
                $this->rcon->send("mp_warmup_start");
                $this->say("nothing change, going to warmup");
            }
        } elseif (($this->getStatus() == self::STATUS_END_KNIFE) && ($text == "!switch" || $text == ".switch" || $text == "!swap" || $text == ".swap")) {
            if ($message->getUserTeam() == $this->winKnife) {
                $this->addLog($message->getUserName() . " want to stay, going to warmup");

                $this->setStatus(self::STATUS_WU_1_SIDE, true);
                $this->currentMap->setStatus(Map::STATUS_WU_1_SIDE, true);

                $this->swapSides();

                $this->rcon->send("mp_do_warmup_period 1");
                $this->rcon->send("mp_warmuptime 30");
                $this->rcon->send("mp_warmup_pausetimer 1");
                $this->rcon->send("mp_warmup_start");
                $this->say("Swapping teams");
                $this->rcon->send("mp_swapteams");
                TaskManager::getInstance()->addTask(new Task($this, self::TASK_SEND_TEAM_NAMES, microtime(true) + 10));
            }
        } elseif ($text == "!notready" || $text == ".notready" || $text == "!unready" || $text == ".unready") {
            if ($this->isWarmupRound() && $this->mapIsEngaged) {
                $this->addLog($message->getUserName() . " (" . $message->getUserTeam() . ") say notready");

                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->teamAName : $this->teamBName;

                    if ($this->ready['ct']) {
                        $this->ready['ct'] = false;
                        $this->say($team . " (CT) \003is now \004not ready");
                    } else {
                        $this->say($team . " (CT) \003is already \004not ready");
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->teamAName : $this->teamBName;

                    if ($this->ready['t']) {
                        $this->ready['t'] = false;
                        $this->say($team . " (T) \003is now \004not ready");
                    } else {
                        $this->say($team . " (T) \003is already \004not ready");
                    }
                }
            }
        } elseif (($text == "abort" || $text == ".abort") && $this->delay_ready_inprogress) {
            if ($this->isWarmupRound() && $this->ready['ct'] && $this->ready['t'] && \eBot\Config\Config::getInstance()->getDelayReady()) {
                $this->addLog($message->getUserName() . " (" . $message->getUserTeam() . ") say abort");
                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->teamAName : $this->teamBName;
                    $this->say($team . " (CT) \004aborted \003the ready countdown");
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->teamAName : $this->teamBName;
                    $this->say($team . " (T) \004aborted \003the ready countdown");
                }
                $this->abortReady();
            }
        } elseif ($text == "!status") {
            if ($this->pluginCsay) {
                $this->addLog($message->getUserName() . " ask status");
                if ($this->enable) {
                    $this->rcon->send("csay_to_player " . $message->userId . " \"e\004Bot\001: Current status: \002" . $this->getStatusText() . "\"");
                } else {
                    $this->rcon->send("csay_to_player " . $message->userId . " \"e\004Bot\001: Current status: \002" . $this->getStatusText() . " - Match paused\"");
                }
            }
        } elseif ($text == "!status") {
            if ($this->pluginCsay) {
                $this->addLog($message->getUserName() . " ask status");
                $this->rcon->send("csay_to_player " . $message->userId . " \"e\004Bot\001: \005" . $this->teamAName . " \004" . $this->currentMap->getScore1() . " \001- \004" . $this->currentMap->getScore2() . " \005" . $this->teamBName . "\"");
            }
        } else {
            // Dispatching events
            $event = new \eBot\Events\Event\Say();
            $event->setMatch($this);
            $event->setUserId($message->getUserId());
            $event->setUserName($message->getUserName());
            $event->setUserTeam($message->getUserTeam());
            $event->setUserSteamid($message->getUserSteamid());
            $event->setType($message->getType());
            $event->setText($message->getText());
            \eBot\Events\EventDispatcher::getInstance()->dispatchEvent($event);
        }

        switch ($message->getType()) {
            case \eBot\Message\Type\Say::SAY:
                $this->addMatchLog($this->getColoredUserNameHTML($message->getUserName(), $message->getUserTeam()) . ": " . htmlentities($message->getText()));
                break;
            case \eBot\Message\Type\Say::SAY_TEAM:
                $this->addMatchLog($this->getColoredUserNameHTML($message->getUserName(), $message->getUserTeam()) . " (private): " . htmlentities($message->getText()), true);
                break;
        }
    }

    private function processRoundScored(\eBot\Message\Type\RoundScored $message) {
        $this->addLog("RoundScore : " . $message->getTeamWin());

        if ($this->getStatus() == self::STATUS_KNIFE) {
            $this->winKnife = ($message->getTeamWin() == "T") ? "TERRORIST" : $message->getTeamWin();
            $this->addLog($message->getTeamWin() . " won the knife round");

            $this->setStatus(self::STATUS_END_KNIFE, true);
            $this->currentMap->setStatus(Map::STATUS_END_KNIFE, true);

            $team = ($this->side['team_a'] == \strtolower($message->getTeamWin())) ? $this->teamAName : $this->teamBName;

            $this->say("\005$team won the knife, !stay or !switch");

            $this->roundEndEvent = true;
            return;
        }

        if (!$this->waitForRestart && $this->enable && in_array($this->getStatus(), array(self::STATUS_FIRST_SIDE, self::STATUS_SECOND_SIDE, self::STATUS_OT_FIRST_SIDE, self::STATUS_OT_SECOND_SIDE))) {
            // Add point
            foreach ($this->players as $player)
                $player->saveKillRound();

            $teamWin = $this->currentMap->addRound($message->getTeamWin());

            $bestActionType = "";
            $bestActionParam = array();

            if ($message->type != "saved") {
                if ($this->specialSituation['active']) {
                    $nbAlive = 0;
                    foreach ($this->players as $k => $v) {
                        if ($v->get("alive") && ($v->get("currentSide") != "other")) {
                            $nbAlive++;
                        }
                    }

                    $team = strtolower($message->team_win);

                    if ($nbAlive == 1) {
                        if (($this->specialSituation['side'] == strtolower($team)) || ($this->specialSituation['side'] == "both") || ($this->specialSituation['side2'] == "both")) {

                            if ($this->specialSituation['side2'] == "both") {
                                if ($this->specialSituation['side'] != "both") {
                                    $nbAlive = 0;
                                    foreach ($this->players as $k => $v) {
                                        if ($v->get("alive") && ($v->get("currentSide") != "other")) {
                                            $nbAlive++;
                                            $id = $k;
                                        }
                                    }

                                    if ($this->specialSituation['id'] != $id) {
                                        $this->addLog("situationSpecialchecker2 found another player");
                                        $this->specialSituation['side'] = "both";
                                        $this->specialSituation['situation'] = 1;
                                    }
                                }
                            }


                            if ($this->specialSituation['side'] == "both") {
                                $id = 0;
                                $nbAlive = 0;
                                foreach ($this->players as $k => $v) {
                                    if ($v->get("alive") && ($v->get("currentSide") != "other")) {
                                        $nbAlive++;
                                        $id = $k;
                                    }
                                }

                                $this->specialSituation['id'] = $id;

                                if ($nbAlive == 2) {
                                    $this->addLog("Incohérence situation spéciale");
                                } else {
                                    if ($this->players[$id]) {
                                        $bestActionType = "1v1";
                                        $bestActionParam = array("player" => $this->players[$id]->getId(), "playerName" => $this->players[$id]->get("name"));
                                        mysql_query("UPDATE players SET nb1 = nb1 + 1 WHERE id = '" . $this->players[$id]->getId() . "'") or Logger::error("Can't update " . $this->players[$id]->getId() . " situation");
                                        $this->addLog("Situation spécial réussie 1v" . $this->specialSituation['situation'] . " (" . $this->players[$id]->get("name") . ")");
                                        $this->addMatchLog("<b>" . htmlentities($this->players[$id]->get("name")) . "</b> a mis un 1v" . $this->specialSituation['situation'] . " !");
                                        $this->players[$id]->inc("v1");

                                        $text = \addslashes(\serialize(array("situation" => 1, "player" => $this->players[$id]->getId(), "playerName" => $this->players[$id]->get("name"))));

                                        // Round TimeLine
                                        \mysql_query("
                                            INSERT INTO `round`
                                            (`match_id`,`map_id`,`event_name`,`event_text`,`event_time`,`round_id`,`created_at`,`updated_at`)
                                                VALUES
                                            ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', '1vx_ok', '$text','" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                                                ");
                                    }
                                }
                            } else {
                                $id = $this->specialSituation['id'];
                                if ($this->players[$id]) {
                                    $bestActionType = "1v" . $this->specialSituation['situation'];
                                    $bestActionParam = array("player" => $this->players[$id]->getId(), "playerName" => $this->players[$id]->get("name"));

                                    $this->addMatchLog("<b>" . htmlentities($this->players[$id]->get("name")) . "</b> a mis un 1v" . $this->specialSituation['situation'] . " !");
                                    mysql_query("UPDATE players SET nb" . $this->specialSituation['situation'] . " = nb" . $this->specialSituation['situation'] . " + 1 WHERE id='" . $this->players[$id]->getId() . "'") or Logger::error("Can't update " . $this->players[$id]->getId() . " situation");
                                    $this->players[$id]->inc("v" . $this->specialSituation['situation']);
                                    $this->addLog("Situation spécial réussie 1v" . $this->specialSituation['situation'] . " (" . $this->players[$id]->get("name") . ")");

                                    $text = \addslashes(\serialize(array("situation" => $this->specialSituation['situation'], "player" => $this->players[$id]->getId(), "playerName" => $this->players[$id]->get("name"))));

                                    // Round TimeLine
                                    \mysql_query("
                                    INSERT INTO `round`
                                    (`match_id`,`map_id`,`event_name`,`event_text`,`event_time`,`round_id`,`created_at`,`updated_at`)
                                        VALUES
                                    ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', '1vx_ok', '$text','" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                                        ");
                                }
                            }
                        }
                    } else {
                        $this->addLog("Situation ratée - alive players: $nbAlive");
                    }
                }
            }

            if ($message->type == "bombdefused") {
                if ($this->gameBombDefuser != null) {
                    $this->gameBombDefuser->inc("defuse");
                    $this->gameBombDefuser->inc("point", 3);
                    $this->gameBombDefuser->saveScore();

                    // Round TimeLine
                    \mysql_query("
                        INSERT INTO `round`
                        (`match_id`,`map_id`,`event_name`,`event_time`,`round_id`,`created_at`,`updated_at`)
                            VALUES
                        ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', 'bomb_defused', '" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                            ");
                }
            }

            if ($message->type == "bombeexploded") {
                if ($this->gameBombPlanter != null) {
                    $this->gameBombPlanter->inc("bombe");
                    $this->gameBombPlanter->inc("point", 2);
                    $this->gameBombPlanter->saveScore();

                    // Round TimeLine
                    \mysql_query("
                        INSERT INTO `round`
                        (`match_id`,`map_id`,`event_name`,`event_time`,`round_id`,`created_at`,`updated_at`)
                            VALUES
                        ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', 'bomb_exploded', '" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                            ");
                }
            }

            // Round TimeLine
            \mysql_query("
                        INSERT INTO `round`
                        (`match_id`,`map_id`,`event_name`,`event_time`,`round_id`,`created_at`,`updated_at`)
                            VALUES
                        ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', 'round_end', '" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                            ");

            $this->score["team_a"] = $this->currentMap->getScore1();
            $this->score["team_b"] = $this->currentMap->getScore2();

            if ($this->getNbRound() == $this->maxRound - 1) {
                // Ensure that halftime_pausetimer is set
                $this->rcon->send("mp_halftime_pausetimer 1");
            }

            $this->say("\005" . $this->teamAName . " \004" . $this->currentMap->getScore1() . " \001- \004" . $this->currentMap->getScore2() . " \005" . $this->teamBName);

            $this->addLog($this->teamAName . " (" . $this->currentMap->getScore1() . ") - (" . $this->currentMap->getScore2() . ") " . $this->teamBName);
            $this->addMatchLog("Un round a été marqué - " . $this->teamAName . " (" . $this->currentMap->getScore1() . ") - (" . $this->currentMap->getScore2() . ") " . $this->teamBName);

            @mysql_query("UPDATE `matchs` SET score_a = '" . $this->score["team_a"] . "', score_b ='" . $this->score["team_b"] . "' WHERE id='" . $this->match_id . "'") or $this->addLog("Can't match " . $this->match_id . " scores", Logger::ERROR);

            // ROUND SUMMARY
            $nb = 0;
            $playerBest = null;
            foreach ($this->players as $player) {
                if ($player->killRound > $nb) {
                    $playerBest = $player;
                    $nb = $player->killRound;
                } elseif ($player->killRound == $nb) {
                    $tmp = ($player->currentSide == "ct") ? "CT" : "TERRORIST";
                    if ($tmp == $teamWin) {
                        $playerBest = $player;
                    }
                }
            }

            if ($playerBest != null) {
                $playerId = $playerBest->getId();
                $playerFirstKill = (int) $playerBest->gotFirstKill;
            } else {
                $playerId = "NULL";
                $playerFirstKill = "NULL";
            }

            $data = $this->rcon->send("mp_backup_round_file_last");
            if (preg_match('!"mp_backup_round_file_last" = "(?<backup>[a-zA-Z0-9\-_\.]+)"!', $data, $match)) {
                $backupFile = "'" . $match["backup"] . "'";
            } else {
                $backupFile = 'NULL';
            }

            if ($bestActionType == "") {
                if ($playerBest != null) {
                    $bestActionType = $nb . "kill";
                    $bestActionParam = array("player" => $playerBest->getId(), "playerName" => $playerBest->get("name"));
                } else {
                    $bestActionType = null;
                    $bestActionParam = null;
                }
            }

            mysql_query("INSERT INTO round_summary
                            (`match_id`,`map_id`,`score_a`,`score_b`,`bomb_planted`,`bomb_defused`,`bomb_exploded`,`ct_win`, `t_win`,`round_id`,`win_type`,`team_win`,`best_killer`,`best_killer_fk`,`best_killer_nb`,`best_action_type`,`best_action_param`, `backup_file_name`,`created_at`,`updated_at`)
                            VALUES
                            ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', '" . $this->score["team_a"] . "', '" . $this->score["team_b"] . "',
                                '" . ($this->gameBombPlanter != null) . "',
                                    '" . ($message->type == "bombdefused") . "',
                                        '" . ($message->type == "bombeexploded") . "',
                                            '" . ($message->getTeamWin() == "CT") . "',
                                                '" . ($message->getTeamWin() != "CT") . "',
                                                    '" . ($this->getNbRound() - 1) . "',
                                                        '" . $message->type . "','" . $teamWin . "',
                                                            $playerId, " . $playerFirstKill . ", $nb, " . (($bestActionType != null) ? "'$bestActionType'" : "NULL") . ", " . (($bestActionParam != null) ? "'" . addslashes(serialize($bestActionParam)) . "'" : "NULL") . ",
                                                                " . $backupFile . ",
                                                                NOW(),
                                                                    NOW()
                                                                    )") or $this->addLog("Can't insert round summary match " . $this->match_id . " - " . mysql_error(), Logger::ERROR);
            // END ROUND SUMMARY
            // Prevent the OverTime bug
            if ($this->config_ot) {
                if ($this->score['team_a'] + $this->score['team_b'] == ($this->maxRound * 2) - 1) {
                    $this->rcon->send("mp_overtime_enable 1");
                    $this->rcon->send("mp_overtime_maxrounds " . ($this->ot_maxround * 2));
                    $this->rcon->send("mp_overtime_startmoney " . $this->ot_startmoney);
                    $this->rcon->send("mp_overtime_halftime_pausetimer 1");
                }
            }

            foreach ($this->players as &$player) {
                $player->snapshot($this->getNbRound() - 1);
            }

            $this->resetSpecialSituation();
            if ($this->getStatus() == self::STATUS_FIRST_SIDE) {
                if ($this->score["team_a"] + $this->score["team_b"] == $this->maxRound) {
                    $this->swapSides();
                    $this->setStatus(self::STATUS_WU_2_SIDE, true);
                    $this->currentMap->setStatus(Map::STATUS_WU_2_SIDE, true);
                    $this->saveScore();

                    $this->rcon->send("mp_halftime_pausetimer 1");
                }
            } elseif ($this->getStatus() == self::STATUS_SECOND_SIDE) {
                if (($this->score["team_a"] + $this->score["team_b"] == $this->maxRound * 2) || ($this->score["team_a"] > $this->maxRound && !$this->config_full_score) || ($this->score["team_b"] > $this->maxRound && !$this->config_full_score)) {

                    if (($this->score["team_a"] == $this->score["team_b"]) && ($this->config_ot)) {
                        $this->setStatus(self::STATUS_WU_OT_1_SIDE, true);
                        $this->currentMap->setStatus(Map::STATUS_WU_OT_1_SIDE, true);
                        $this->maxRound = $this->ot_maxround;
                        $this->currentMap->addOvertime();
                        $this->nbOT++;
                        $this->addLog("Going to overtime");
                        $this->say("Going to overtime");
                        $this->currentMap->setNbMaxRound($this->ot_maxround);
                        //$this->rcon->send("mp_do_warmup_period 1; mp_warmuptime 30; mp_warmup_pausetimer 1");
                        //$this->rcon->send("mp_restartgame 1");
                        //$this->sendTeamNames();
                    } else {
                        $this->currentMap->setStatus(Map::STATUS_MAP_ENDED, true);

                        $this->lookEndingMatch();
                    }

                    $this->saveScore();
                }
            } elseif ($this->getStatus() == self::STATUS_OT_FIRST_SIDE) {
                $scoreToReach = $this->oldMaxround * 2 + $this->ot_maxround + ($this->ot_maxround * 2 * ($this->nbOT - 1));

                if ($this->score["team_a"] + $this->score["team_b"] == $scoreToReach) {
                    $this->setStatus(self::STATUS_WU_OT_2_SIDE, true);
                    $this->currentMap->setStatus(Map::STATUS_WU_OT_2_SIDE, true);
                    $this->saveScore();
                    $this->swapSides();
                    //$this->sendTeamNames();
                    // Not needed anymore with last updates
                    // $this->rcon->send("mp_restartgame 1");

                    $this->rcon->send("mp_halftime_pausetimer 1");
                }
            } elseif ($this->getStatus() == self::STATUS_OT_SECOND_SIDE) {
                $scoreToReach = $this->oldMaxround * 2 + $this->ot_maxround * 2 + ($this->ot_maxround * 2 * ($this->nbOT - 1));
                $scoreToReach2 = $this->oldMaxround + $this->ot_maxround + ($this->ot_maxround * ($this->nbOT - 1));

                if (($this->score["team_a"] + $this->score["team_b"] == $scoreToReach) || ($this->score["team_a"] > $scoreToReach2) || ($this->score["team_b"] > $scoreToReach2)) {

                    if ($this->score["team_a"] == $this->score["team_b"]) {
                        $this->setStatus(self::STATUS_WU_OT_1_SIDE, true);
                        $this->currentMap->setStatus(Map::STATUS_WU_OT_1_SIDE, true);
                        $this->maxRound = $this->ot_maxround;
                        $this->currentMap->addOvertime();
                        $this->currentMap->setNbMaxRound($this->ot_maxround);
                        $this->nbOT++;
                        $this->addLog("Going to overtime");
                        //$this->rcon->send("mp_do_warmup_period 1; mp_warmuptime 30; mp_warmup_pausetimer 1");
                        //$this->rcon->send("mp_restartgame 1");
                    } else {
                        $this->currentMap->setStatus(Map::STATUS_MAP_ENDED, true);

                        $this->lookEndingMatch();
                    }
                    $this->saveScore();
                }
            }

            // Dispatching to Websocket

            $this->websocket['match']->sendData(json_encode(array('message' => 'status', 'content' => $this->getStatusText(false), 'id' => $this->match_id)));
            $this->websocket['match']->sendData(json_encode(array('message' => 'button', 'content' => $this->getStatus(), 'id' => $this->match_id)));
            $this->websocket['match']->sendData(json_encode(array('message' => 'score', 'scoreA' => $this->score['team_a'], 'scoreB' => $this->score['team_b'], 'id' => $this->match_id)));

            // Dispatching events
            $event = new \eBot\Events\Event\RoundScored();
            $event->setMatch($this);
            $event->setTeamA($this->teamAName);
            $event->setTeamB($this->teamAName);
            $event->setScoreA($this->score["team_a"]);
            $event->setScoreB($this->score["team_b"]);
            $event->setStatus($this->getStatus());
            \eBot\Events\EventDispatcher::getInstance()->dispatchEvent($event);
        }
        $this->roundEndEvent = true;
    }

    private $needDelTask = false;
    private $currentRecordName = false;

    private function lookEndingMatch() {

        /**
         * TV PUSH
         */
        TaskManager::getInstance()->addTask(new Task($this, self::STOP_RECORD, microtime(true) + 2));
        $record_name = $this->currentMap->getTvRecordFile();
        if ($record_name != "") {
            $this->currentRecordName = $record_name;
        }

        if ($this->matchData['map_selection_mode'] == "normal") {
            $allFinish = true;
        } else {
            $team1win = 0;
            $team2win = 0;

            $countPlayed = 0;
            foreach ($this->maps as $map) {
                if ($map->getStatus() == Map::STATUS_MAP_ENDED) {
                    $countPlayed++;
                    if ($map->getScore1() > $map->getScore2())
                        $team1win++;
                    else
                        $team2win++;
                }
            }

            $this->addLog("Score end: $team1win - $team2win");
            $this->addLog("Number of map to win: " . ceil(count($this->maps) / 2));

            if ($countPlayed == count($this->maps)) {
                $allFinish = true;
            } elseif ($this->matchData['map_selection_mode'] == "bo2") {
                if ($team1win > $team2win)
                    $allFinish = true;
                else
                    $allFinish = false;
            } else {
                if (($team1win > $team2win && $team1win >= ceil(count($this->maps) / 2)) || ($team1win < $team2win && $team2win >= ceil(count($this->maps) / 2)))
                    $allFinish = true;
                else
                    $allFinish = false;
            }
        }

        if (count($this->maps) == 1 || $allFinish) {
            $this->needDelTask = true;
            $this->setStatus(self::STATUS_END_MATCH, true);

            $this->addLog("Match is closed");
            if ($this->score["team_a"] > $this->score["team_b"]) {
                $this->say($this->teamAName . " win ! Final score: " . $this->score["team_a"] . "/" . $this->score["team_b"]);
                $this->addMatchLog($this->teamAName . " win ! Final score: " . $this->score["team_a"] . "/" . $this->score["team_b"]);
            } elseif ($this->score["team_a"] < $this->score["team_b"]) {
                $this->say($this->teamBName . " win ! Final score: " . $this->score["team_b"] . "/" . $this->score["team_a"]);
                $this->addMatchLog($this->teamBName . " win ! Final score: " . $this->score["team_b"] . "/" . $this->score["team_a"]);
            } else {
                $this->say("Final score: " . $this->score["team_a"] . " - " . $this->score["team_b"] . " - Draw !");
                $this->addMatchLog("Final score: " . $this->score["team_a"] . " - " . $this->score["team_b"] . " - Draw !");
            }
            $this->rcon->send("mp_teamname_1 \"\"; mp_teamflag_1 \"\";");
            $this->rcon->send("mp_teamname_2 \"\"; mp_teamflag_2 \"\";");

            $this->websocket['match']->sendData(json_encode(array('message' => 'status', 'content' => $this->getStatusText(false), 'id' => $this->match_id)));

            $event = new \eBot\Events\Event\MatchEnd();
            $event->setMatch($this);
            $event->setScore1($this->score["team_a"]);
            $event->setScore2($this->score["team_a"]);
            \eBot\Events\EventDispatcher::getInstance()->dispatchEvent($event);
        } else {
            $backupMap = $this->currentMap;
            $this->currentMap = null;

            // bo2, bo3_modea, bo3_modeb, normal
            if ($this->matchData['map_selection_mode'] == "bo2") {
                if (count($this->maps) == 2) {
                    $this->addLog("Best Of Two matches - Engaging next map");
                    foreach ($this->maps as $map) {
                        if ($map->getStatus() == Map::STATUS_NOT_STARTED) {
                            $this->addLog("Engaging " . $map->getMapName());
                            $this->currentMap = $map;
                            break;
                        }
                    }
                }
            } elseif ($this->matchData['map_selection_mode'] == "bo3_modea") {
                if ($backupMap->getMapsFor() == "default") {
                    if ($this->score["team_a"] > $this->score["team_b"]) {
                        $mapFor = "team2";
                    } else {
                        $mapFor = "team1";
                    }

                    foreach ($this->maps as $map) {
                        if ($map->getMapsFor() == $mapFor) {
                            if ($map->getStatus() == Map::STATUS_NOT_STARTED) {
                                $this->currentMap = $map;
                                break;
                            }
                        }
                    }
                } else {
                    foreach ($this->maps as $map) {
                        if ($map->getStatus() == Map::STATUS_NOT_STARTED) {
                            $this->currentMap = $map;
                            break;
                        }
                    }
                }
            } elseif ($this->matchData['map_selection_mode'] == "bo3_modeb") {
                if ($backupMap->getMapsFor() == "team1") {
                    $mapFor = "team2";
                } elseif ($backupMap->getMapsFor() == "team2") {
                    $mapFor = "default";
                }

                foreach ($this->maps as $map) {
                    if ($map->getMapsFor() == $mapFor) {
                        if ($map->getStatus() == Map::STATUS_NOT_STARTED) {
                            $this->currentMap = $map;
                            break;
                        }
                    }
                }
            } else {
                foreach ($this->maps as $map) {
                    if ($map->getStatus() == Map::STATUS_NOT_STARTED) {
                        $this->currentMap = $map;
                        break;
                    }
                }
            }

            if ($this->currentMap != null) {
                $this->currentMap->setStatus(Map::STATUS_STARTING, true);
                $this->setStatus(self::STATUS_STARTING, true);
                \mysql_query("UPDATE `matchs` SET `current_map` = '" . $this->currentMap->getMapId() . "' WHERE `id` = '" . $this->match_id . "'");

                Logger::debug("Setting need knife round on map");
                $this->currentMap->setNeedKnifeRound(true);
                $this->nbOT = 0;
                $this->score["team_a"] = 0;
                $this->score["team_b"] = 0;
                $currentSide = $this->currentMap->getCurrentSide();
                if ($currentSide == 'ct') {
                    $this->side['team_a'] = "ct";
                    $this->side['team_b'] = "t";
                } else {
                    $this->side['team_a'] = "t";
                    $this->side['team_b'] = "ct";
                }

                $this->addLog("Engaging next map " . $this->currentMap->getMapName());
                $this->addMatchLog("Engaging next map " . $this->currentMap->getMapName());
                $time = microtime(true) + \eBot\Config\Config::getInstance()->getDelay_busy_server();
                $this->timeEngageMap = $time;
                $this->addLog("Launching map in " . \eBot\Config\Config::getInstance()->getDelay_busy_server() . " seconds");
                TaskManager::getInstance()->addTask(new Task($this, self::TASK_ENGAGE_MAP, $time));
            } else {
                $this->setStatus(self::STATUS_END_MATCH, true);
                Logger::error("Not map found");
                $this->addLog("Match is closed");
            }
        }
    }

    private function processChangeName(\eBot\Message\Type\ChangeName $message) {
        $this->processPlayer($message->getUserId(), $message->newName, $message->getUserTeam(), $message->getUserSteamid());
    }

    private function processKillAssist(\eBot\Message\Type\KillAssist $message) {
        $killer = $this->processPlayer($message->getUserId(), $message->getUserName(), $message->getUserTeam(), $message->getUserSteamid());
        //$killed = $this->processPlayer($message->getKilledUserId(), $message->getKilledUserName(), $message->getKilledUserTeam(), $message->getKilledUserSteamid());

        if (!$this->waitForRestart && $this->enable && in_array($this->getStatus(), array(self::STATUS_FIRST_SIDE, self::STATUS_SECOND_SIDE, self::STATUS_OT_FIRST_SIDE, self::STATUS_OT_SECOND_SIDE))) {
            $killer->inc("assist");
            $killer->save();
        }

//        $this->addLog($message->userName . " assisted the kill of " . $message->killedUserName);
//        $this->addMatchLog($this->getColoredUserNameHTML($message->userName, $message->userTeam) . " assisted the kill of " . $this->getColoredUserNameHTML($message->killedUserName, $message->killedUserTeam));
    }

    private function processKill(\eBot\Message\Type\Kill $message) {
        $this->processPlayer($message->getUserId(), $message->getUserName(), $message->getUserTeam(), $message->getUserSteamid());
        $this->processPlayer($message->getKilledUserId(), $message->getKilledUserName(), $message->getKilledUserTeam(), $message->getKilledUserSteamid());

        if (!$this->waitForRestart && $this->enable && in_array($this->getStatus(), array(self::STATUS_FIRST_SIDE, self::STATUS_SECOND_SIDE, self::STATUS_OT_FIRST_SIDE, self::STATUS_OT_SECOND_SIDE))) {
            $killer = $this->findPlayer($message->userId, $message->userSteamid);
            $killed = $this->findPlayer($message->killedUserId, $message->killedUserSteamid);

            if ($this->firstFrag) {
                if ($killer != null) {
                    $killer->inc("firstKill");
                    $killer->gotFirstKill = true;
                }
                $this->firstFrag = false;
            }

            if ($killed != null) {
                $killed->set("alive", false);
            }

            if ($message->userTeam == $message->killedUserTeam) {
                if ($killer) {
                    $killer->inc("tk");
                    $killer->deinc("point");
                }

                if ($killed) {
                    $killed->inc("death");
                }
            } else {
                if ($killer) {
                    $killer->inc("killRound");
                    $killer->inc("kill");
                    $killer->inc("point");
                    if ($message->headshot) {
                        $killer->inc("hs");
                    }
                }

                if ($killed) {
                    $killed->inc("death");
                }
            }

            $killer_id = null;
            $killed_id = null;
            $killer_name = $message->userName;
            $killed_name = $message->killedUserName;
            if ($killer != null) {
                $killer_id = $killer->getId();
            }

            if ($killed != null) {
                $killed_id = $killed->getId();
            }

            //getNbRound
            \mysql_query("INSERT INTO player_kill
                (`match_id`,`map_id`, `killer_team`,`killer_name`,`killer_id`,`killed_team`,`killed_name`,`killed_id`,`weapon`,`headshot`,`round_id`,`created_at`,`updated_at`)
                VALUES
                ('" . $this->match_id . "','" . $this->currentMap->getMapId() . "', '" . $message->userTeam . "', '" . addslashes($killer_name) . "', " . (($killer_id != null) ? $killer_id : "NULL") . ", '" . $message->killedUserTeam . "' ,'" . addslashes($killed_name) . "', " . (($killed_id != null) ? $killed_id : "NULL") . ", '" . $message->weapon . "', '" . $message->headshot . "','" . (($this->roundEndEvent) ? $this->getNbRound() - 1 : $this->getNbRound() ) . "', NOW(), NOW())
                    ") or $this->addLog("Can't insert player_kill " . mysql_error(), Logger::ERROR);

            // Round Event
            $id = \mysql_insert_id();
            if (is_numeric($id)) {
                // Inserting round event
                \mysql_query("
                    INSERT INTO `round`
                    (`match_id`,`map_id`,`event_name`,`event_time`,`kill_id`,`round_id`,`created_at`,`updated_at`)
                        VALUES
                    ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', 'kill', '" . $this->getRoundTime() . "', $id, '" . (($this->roundEndEvent) ? $this->getNbRound() - 1 : $this->getNbRound() ) . "', NOW(), NOW())
                        ");
            }

            // HeatMap !
            \mysql_query("INSERT INTO `players_heatmap`
                            (`match_id`,`map_id`,`event_name`,`event_x`,`event_y`,`event_z`,`player_name`,`player_id`,`player_team`,`attacker_x`,`attacker_y`,`attacker_z`,`attacker_name`,`attacker_id`,`attacker_team`,`round_id`,`round_time`,`created_at`,`updated_at`) VALUES
                            (" . $this->match_id . ", " . $this->currentMap->getMapId() . ", 'kill', '" . $message->killedPosX . "', '" . $message->killedPosY . "', '" . $message->killedPosZ . "','" . addslashes($message->killedUserName) . "', '" . $killed_id . "', '" . $message->killedUserTeam . "', '" . $message->killerPosX . "', '" . $message->killerPosY . "', '" . $message->killerPosZ . "', '" . $message->userName . "', '" . $killer_id . "', '" . $message->userTeam . "', '" . (($this->roundEndEvent) ? $this->getNbRound() - 1 : $this->getNbRound() ) . "', '" . $this->getRoundTime() . "', NOW(), NOW())
                            ");

            if ($killer) {
                $killer->saveScore();
            }

            if ($killed) {
                $killed->saveScore();
            }
        }

        if ($message->killedUserTeam == "CT") {
            $this->nbLast["nb_ct"]--;
        } elseif ($message->killedUserTeam == "TERRORIST") {
            $this->nbLast["nb_t"]--;
        }

        $this->addLog($message->getUserName() . " killed " . $message->getKilledUserName() . " with " . $message->weapon . (($message->headshot) ? " (headshot)" : "") . " (CT: " . $this->nbLast["nb_ct"] . " - T: " . $this->nbLast['nb_t'] . ")");
        $this->addMatchLog($this->getColoredUserNameHTML($message->getUserName(), $message->userTeam) . " killed " . $this->getColoredUserNameHTML($message->getKilledUserName(), $message->killedUserTeam) . " with " . $message->weapon . (($message->headshot) ? " (headshot)" : "") . " (CT: " . $this->nbLast["nb_ct"] . " - T: " . $this->nbLast['nb_t'] . ")");

        $this->watchForSpecialSituation();

        if ($this->isMatchRound()) {
            $sendToWebsocket = json_encode(array(
                'type' => 'kill',
                'id' => $this->match_id,
                'killer' => $message->getUserName(),
                'killerPosX' => $message->killerPosX,
                'killerPosY' => $message->killerPosY,
                'weapon' => $message->getWeapon(),
                'killed' => $message->getKilledUserName(),
                'killedPosX' => $message->killedPosX,
                'killedPosY' => $message->killedPosY,
                'headshot' => $message->getHeadshot()
            ));
            $this->websocket['livemap']->sendData($sendToWebsocket);
        }

        $event = new \eBot\Events\Event\Kill();
        $event->setMatch($this);
        $event->setUserId($message->getUserId());
        $event->setUserName($message->getUserName());
        $event->setUserTeam($message->getUserTeam());
        $event->setUserSteamid($message->getUserSteamid());
        $event->setKilledUserId($message->getKilledUserId());
        $event->setKilledUserName($message->getKilledUserName());
        $event->setKilledUserTeam($message->getKilledUserTeam());
        $event->setKilledUserSteamid($message->getKilledUserSteamid());
        $event->setHeadshot($message->getHeadshot());
        $event->setWeapon($message->getWeapon());
        \eBot\Events\EventDispatcher::getInstance()->dispatchEvent($event);
    }

    private function processConnected(\eBot\Message\Type\Connected $message) {
        $this->addLog($message->userName . " connected (" . $message->address . ")");
        $this->addMatchLog(htmlentities($message->userName) . " connected");
        $this->userToEnter[$message->userId] = $message->address;
    }

    private function processEnteredTheGame(\eBot\Message\Type\EnteredTheGame $message) {
        $this->addLog($message->userName . " entered the game");
    }

    private function processGotTheBomb(\eBot\Message\Type\GotTheBomb $message) {
        if ($this->roundEndEvent) {
            foreach ($this->players as $k => &$v) {
                if ($this->getNbRound() > 1) {
                    $v->snapshot($this->getNbRound() - 1);
                }
            }
        }
    }

    private function processJoinTeam(\eBot\Message\Type\JoinTeam $message) {
        $this->processPlayer($message->getUserId(), $message->getUserName(), $message->joinTeam, $message->getUserSteamid());
        $this->addLog($message->userName . " join team " . $message->joinTeam);
        $this->addMatchLog(htmlentities($message->userName) . " join team " . $message->joinTeam);
    }

    private function processDisconnected(\eBot\Message\Type\Disconnected $message) {
        $this->addLog($message->userName . " disconnected");
        $this->addMatchLog(htmlentities($message->userName) . " disconnected");
        $player = $this->findPlayer($message->userId, $message->userSteamid);
        if ($player != null) {
            $player->setOnline(false);
        }
    }

    private function processRemindRoundScored(\eBot\Message\Type\RemindRoundScored $message) {
        if (!$this->roundRestartEvent) {
            if (!$this->waitForRestart && $this->enable && in_array($this->getStatus(), array(self::STATUS_FIRST_SIDE, self::STATUS_SECOND_SIDE, self::STATUS_OT_FIRST_SIDE, self::STATUS_OT_SECOND_SIDE))) {
                if (!$this->roundEndEvent) {
                    $roundScored = new \eBot\Message\Type\RoundScored();
                    $roundScored->team = $message->team;
                    $roundScored->type = $message->type;
                    $roundScored->teamWin = $message->teamWin;
                    $this->addLog("Missed Round_Scored event !");
                    $this->processRoundScored($roundScored);
                }
            }
        } else {
            $this->addLog("Round restarted, don't forward remind round scored !");
        }
    }

    private function processRoundSpawn(\eBot\Message\Type\RoundSpawn $message) {
        $this->roundRestartEvent = false;
        if ($this->roundEndEvent) {
            foreach ($this->players as $k => &$v) {
                if ($this->getNbRound() > 1) {
                    $v->snapshot($this->getNbRound() - 1);
                }
            }
        }
    }

    private function processRoundRestart(\eBot\Message\Type\RoundRestart $message) {
        if ($this->waitForRestart && $this->getStatus() == self::STATUS_FIRST_SIDE && ( \eBot\Config\Config::getInstance()->getConfigKnifeMethod() == "matchstart" || $this->forceRoundStartRecord)) {
            $this->waitRoundStartRecord = true;
            $this->forceRoundStartRecord = false;
        } elseif ($this->waitForRestart && $this->getStatus() == self::STATUS_KNIFE && \eBot\Config\Config::getInstance()->getConfigKnifeMethod() == "knifestart") {
            $this->waitRoundStartRecord = true;
        }
        $this->roundRestartEvent = true;
    }

    private function processRoundStart(\eBot\Message\Type\RoundStart $message) {
        $this->roundRestartEvent = false;
        if (!$this->roundEndEvent && $this->isMatchRound()) {
            $this->addLog("Missed Round_Score Event !!!", Logger::ERROR);
        }

        if ($this->waitForRestart) {
            $this->waitForRestart = false;
            Logger::log("Starting counting score");
        }

        if ($this->waitRoundStartRecord) {
            $record_name = $this->match_id . "_" . \eTools\Utils\Slugify::cleanTeamName($this->teamAName) . "-" . \eTools\Utils\Slugify::cleanTeamName($this->teamBName) . "_" . $this->currentMap->getMapName();
            $text = $this->rcon->send("tv_autorecord");
            if (preg_match('!"tv_autorecord" = "(?<value>.*)"!', $text, $match)) {
                if ($match["value"] == 1) {
                    Logger::log("Stopping running records (tv_autorecord)");
                    $this->rcon->send("tv_autorecord 0; tv_stoprecord");
                }
            }

            Logger::log("Launching record $record_name");
            $this->rcon->send("tv_record $record_name");
            $this->currentMap->setTvRecordFile($record_name);
            $this->waitRoundStartRecord = false;

            \mysql_query("UPDATE `maps` SET tv_record_file='" . $record_name . "' WHERE id='" . $this->currentMap->getMapId() . "'") or $this->addLog("Error while updating tv record name - " . mysql_error(), Logger::ERROR);
        }

        $this->nbLast['nb_ct'] = $this->nbLast['nb_max_ct'];
        $this->nbLast['nb_t'] = $this->nbLast['nb_max_t'];
        $this->gameBombPlanter = null;
        $this->gameBombeDefuser = null;
        $this->firstFrag = true;
        $this->timeRound = time();
        $this->saveScore();
        $this->resetSpecialSituation();

        foreach ($this->players as $k => &$v) {
            $v->roundStart();
            if ($this->getNbRound() > 1) {
                $v->snapshot($this->getNbRound() - 1);
            }
        }

        $this->countPlayers();

        $this->watchForSpecialSituation();

        // Feedback from players
        $this->pause["ct"] = false;
        $this->pause["t"] = false;
        $this->unpause["ct"] = false;
        $this->unpause["t"] = false;
        $this->stop["ct"] = false;
        $this->stop["t"] = false;

        // Preventing old data
        mysql_query("DELETE FROM player_kill WHERE round_id = " . $this->getNbRound() . " AND map_id='" . $this->currentMap->getMapId() . "'");
        mysql_query("DELETE FROM round WHERE round_id = " . $this->getNbRound() . " AND map_id='" . $this->currentMap->getMapId() . "'");
        mysql_query("DELETE FROM round_summary WHERE round_id = " . $this->getNbRound() . " AND map_id='" . $this->currentMap->getMapId() . "'");

        \mysql_query("
                    INSERT INTO `round`
                    (`match_id`,`map_id`,`event_name`,`event_time`,`round_id`,`created_at`,`updated_at`)
                        VALUES
                    ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', 'round_start', '" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                        ");
        if ($this->isMatchRound())
            $this->websocket['livemap']->sendData(json_encode(array(
                'type' => 'newRound',
                'id' => $this->match_id,
                'message' => "newRound",
                'round' => $this->getNbRound(),
                'status' => $this->getStatusText(false)
            )));

        $this->roundEndEvent = false;
    }

    private function processPlayer($user_id, $user_name, $team, $steamid) {
        Logger::debug("Processing player $user_id $user_name $team $steamid");
        $player = $this->findPlayer($user_id, $steamid);
        if ($player == null) {
            $player = new Player($this->match_id, $this->currentMap->getMapId(), $steamid);
            $this->players[$user_id] = $player;
            $this->countPlayers();
        }

        if (@$this->userToEnter[$user_id]) {
            $player->setIp($this->userToEnter[$user_id]);
        }

        $player->setUserName($user_name);
        $player->setOnline(true);

        $teamToSet = null;
        if (strtolower($team) == "ct") {
            if (($this->side["team_a"] == "ct")) {
                $teamToSet = "a";
            } elseif (($this->side["team_b"] == "ct")) {
                $teamToSet = "b";
            }
        } elseif ((strtolower($team) == "terrorist") || (strtolower($team) == "t")) {
            if (($this->side["team_a"] == "t")) {
                $teamToSet = "a";
            } elseif (($this->side["team_b"] == "t")) {
                $teamToSet = "b";
            }
        }

        $player->setCurrentTeam($team, $teamToSet);
        $player->save();

        return $player;
    }

    private function resetSpecialSituation() {
        $this->specialSituation['id'] = 0;
        $this->specialSituation['situation'] = 0;
        $this->specialSituation['active'] = false;
        $this->specialSituation['side'] = "";
        $this->specialSituation['side2'] = "";
        $this->specialSituation['status'] = false;
    }

    private $specialSituation = array("id" => 0, "situation" => 0, "active" => false, "side" => "");

    private function watchForSpecialSituation() {
        if (!$this->specialSituation['active']) {
            if (($this->nbLast['nb_ct'] == 1) && ($this->nbLast['nb_t'] == 1)) {
                $this->specialSituation['id'] = 0;
                $this->specialSituation['situation'] = 1;
                $this->specialSituation['active'] = true;
                $this->specialSituation['side'] = "both";
                $this->addLog("1v1 situation !");
                $this->addMatchLog("<b>Situation 1v1</b>", true);
                \mysql_query("
                            INSERT INTO `round`
                            (`match_id`,`map_id`,`event_name`,`event_time`,`round_id`,`created_at`,`updated_at`)
                                VALUES
                            ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', '1vx', '" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                                ");
            } else {
                if ($this->nbLast['nb_ct'] == 1) {
                    $nbAlive = 0;
                    $id = false;
                    foreach ($this->players as $k => &$v) {
                        if (!$v->get("online"))
                            continue;

                        if (($v->get("currentSide") == "t") && $v->get("alive")) {
                            $nbAlive++;
                        } elseif (($v->get("currentSide") == "ct") && $v->get("alive")) {
                            $id = $k;
                        }
                    }

                    if (($nbAlive > 0) && $id) {
                        $this->specialSituation['id'] = $id;
                        $this->specialSituation['situation'] = $nbAlive;
                        $this->specialSituation['active'] = true;
                        $this->specialSituation['side'] = "ct";

                        $this->addLog("Situation spécial ! 1v" . $nbAlive . " (" . $this->players[$id]->get("name") . ")");
                        $this->addMatchLog("<b>Situation spécial ! 1v" . $nbAlive . " (" . htmlentities($this->players[$id]->get("name")) . ")</b>");

                        \mysql_query("
                            INSERT INTO `round`
                            (`match_id`,`map_id`,`event_name`, `event_text`,`event_time`,`round_id`,`created_at`,`updated_at`)
                                VALUES
                            ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', '1vx', '" . addslashes(serialize(array("situation" => $nbAlive, "player_id" => $this->players[$id]->get("name"), "player_id" => $this->players[$id]->getId()))) . "', '" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                                ");
                    }
                } elseif ($this->nbLast['nb_t'] == 1) {
                    $nbAlive = 0;
                    $id = false;
                    foreach ($this->players as $k => &$v) {
                        if (!$v->get("online"))
                            continue;

                        if (($v->get("currentSide") == "ct") && $v->get("alive")) {
                            $nbAlive++;
                        } elseif (($v->get("currentSide") == "t") && $v->get("alive")) {
                            $id = $k;
                        }
                    }

                    if (($nbAlive > 0) && $id) {
                        $this->specialSituation['id'] = $id;
                        $this->specialSituation['situation'] = $nbAlive;
                        $this->specialSituation['active'] = true;
                        $this->specialSituation['side'] = "t";

                        $this->addLog("Situation spécial ! 1v" . $nbAlive . " (" . $this->players[$id]->get("name") . ")");
                        $this->addMatchLog("<b>Situation spécial ! 1v" . $nbAlive . " (" . htmlentities($this->players[$id]->get("name")) . ")</b>");
                        \mysql_query("
                            INSERT INTO `round`
                            (`match_id`,`map_id`,`event_name`, `event_text`,`event_time`,`round_id`,`created_at`,`updated_at`)
                                VALUES
                            ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', '1vx', '" . addslashes(serialize(array("situation" => $nbAlive, "player_id" => $this->players[$id]->get("name"), "player_id" => $this->players[$id]->getId()))) . "', '" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                                ");
                    }
                }
            }
        } else {
            if (($this->specialSituation['side2'] != "both") && ($this->specialSituation['side'] != "both")) {
                if (($this->nbLast['nb_ct'] == 1) && ($this->nbLast['nb_t'] == 1)) {
                    if ($this->players[$this->specialSituation['id']]) {
                        $this->addLog("Situation spécial 1v1 ! - Le joueur " . $this->players[$this->specialSituation['id']]->get("name") . " est en 1v" . $this->specialSituation['situation']);
                        $this->addMatchLog("<b>Situation spécial 1v1 ! - Le joueur " . htmlentities($this->players[$this->specialSituation['id']]->get("name")) . " est en 1v" . $this->specialSituation['situation'] . "</b>", false);
                        $this->specialSituation['side2'] = "both";

                        \mysql_query("
                            INSERT INTO `round`
                            (`match_id`,`map_id`,`event_name`,`event_time`,`round_id`,`created_at`,`updated_at`)
                                VALUES
                            ('" . $this->match_id . "', '" . $this->currentMap->getMapId() . "', '1v1', '" . $this->getRoundTime() . "', '" . $this->getNbRound() . "', NOW(), NOW())
                                ");
                    }
                }
            }
        }
    }

    /**
     *
     * @param type $user_id
     * @param type $steamid
     * @return \eBot\Match\Player
     */
    private function findPlayer($user_id = null, $steamid = null) {
        foreach ($this->players as $player) {
            if (($player->getSteamid() == $steamid)) {
                return $player;
            }
        }

        return null;
    }

    private function saveScore() {
        foreach ($this->players as $player) {
            
        }
    }

    private function countPlayers() {
        $this->nbLast['nb_max_ct'] = 0;
        $this->nbLast['nb_max_t'] = 0;
        $this->nbLast["nb_ct"] = 0;
        $this->nbLast["nb_t"] = 0;

        foreach ($this->players as $k => &$v) {
            if (!$v->get("online"))
                continue;
            if ($v->get("currentSide") == "ct") {
                $this->nbLast['nb_max_ct']++;
                if ($v->get("alive"))
                    $this->nbLast["nb_ct"]++;
            } elseif ($v->get("currentSide") == "t") {
                $this->nbLast['nb_max_t']++;
                if ($v->get("alive"))
                    $this->nbLast["nb_t"]++;
            }
        }

        $this->addLog("Counting players :: CT:" . $this->nbLast['nb_max_ct'] . " :: T:" . $this->nbLast['nb_max_t']);
    }

    private function pauseMatch() {
        if (\eBot\Config\Config::getInstance()->getPauseMethod() == "instantConfirm") {
            if ($this->pause["ct"] && $this->pause["t"] && $this->isMatchRound() && !$this->isPaused) {
                $this->isPaused = true;
                $this->say("Match is paused");
                $this->say("Write !unpause to remove the pause when ready");
                $this->addMatchLog("Pausing match");
                $this->rcon->send("pause");
                \mysql_query("UPDATE `matchs` SET `is_paused` = '1' WHERE `id` = '" . $this->match_id . "'");
                $this->websocket['match']->sendData(json_encode(array('message' => 'status', 'content' => 'is_paused', 'id' => $this->match_id)));

                $this->pause["ct"] = false;
                $this->pause["t"] = false;
                $this->unpause["ct"] = false;
                $this->unpause["t"] = false;
            }
        } elseif (\eBot\Config\Config::getInstance()->getPauseMethod() == "instantNoConfirm") {
            if (($this->pause["ct"] || $this->pause["t"]) && $this->isMatchRound() && !$this->isPaused) {
                $this->isPaused = true;
                $this->say("Match is paused");
                $this->say("Write !unpause to remove the pause when ready");
                $this->addMatchLog("Pausing match");
                $this->rcon->send("pause");
                \mysql_query("UPDATE `matchs` SET `is_paused` = '1' WHERE `id` = '" . $this->match_id . "'");
                $this->websocket['match']->sendData(json_encode(array('message' => 'status', 'content' => 'is_paused', 'id' => $this->match_id)));

                $this->pause["ct"] = false;
                $this->pause["t"] = false;
                $this->unpause["ct"] = false;
                $this->unpause["t"] = false;
            }
        } elseif (\eBot\Config\Config::getInstance()->getPauseMethod() == "nextRound") {
            if (($this->pause["ct"] || $this->pause["t"]) && $this->isMatchRound() && !$this->isPaused) {
                $this->isPaused = true;
                $this->say("Match will be paused at next round !");
                $this->say("Write !unpause to remove the pause when ready");
                $this->addMatchLog("Pausing match");
                $this->rcon->send("mp_pause_match");
                \mysql_query("UPDATE `matchs` SET `is_paused` = '1' WHERE `id` = '" . $this->match_id . "'");
                $this->websocket['match']->sendData(json_encode(array('message' => 'status', 'content' => 'is_paused', 'id' => $this->match_id)));

                $this->pause["ct"] = false;
                $this->pause["t"] = false;
                $this->unpause["ct"] = false;
                $this->unpause["t"] = false;
            }
        }
    }

    private function unpauseMatch() {
        if ($this->unpause["ct"] && $this->unpause["t"] && $this->isMatchRound() && $this->isPaused) {
            $this->isPaused = false;
            $this->say("Match is unpaused, live !");
            $this->addMatchLog("Unpausing match");
            if (\eBot\Config\Config::getInstance()->getPauseMethod() == "nextRound") {
                $this->rcon->send("mp_unpause_match");
            } else {
                $this->rcon->send("pause");
            }
            \mysql_query("UPDATE `matchs` SET `is_paused` = '0' WHERE `id` = '" . $this->match_id . "'");
            $this->websocket['match']->sendData(json_encode(array('message' => 'status', 'content' => 'is_unpaused', 'id' => $this->match_id)));

            $this->pause["ct"] = false;
            $this->pause["t"] = false;
            $this->unpause["ct"] = false;
            $this->unpause["t"] = false;
        }
    }

    private function setMatchMap($mapname) {
        if ($this->playMap["ct"] == $this->playMap["t"] AND $this->playMap["ct"] != "") {
            \mysql_query("UPDATE `maps` SET `map_name` = '" . $this->playMap["ct"] . "' WHERE `match_id` = '" . $this->match_id . "'");
            Logger::debug("Loading map");
            $query = \mysql_query("SELECT * FROM `maps` WHERE match_id = '" . $this->match_id . "'");
            if (!$query) {
                throw new MatchException();
            }
            while ($data = \mysql_fetch_assoc($query)) {
                $this->maps[$data["id"]] = new Map($data);
                $this->maps[$data["id"]]->setNbMaxRound($this->maxRound);
            }
            if ($this->maps[$this->matchData["current_map"]]) {
                $this->currentMap = $this->maps[$this->matchData["current_map"]];
            } else {
                $this->addLog("Can't find the map #" . $this->matchData["current_map"], Logger::ERROR);
                throw new MatchException();
            }
            $this->addLog("Maps selected: #" . $this->currentMap->getMapId() . " - " . $this->currentMap->getMapName() . " - " . $this->currentMap->getStatusText());
            Logger::debug("Engage new Map.");
            TaskManager::getInstance()->addTask(new Task($this, self::TASK_ENGAGE_MAP, microtime(true) + 1));

            if ($this->currentMap->getCurrentSide() == "ct") {
                $this->side['team_a'] = "ct";
                $this->side['team_b'] = "t";
            } else {
                $this->side['team_a'] = "t";
                $this->side['team_b'] = "ct";
            }

            $this->websocket['match']->sendData(json_encode(array('message' => 'teams', 'teamA' => $this->side['team_a'], 'teamB' => $this->side['team_b'], 'id' => $this->match_id)));

            $this->currentMap->calculScores();

            $this->score["team_a"] = $this->currentMap->getScore1();
            $this->score["team_b"] = $this->currentMap->getScore2();

            @mysql_query("UPDATE `matchs` SET score_a = '" . $this->score["team_a"] . "', score_b ='" . $this->score["team_b"] . "' WHERE id='" . $this->match_id . "'");

            // Setting nb OverTime
            $this->nbOT = $this->currentMap->getNbOt();
        }
    }

    private function continueMatch() {
        if ($this->continue["ct"] && $this->continue["t"]) {
            $this->continue["ct"] = false;
            $this->continue["t"] = false;

            $this->addMatchLog("Getting back to the match");
            $this->addLog("Getting back to the match");

            // Sending roundbackup format file
            $this->rcon->send("mp_backup_round_file \"ebot_" . $this->match_id . "\"");

            // Prevent the halftime pausetimer
            $this->rcon->send("mp_halftime_pausetimer 0");

            if (!$this->backupFile) {
                $this->addLog("Backup file not found, simulating one");
                $this->backupFile = "ebot_" . $this->match_id . "_round" . sprintf("%02s", $this->getNbRound()) . ".txt";
            }
            // Sending restore
            $this->rcon->send("mp_backup_restore_load_file " . $this->backupFile);

            // Prevent a bug for double stop
            $this->rcon->send("mp_backup_round_file_last " . $this->backupFile);

            foreach ($this->players as &$player) {
                $player->restoreSnapshot($this->getNbRound() - 1);
            }
			
            $this->say("Round restored, going live !");
            \mysql_query("UPDATE `matchs` SET ingame_enable = 1 WHERE id='" . $this->match_id . "'") or $this->addLog("Can't update ingame_enable", Logger::ERROR);
            TaskManager::getInstance()->addTask(new Task($this, self::SET_LIVE, microtime(true) + 2));
        }
    }

    private function stopMatch() {
        if ($this->stop["ct"] && $this->stop["t"]) {
            if (in_array($this->getStatus(), array(self::STATUS_FIRST_SIDE, self::STATUS_SECOND_SIDE, self::STATUS_OT_FIRST_SIDE, self::STATUS_OT_SECOND_SIDE))) {
                if ($this->getNbRound() == 1) {
                    $this->setStatus($this->getStatus() - 1, true);
                    $this->currentMap->setStatus($this->currentMap->getStatus() - 1, true);

                    $this->addLog("Stopping current side, new status: " . $this->getStatusText());

                    $this->recupStatus(true);
                    mysql_query("DELETE FROM player_kill WHERE round_id >= 1 AND map_id='" . $this->currentMap->getMapId() . "'");
                    mysql_query("DELETE FROM round WHERE round_id >= 1 AND map_id='" . $this->currentMap->getMapId() . "'");
                    mysql_query("DELETE FROM round_summary WHERE round_id >= 1 AND map_id='" . $this->currentMap->getMapId() . "'");
                } else {
                    // Getting file to restore
                    $data = $this->rcon->send("mp_backup_round_file_last");
                    if (preg_match('!"mp_backup_round_file_last" = "(?<backup>[a-zA-Z0-9\-_\.]+)"!', $data, $match)) {
                        $this->backupFile = $match["backup"];
                    } else {
                        $this->addLog("Backup file not found, simulating one ");
                        $this->backupFile = "ebot_" . $this->match_id . "_round" . sprintf("%02s", $this->getNbRound()) . ".txt";
                    }

                    $this->addLog("Backup file :" . $this->backupFile);

                    $this->say("\001This round has been cancelled, we will restart at the begin of the round");
                    $this->enable = false;

                    $this->rcon->send("mp_backup_round_file \"ebot_paused_" . $this->match_id . "\"");
                    $this->rcon->send("mp_restartgame 1");
                    \mysql_query("UPDATE `matchs` SET ingame_enable = 0 WHERE id='" . $this->match_id . "'") or $this->addLog("Can't update ingame_enable", Logger::ERROR);

                    /* if ($this->getNbRound() == $this->maxRound + 1) {
                      $this->rcon->send("mp_swapteams");
                      $this->say("\001Don't panic, to prevent a bug from backup system, you are switched. You will be switched when you continue the match");
                      }

                      if ($this->getStatus() > self::STATUS_WU_OT_1_SIDE) {
                      $round = $this->getNbRound() - ($this->oldMaxround * 2);
                      if ($round % ($this->maxRound * 2) == $this->maxRound + 1) {
                      $this->rcon->send("mp_swapteams");
                      $this->say("\001Don't panic, to prevent a bug from backup system, you are switched. You will be switched when you continue the match");
                      }
                      } */

                    mysql_query("DELETE FROM player_kill WHERE round_id = " . $this->getNbRound() . " AND map_id='" . $this->currentMap->getMapId() . "'");
                    mysql_query("DELETE FROM round WHERE round_id = " . $this->getNbRound() . " AND map_id='" . $this->currentMap->getMapId() . "'");
                    mysql_query("DELETE FROM round_summary WHERE round_id = " . $this->getNbRound() . " AND map_id='" . $this->currentMap->getMapId() . "'");
                }

                $this->ready["ct"] = false;
                $this->ready["t"] = false;
                $this->stop["ct"] = false;
                $this->stop["t"] = false;
                $this->pause["ct"] = false;
                $this->pause["t"] = false;
                $this->unpause["ct"] = false;
                $this->unpause["t"] = false;
            } elseif ($this->getStatus() == self::STATUS_KNIFE) {
                $this->setStatus($this->getStatus() - 1, true);
                $this->currentMap->setStatus($this->currentMap->getStatus() - 1, true);

                $this->ready["ct"] = false;
                $this->ready["t"] = false;
                $this->stop["ct"] = false;
                $this->stop["t"] = false;
                $this->pause["ct"] = false;
                $this->pause["t"] = false;
                $this->unpause["ct"] = false;
                $this->unpause["t"] = false;

                $this->say("\001The knife round is stopped \005- \003" . $this->getStatusText());
                $this->rcon->send("mp_restartgame 1");
            }
        }
    }

    private function abortReady() {
        $this->ready['ct'] = $this->ready['t'] = false;
        $this->ready['ct'] = $this->ready['t'] = false;
        $this->delay_ready_countdown = 10;
        $this->delay_ready_inprogress = false;
    }

    private function startMatch($force_ready = false) {
        if (\eBot\Config\Config::getInstance()->getDelayReady() && !$force_ready && $this->ready['ct'] && $this->ready['t']) {
            if ($this->delay_ready_abort)
                $this->delay_ready_abort = false;
            $this->delay_ready_inprogress = true;
            TaskManager::getInstance()->addTask(new Task($this, self::TASK_DELAY_READY, microtime(true) + 1));
        } else {
            if ($this->ready['ct'] && $this->ready['t']) {
                if ($this->getStatus() == self::STATUS_WU_KNIFE) {
                    $this->stop['t'] = false;
                    $this->stop['ct'] = false;

                    $this->addMatchLog("<b>INFO:</b> Starting Knife Round");
                    $this->addLog("Starting Knife Round");

                    $this->setStatus(self::STATUS_KNIFE, true);
                    $this->currentMap->setStatus(Map::STATUS_KNIFE, true);

                    // FIX for warmup

                    $this->rcon->send("exec " . $this->matchData["rules"] . ".cfg; mp_warmuptime 0; mp_halftime_pausetimer 1; mp_warmup_pausetimer 0;");
                    $this->rcon->send("sv_rcon_whitelist_address \"" . \eBot\Config\Config::getInstance()->getBot_ip() . "\"");
                    $this->rcon->send("mp_halftime_duration 1; mp_roundtime_defuse 60");
                    $this->rcon->send("mp_warmup_end");
                    if (\eBot\Config\Config::getInstance()->getKo3Method() == "csay" && $this->pluginCsay) {
                        $this->rcon->send("csay_ko3");
                    } elseif (\eBot\Config\Config::getInstance()->getKo3Method() == "esl" && $this->pluginESL) {
                        $this->rcon->send("esl_ko3");
                        $this->say("KNIFE LIVE!");
                    } else {
                        $this->rcon->send("mp_restartgame 3");
                        $this->say("KNIFE!");
                        $this->say("KNIFE!");
                        $this->say("KNIFE!");
                    }

                    $this->waitForRestart = true;
                } else {
                    // FIX for warmup
                    $this->rcon->send("mp_warmup_pausetimer 0;");

                    $this->stop['t'] = false;
                    $this->stop['ct'] = false;
                    $this->waitForRestart = true;
                    $this->nbRS = 0;

                    $this->addMatchLog("<b>INFO:</b> Launching RS");
                    $this->addLog("Launching RS");

                    switch ($this->currentMap->getStatus()) {
                        case Map::STATUS_WU_1_SIDE:
                            $this->currentMap->setStatus(Map::STATUS_FIRST_SIDE, true);
                            $this->setStatus(self::STATUS_FIRST_SIDE, true);
                            $fichier = $this->matchData["rules"] . ".cfg; mp_maxrounds " . ($this->maxRound * 2);

                            // NEW
                            $this->rcon->send("exec $fichier; mp_warmuptime 0; mp_halftime_pausetimer 1;");
                            $this->rcon->send("sv_rcon_whitelist_address \"" . \eBot\Config\Config::getInstance()->getBot_ip() . "\"");
                            $this->rcon->send("mp_halftime_duration 1");
                            $this->rcon->send("mp_warmup_end");
                            if (\eBot\Config\Config::getInstance()->getLo3Method() == "csay" && $this->pluginCsay) {
                                $this->rcon->send("csay_lo3");
                            } elseif (\eBot\Config\Config::getInstance()->getLo3Method() == "esl" && $this->pluginESL) {
                                $this->rcon->send("esl_lo3");
                                $this->say("1st Side: LIVE!");
                            } else {
                                $this->rcon->send("mp_restartgame 3");
                                $this->say("LIVE!");
                                $this->say("LIVE!");
                                $this->say("LIVE!");
                            }
                            break;
                        case Map::STATUS_WU_2_SIDE :
                            $this->currentMap->setStatus(Map::STATUS_SECOND_SIDE, true);
                            $this->setStatus(self::STATUS_SECOND_SIDE, true);
                            $fichier = $this->matchData["rules"] . ".cfg";

                            // NEW
                            $this->waitForRestart = false;
                            $this->rcon->send("mp_halftime_pausetimer 0; ");
                            if ($this->config_full_score) {
                                $this->rcon->send("mp_match_can_clinch 0");
                            }
                            $this->say("2nd Side: LIVE!");
                            break;
                        case Map::STATUS_WU_OT_1_SIDE :
                            $this->currentMap->setStatus(Map::STATUS_OT_FIRST_SIDE, true);
                            $this->setStatus(self::STATUS_OT_FIRST_SIDE, true);
                            // NEW
                            $this->rcon->send("mp_halftime_pausetimer 0");
                            $this->say("1st Side OT: LIVE!");
                            $this->waitForRestart = false;
                            break;
                        case Map::STATUS_WU_OT_2_SIDE :
                            $this->currentMap->setStatus(Map::STATUS_OT_SECOND_SIDE, true);
                            $this->setStatus(self::STATUS_OT_SECOND_SIDE, true);

                            // NEW
                            $this->waitForRestart = false;
                            $this->rcon->send("mp_halftime_pausetimer 0");
                            $this->say("2nd Side OT: LIVE!");
                            break;
                    }

                    if ($this->getStatus() == self::STATUS_FIRST_SIDE) {
                        $this->recupStatus(true);
                    }
                }

                $this->ready['ct'] = false;
                $this->ready['t'] = false;
                $this->pause["ct"] = false;
                $this->pause["t"] = false;
                $this->unpause["ct"] = false;
                $this->unpause["t"] = false;
            }
        }
    }

    private function addLog($text, $type = Logger::LOG) {
        $text = $this->server_ip . " :: " . $text;

        switch ($type) {
            case Logger::DEBUG:
                Logger::debug($text);
                break;
            case Logger::LOG:
                Logger::log($text);
                break;
            case Logger::ERROR:
                Logger::error($text);
                break;
        }
    }

    private function addMatchLog($text, $admin = false, $time = true) {
        if ($time) {
            $text = date('Y-m-d H:i:s') . " - " . $text;
        }

        @file_put_contents($this->getLogAdminFilePath(), $text . "<br/>\n", FILE_APPEND);

        if ($admin) {
            return;
        }

        @file_put_contents($this->getLogFilePath(), $text . "<br/>\n", FILE_APPEND);
    }

    private function getColoredUserNameHTML($user, $team) {
        if ($team == "CT") {
            $color = "blue";
        } elseif ($team == "TERRORIST") {
            $color = "red";
        } else {
            $color = "orange";
        }

        return '<font color="' . $color . '">' . htmlentities($user) . '</font>';
    }

    private function swapSides() {
        if ($this->side['team_a'] == "ct") {
            $this->side['team_a'] = "t";
            $this->side['team_b'] = "ct";
        } else {
            $this->side['team_a'] = "ct";
            $this->side['team_b'] = "t";
        }
        $this->websocket['match']->sendData(json_encode(array('message' => 'teams', 'teamA' => $this->side['team_a'], 'teamB' => $this->side['team_b'], 'id' => $this->match_id)));
        $this->currentMap->setCurrentSide($this->side["team_a"], true);
    }

    private function getLogAdminFilePath() {
        return Logger::getInstance()->getLogPathAdmin() . "/match-" . $this->matchData["id"] . ".html";
    }

    private function getLogFilePath() {
        return Logger::getInstance()->getLogPath() . "/match-" . $this->matchData["id"] . ".html";
    }

    public function __call($name, $arguments) {
        $this->addLog("Call to inexistant function $name", Logger::ERROR);
    }

    public function adminStopNoRs() {
        $this->addLog("Match stopped by admin");
        $this->addMatchLog("Match stopped by admin");
        $this->say("#redMatch stopped by admin");

        $this->rcon->send("mp_teamname_1 \"\"; mp_teamflag_2 \"\";");
        $this->rcon->send("mp_teamname_2 \"\"; mp_teamflag_1 \"\";");
        $this->rcon->send("exec server.cfg");


        mysql_query("UPDATE `matchs` SET enable = 0, auto_start = 0 WHERE id = '" . $this->match_id . "'");
        $this->needDel = true;
        return true;
    }

    public function adminStop() {
        $this->addLog("Match stopped by admin");
        $this->addMatchLog("Match stopped by admin");
        $this->say("#redMatch stopped by admin");

        $this->rcon->send("mp_restartgame 1");

        $this->rcon->send("exec server.cfg");
        $this->rcon->send("mp_teamname_1 \"\"; mp_teamname_2 \"\"; mp_teamflag_1 \"\"; mp_teamflag_2 \"\"");

        mysql_query("UPDATE `matchs` SET enable = 0, auto_start = 0 WHERE id = '" . $this->match_id . "'");
        $this->needDel = true;
        return true;
    }

    public function adminPassKnife() {
        if ($this->getStatus() == self::STATUS_WU_KNIFE) {
            $this->addLog("Knife has been skipped by the admin");
            $this->addMatchLog("Knife has been skipped by the admin");
            $this->say("#redKnife has been skipped by the admin");

            $this->ready["ct"] = false;
            $this->ready["t"] = false;
            $this->currentMap->setStatus(Map::STATUS_WU_1_SIDE, true);
            $this->setStatus(self::STATUS_WU_1_SIDE, true);
            if (\eBot\Config\Config::getInstance()->getConfigKnifeMethod() == "knifestart")
                $this->forceRoundStartRecord = true;
            return true;
        }
    }

    public function adminExecuteCommand($command) {
        $reply = $this->rcon->send($command);
        return $reply;
    }

    public function adminSkipMap() {
        $backupMap = $this->currentMap;
        $this->currentMap = null;
        if ($backupMap->getMapsFor() == "team1") {
            $mapFor = "team2";
        } elseif ($backupMap->getMapsFor() == "team2") {
            $mapFor = "default";
        }

        foreach ($this->maps as $map) {
            if ($map->getMapsFor() == $mapFor) {
                if ($map->getStatus() == Map::STATUS_NOT_STARTED) {
                    $this->currentMap = $map;
                    break;
                }
            }
        }
        foreach ($this->maps as $map) {
            if ($map->getStatus() == Map::STATUS_NOT_STARTED) {
                if ($map->getMapsFor() == $mapFor) {
                    $this->currentMap = $map;
                    break;
                }
            }
        }

        if ($this->currentMap != null) {
            $this->currentMap->setStatus(Map::STATUS_STARTING, true);
            $this->setStatus(self::STATUS_STARTING, true);
            \mysql_query("UPDATE `matchs` SET `current_map` = '" . $this->currentMap->getMapId() . "' WHERE `id` = '" . $this->match_id . "'");

            Logger::debug("Setting need knife round on map");
            $this->currentMap->setNeedKnifeRound(true);
            $this->nbOT = 0;
            $this->score["team_a"] = 0;
            $this->score["team_b"] = 0;

            $this->addLog("Engaging next map " . $this->currentMap->getMapName());
            $this->addMatchLog("Engaging next map " . $this->currentMap->getMapName());
            $time = microtime(true);
            $this->timeEngageMap = $time;
            $this->addLog("Skipping Map");
            TaskManager::getInstance()->addTask(new Task($this, self::TASK_ENGAGE_MAP, $time));
        } else {
            $this->setStatus(self::STATUS_END_MATCH, true);
            Logger::error("Not map found");
            $this->addLog("Match is closed");
        }
        return true;
    }

    public function adminFixSides() {
        $this->swapSides();
        $this->sendTeamNames();
        $this->addLog("Hot swapping sides");
        return true;
    }

    public function adminStreamerReady() {
        if ($this->config_streamer) {
            $this->streamerReady = true;
            \mysql_query("UPDATE `matchs` SET `config_streamer` = 2 WHERE `id` = '" . $this->match_id . "'");
            if (($this->getStatus() == self::STATUS_WU_1_SIDE) || ($this->getStatus() == self::STATUS_WU_KNIFE)) {
                $this->say("\002Streamers are ready now!");
                $this->say("\001Please get ready: !ready");
            }
        }
        else
            $this->streamerReady = false;
        return true;
    }

    public function adminForceKnife() {
        if ($this->getStatus() == self::STATUS_WU_KNIFE) {
            $this->addLog("Knife has been forced by the admin");
            $this->addMatchLog("Knife has been forced by the admin");
            $this->say("#redKnife has been forced by the admin");

            $this->ready["ct"] = true;
            $this->ready["t"] = true;

            $this->startMatch(true);
            return true;
        }
    }

    public function adminForceKnifeEnd() {
        if ($this->getStatus() == self::STATUS_KNIFE) {
            $this->addLog("Knife has been skipped by the admin");
            $this->addMatchLog("Knife has been skipped by the admin");
            $this->say("#redKnife has been skipped by the admin");

            $this->ready["ct"] = false;
            $this->ready["t"] = false;
            $this->currentMap->setStatus(Map::STATUS_WU_1_SIDE, true);
            $this->setStatus(self::STATUS_WU_1_SIDE, true);
            return true;
        }
    }

    public function adminForceStart() {
        if ($this->isWarmupRound()) {
            $this->addLog("The match has been forced by the admin");
            $this->addMatchLog("The match has been forced by the admin");
            $this->say("#redThe match has been forced by the admin");

            $this->ready["ct"] = true;
            $this->ready["t"] = true;

            $this->startMatch(true);
            return true;
        }
    }

    public function adminPauseUnpause() {
        if ($this->isMatchRound() && $this->isPaused) {
            $this->isPaused = false;
            $this->say("Match is unpaused by admin, live !");
            $this->addMatchLog("Unpausing match by admin");
            $this->addLog('Match is unpaused!');
            if (\eBot\Config\Config::getInstance()->getPauseMethod() == "nextRound") {
                $this->rcon->send("mp_unpause_match");
            } else {
                $this->rcon->send("pause");
            }
            \mysql_query("UPDATE `matchs` SET `is_paused` = '0' WHERE `id` = '" . $this->match_id . "'");
            $this->websocket['match']->sendData(json_encode(array('message' => 'status', 'content' => 'is_unpaused', 'id' => $this->match_id)));

            $this->pause["ct"] = false;
            $this->pause["t"] = false;
            $this->unpause["ct"] = false;
            $this->unpause["t"] = false;
            return true;
        } elseif ($this->isMatchRound() && !$this->isPaused) {
            $this->isPaused = true;
            $this->say("Match is paused by admin");
            $this->say("Write !unpause to remove the pause when ready");
            $this->addMatchLog("Pausing match by admin");
            $this->addLog('Match is paused!');
            if (\eBot\Config\Config::getInstance()->getPauseMethod() == "nextRound") {
                $this->rcon->send("mp_pause_match");
            } else {
                $this->rcon->send("pause");
            }
            \mysql_query("UPDATE `matchs` SET `is_paused` = '1' WHERE `id` = '" . $this->match_id . "'");
            $this->websocket['match']->sendData(json_encode(array('message' => 'status', 'content' => 'is_paused', 'id' => $this->match_id)));

            $this->pause["ct"] = false;
            $this->pause["t"] = false;
            $this->unpause["ct"] = false;
            $this->unpause["t"] = false;
            return true;
        }
    }

    public function adminStopBack() {
        if ($this->isMatchRound()) {
            $this->addLog("The match has been stopped by the admin");
            $this->addMatchLog("The match has been stopped by the admin");
            $this->say("#redThe match has been stopped by the admin");
            $this->say("#redBack to warmup");

            $this->stop["ct"] = true;
            $this->stop["t"] = true;

            $this->stopMatch();
            return true;
        }
    }

    public function adminGoBackRounds($round) {
        $this->enable = false;
        $sql = mysql_query("SELECT * FROM  round_summary WHERE match_id = '" . $this->match_id . "' AND map_id = '" . $this->currentMap->getMapId() . "' AND round_id = $round");
        $req = mysql_fetch_array($sql);

        $backup = $req['backup_file_name'];

        $this->addLog("Admin backup round $round");
        $this->addLog("Backup file " . $backup);

        $this->stop["ct"] = false;
        $this->stop["t"] = false;
        if ($this->isPaused) {
            if (\eBot\Config\Config::getInstance()->getPauseMethod() == "nextRound") {
                $this->rcon->send("mp_unpause_match");
            } else {
                $this->rcon->send("pause");
            }
            $this->isPaused = false;
            $this->addLog("Disabling pause");
            \mysql_query("UPDATE `matchs` SET `is_paused` = '0' WHERE `id` = '" . $this->match_id . "'");
            $this->websocket['match']->sendData(json_encode(array('message' => 'status', 'content' => 'is_unpaused', 'id' => $this->match_id)));
        }
		
		$this->rcon->send("mp_unpause_match");

        $this->score["team_a"] = $req['score_a'];
        $this->score["team_b"] = $req['score_b'];

        @mysql_query("UPDATE `matchs` SET score_a = '" . $this->score["team_a"] . "', score_b ='" . $this->score["team_b"] . "' WHERE id='" . $this->match_id . "'");

        // To check with overtime
        if ($this->score["team_a"] + $this->score["team_b"] < $this->matchData["max_round"]) {
            $score = $this->currentMap->getCurrentScore();
            if ($score != null) {
                $score->setScore1Side1($this->score['team_a']);
                $score->setScore2Side1($this->score['team_b']);
                $score->setScore1Side2(0);
                $score->setScore2Side2(0);
                $score->saveScore();
            }
            $this->currentMap->calculScores();
        } else {
            $score = $this->currentMap->getCurrentScore();
            if ($score != null) {
                $score->setScore1Side2($this->score['team_a'] - $score->getScore1Side1());
                $score->setScore2Side2($this->score['team_b'] - $score->getScore2Side1());
                $score->saveScore();
            }
            $this->currentMap->calculScores();
        }

        // Sending roundbackup format file
        $this->rcon->send("mp_backup_round_file \"ebot_" . $this->match_id . "\"");

        // Prevent the halftime pausetimer
        $this->rcon->send("mp_halftime_pausetimer 0");

        // Sending restore
        $this->rcon->send("mp_backup_restore_load_file " . $backup);

        // Prevent a bug for double stop
        $this->rcon->send("mp_backup_round_file_last " . $backup);

        foreach ($this->players as &$player) {
            $player->restoreSnapshot($this->getNbRound() - 1);
        }

        // Determine status
        $status = false;
        $total = $this->score["team_a"] + $this->score["team_b"];
        if ($total < $this->matchData["max_round"]) {
            $status = self::STATUS_FIRST_SIDE;
            $statusMap = Map::STATUS_FIRST_SIDE;
        } elseif ($total < $this->matchData["max_round"] * 2) {
            $status = self::STATUS_SECOND_SIDE;
            $statusMap = Map::STATUS_SECOND_SIDE;
        } else {
            if ($this->config_ot) {
                $total -= $this->matchData["max_round"] * 2;
                $total_rest = $total % $this->ot_maxround * 2;
                if ($total_rest < $this->ot_maxround) {
                    $status = self::STATUS_OT_FIRST_SIDE;
                    $statusMap = Map::STATUS_OT_FIRST_SIDE;
                } else {
                    $status = self::STATUS_OT_SECOND_SIDE;
                    $statusMap = Map::STATUS_OT_SECOND_SIDE;
                }
            }
        }

        if ($status && $this->getStatus() != $status) {
            $this->addLog("Setting match to another status: " . $status);
            switch ($this->getStatus()) {
                case self::STATUS_SECOND_SIDE:
                    if ($status == self::STATUS_FIRST_SIDE) {
                        $this->addLog("Swapping teams !");
                        $this->swapSides();
                    }
                    break;
                case self::STATUS_OT_FIRST_SIDE:
                    if ($status == self::STATUS_FIRST_SIDE) {
                        $this->addLog("Swapping teams !");
                        $this->swapSides();
                    }
                    break;
                case self::STATUS_OT_SECOND_SIDE:
                    if ($status == self::STATUS_OT_FIRST_SIDE) {
                        $this->addLog("Swapping teams !");
                        $this->swapSides();
                    }

                    if ($status == self::STATUS_SECOND_SIDE) {
                        $this->addLog("Swapping teams !");
                        $this->swapSides();
                    }
                    break;
            }
            $this->setStatus($status, true);
            $this->currentMap->setStatus($statusMap, true);
        }

        $this->say("Round restored, going live !");
        \mysql_query("UPDATE `matchs` SET ingame_enable = 1 WHERE id='" . $this->match_id . "'") or $this->addLog("Can't update ingame_enable", Logger::ERROR);
        TaskManager::getInstance()->addTask(new Task($this, self::SET_LIVE, microtime(true) + 2));
        return true;
    }

    private function sendTeamNames() {
        if ($this->currentMap->getCurrentSide() == "ct") {
            $this->rcon->send("mp_teamname_1 \"" . $this->teamAName . "\"");
            $this->rcon->send("mp_teamname_2 \"" . $this->teamBName . "\"");
            $this->rcon->send("mp_teamflag_1 \"" . $this->teamAFlag . "\"");
            $this->rcon->send("mp_teamflag_2 \"" . $this->teamBFlag . "\"");
        } else {
            $this->rcon->send("mp_teamname_2 \"" . $this->teamAName . "\"");
            $this->rcon->send("mp_teamname_1 \"" . $this->teamBName . "\"");
            $this->rcon->send("mp_teamflag_2 \"" . $this->teamAFlag . "\"");
            $this->rcon->send("mp_teamflag_1 \"" . $this->teamBFlag . "\"");
        }
    }

    private function getRoundTime() {
        return time() - $this->timeRound;
    }

    public function getIdentifier() {
        return @$this->matchData["identifier_id"];
    }

    public function getMatchId() {
        return @$this->matchData["id"];
    }

    public function getCurrentMapId() {
        return $this->currentMap->getMapId();
    }

}

?>
