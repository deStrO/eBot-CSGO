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
use eBot\Exception\Match_Exception;
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
    const TASK_ENGAGE_FIRST_MAP = "engageFirstMap";
    const TASK_ENGAGE_CURRENT_MAP = "engageCurrentMap";
    const CHANGE_HOSTNAME = "changeHostname";
    const TEST_RCON = "testRcon";
    const REINIT_RCON = "rconReinit";

    // Variable calculable (pas en BDD)
    private $players = array();
    private $players_old = array();
    private $rcon = null;
    private $lastMessage = 0;
    private $match = null;
    private $nbRS = 0;
    private $message = 0;
    private $pluginCsay = false;
    private $pluginSwitch = false;
    private $pluginPrintPlayers = false;
    private $ready = array("ct" => false, "t" => false);
    private $wait3rs = false;
    private $flood = array();
    private $gameBombPlanter = null;
    private $gameBombDefuser = null;
    private $stop = array("ct" => false, "t" => false);
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
    private $timeRound = 0;
    // Variable en BDD obligatoire
    private $match_id = 0;
    private $server_ip = "";
    private $score = array("team_a" => 0, "team_b" => 0);
    private $nbRound = 0;
    private $nbOT = 0;
    private $side = array("team_a" => "ct", "team_b" => "t");
    private $scoreSide = array();
    private $scoreJoueurSide = array();
    private $config_full_score = false;
    private $config_ot = false;
    private $config_switch_auto = false;
    private $config_kniferound = false;
    private $rules;
    private $maxRound = 15;
    private $oldMaxround = 15;
    private $maps = array();
    private $matchData = array();
    private $currentMap = null;
    private $messageManager;
    private $rconPassword;
    private $isPaused;
    private $pause = array("ct" => false, "t" => false);
    private $unpause = array("ct" => false, "t" => false);

    public function __construct($match_id, $server_ip, $rcon) {
        Logger::debug("Registring MessageManager");
        $this->messageManager = \eBot\Manager\MessageManager::getInstance("CSGO");

        Logger::debug("Creating match #" . $match_id . " on $server_ip");

        $this->match_id = $match_id;
        $this->server_ip = $server_ip;

        $query = \mysql_query("SELECT * FROM `matchs` WHERE id = '" . $match_id . "'");
        if (!$query) {
            throw new Match_Exception();
        }

        $this->matchData = \mysql_fetch_assoc($query);

        $this->addMatchLog("----------- Creating log file -----------", false, false);
        $this->addMatchLog("- Paramètre du match", false, false);
        $this->addMatchLog("- Match ID: " . $this->match_id, false, false);
        $this->addMatchLog("- Teams: " . $this->matchData["team_a"] . " - " . $this->matchData["team_b"], false, false);
        $this->addMatchLog("- MaxRound: " . $this->matchData["max_round"], false, false);

        $ip = explode(":", $this->server_ip);
        try {
            $this->rcon = new Rcon($ip[0], $ip[1], $rcon);
            $this->rconPassword = $rcon;
            Logger::log("RCON init ok");
            $this->rcon->send("log on;mp_logdetail 0;logaddress_del " . \eBot\Config\Config::getInstance()->getBot_ip() . ":" . \eBot\Config\Config::getInstance()->getBot_port() . ";logaddress_add " . \eBot\Config\Config::getInstance()->getBot_ip() . ":" . \eBot\Config\Config::getInstance()->getBot_port());
            $this->addMatchLog("- RCON connection OK", true, false);
        } catch (\Exception $ex) {
            $this->needDel = true;
            Logger::error("Rcon failed - " . $ex->getMessage());
            $this->addMatchLog("RCON Failed - " . $ex->getMessage(), false, false);
            throw new Match_Exception();
        }

        TaskManager::getInstance()->addTask(new Task($this, self::TEST_RCON, microtime(true) + 30));

        // Détection des plugins
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

        $this->config_full_score = $this->matchData["config_full_score"];
        $this->config_kniferound = $this->matchData["config_knife_round"];
        $this->config_switch_auto = $this->matchData["config_switch_auto"];
        $this->config_ot = $this->matchData["config_ot"];

        $this->maxRound = $this->matchData["max_round"];
        $this->oldMaxround = $this->maxRound;
        $this->rules = $this->matchData["rules"];

        $this->status = $this->matchData["status"];

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

        // Fixing for maxround in OT
        if ($this->getStatus() >= self::STATUS_WU_OT_1_SIDE) {
            $this->maxRound = \eBot\Config\Config::getInstance()->getNbRoundOvertime();
        }

        Logger::debug("Loading maps");
        $query = \mysql_query("SELECT * FROM `maps` WHERE match_id = '" . $match_id . "'");
        if (!$query) {
            throw new Match_Exception();
        }

        while ($data = \mysql_fetch_assoc($query)) {
            $this->maps[$data["id"]] = new Map($data);
            $this->maps[$data["id"]]->setNbMaxRound($this->maxRound);
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
                throw new Match_Exception();
            }
        }

        if ($this->currentMap == null) {
            $this->addLog("No map found, exiting matchs", Logger::ERROR);
            mysql_query("UPDATE `matchs` SET enable='n', status='" . self::STATUS_END_MATCH . "' WHERE id='" . $this->match_id . "'");
            throw new Match_Exception();
        }

        $this->addLog("Maps selected: #" . $this->currentMap->getMapId() . " - " . $this->currentMap->getMapName() . " - " . $this->currentMap->getStatusText());

        if ($this->getStatus() == self::STATUS_STARTING) {
            if (($this->currentMap->getStatus() == Map::STATUS_NOT_STARTED) || ($this->currentMap->getStatus() == Map::STATUS_STARTING)) {
                if ($this->config_kniferound) {
                    Logger::debug("Setting need knife round on map");
                    $this->currentMap->setNeedKnifeRound(true);
                }
            }

            Logger::debug("Schedule task for first map");
            TaskManager::getInstance()->addTask(new Task($this, self::TASK_ENGAGE_FIRST_MAP, microtime(true) + 1));
        } else {
            if (($this->currentMap->getStatus() == Map::STATUS_NOT_STARTED) || ($this->currentMap->getStatus() == Map::STATUS_STARTING)) {
                Logger::debug("Current map is not started/starting, engaging map");
                TaskManager::getInstance()->addTask(new Task($this, self::TASK_ENGAGE_CURRENT_MAP, microtime(true) + 1));
            } else {
                Logger::debug("Restore score");
            }
        }

        TaskManager::getInstance()->addTask(new Task($this, self::CHANGE_HOSTNAME, microtime(true) + 5));

        if ($this->currentMap->getCurrentSide() == "ct") {
            $this->side['team_a'] = "ct";
            $this->side['team_b'] = "t";
        } else {
            $this->side['team_a'] = "t";
            $this->side['team_b'] = "ct";
        }

        $this->currentMap->calculScores();

        $this->score["team_a"] = $this->currentMap->getScore1();
        $this->score["team_b"] = $this->currentMap->getScore2();

        @mysql_query("UPDATE `matchs` SET score_a = '" . $this->score["team_a"] . "', score_b ='" . $this->score["team_b"] . "' WHERE id='" . $this->match_id . "'");

        // Setting nb OverTime
        $this->nbOT = $this->currentMap->getNbOt();

        if ($this->currentMap->getStatus() == Map::STATUS_END_KNIFE) {
            $this->addLog("Setting round to knife round, because was waiting end knife");
            $this->currentMap->setStatus(Map::STATUS_WU_KNIFE, true);
            $this->setStatus(Map::STATUS_WU_KNIFE, true);
        }

        $this->recupStatus();

        if ($this->matchData["config_password"] != "") {
            $this->rcon->send("sv_password \"" . $this->matchData["config_password"] . "\"");
        }

        $this->addMatchLog("----------- End match loading -----------", false, false);

        if ($this->getStatus() < self::STATUS_WU_1_SIDE) {
            $this->sendTeamNames();
        }
        
        $this->rcon->send("mp_backup_round_file \"ebot_".$this->match_id."\""); 
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

    public function getStatusText() {
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
                return "First side - Round #" . $this->getNbRound();
            case self::STATUS_WU_2_SIDE:
                return "Warmup second side";
            case self::STATUS_SECOND_SIDE:
                return "Second side - Round #" . $this->getNbRound();
            case self::STATUS_WU_OT_1_SIDE:
                return "Warmup first side OverTime";
            case self::STATUS_OT_FIRST_SIDE:
                return "First side OverTime - Round #" . $this->getNbRound();
            case self::STATUS_WU_OT_2_SIDE:
                return "Warmup second side OverTime";
            case self::STATUS_OT_SECOND_SIDE:
                return "Second side OverTime - Round #" . $this->getNbRound();
            case self::STATUS_END_MATCH:
                return "Finished";
        }
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($newStatus, $save = false) {
        $this->status = $newStatus;
        if ($save) {
            $this->message = 0;
            Logger::debug("Updating status to " . $this->getStatusText() . " in database");
            mysql_query("UPDATE `matchs` SET status='" . $newStatus . "' WHERE id='" . $this->match_id . "'");
        }
    }

    private function getHostname() {
        return "eBot :: " . $this->matchData["team_a"] . " vs " . $this->matchData["team_b"];
    }

    public function taskExecute($name) {
        if ($name == self::TASK_ENGAGE_FIRST_MAP) {
            $this->engageFirstMap();
        } elseif ($name == self::CHANGE_HOSTNAME) {
            if ($this->rcon->getState()) {
                $this->rcon->send('hostname "' . $this->getHostname() . '"');
            } else {
                TaskManager::getInstance()->addTask(new Task($this, self::CHANGE_HOSTNAME, microtime(true) + 5));
            }
        } elseif ($name == self::TEST_RCON) {
            if ($this->rcon->getState()) {
                if (!$this->rcon->send('echo "eBot"')) {
                    $ip = explode(":", $this->server_ip);
                    try {
                        $this->rcon = new Rcon($ip[0], $ip[1], $this->rconPassword);
                        $this->rcon->send("echo eBot");
                    } catch (\Exception $ex) {
                        Logger::error("Reinit rcon failed - " . $ex->getMessage());
                        Logger::error("Trying to rengage in 10 seconds");

                        \eBot\Manager\MatchManager::getInstance()->delayServer($this->server_ip, 10);
                        $this->needDel = true;
                    }
                }
            }
            TaskManager::getInstance()->addTask(new Task($this, self::TEST_RCON, microtime(true) + 10));
        } elseif ($name == self::REINIT_RCON) {
            $ip = explode(":", $this->server_ip);
            try {
                $this->rcon = new Rcon($ip[0], $ip[1], $this->rconPassword);
                $this->rcon->send("echo eBot");
            } catch (\Exception $ex) {
                Logger::error("Reinit rcon failed - " . $ex->getMessage());
                Logger::error("Trying to rengage in 10 seconds");

                \eBot\Manager\MatchManager::getInstance()->delayServer($this->server_ip, 10);
                $this->needDel = true;
            }
        }
    }

    /**
     * Engagin the first map
     */
    private function engageFirstMap() {
        if ($this->currentMap == null) {
            $this->addLog("Can't engage first map");
            return;
        }

        if (($this->currentMap->getStatus() == Map::STATUS_STARTING) || ($this->currentMap->getStatus() == Map::STATUS_NOT_STARTED)) {
            $this->addLog("Engaging the first map");

            // Changing map
            $this->addLog("Changing map to " . $this->currentMap->getMapName());
            $this->rcon->send("changelevel " . $this->currentMap->getMapName());

            if ($this->config_kniferound) {
                $this->setStatus(self::STATUS_WU_KNIFE, true);
                $this->currentMap->setStatus(Map::STATUS_WU_KNIFE, true);
            } else {
                $this->setStatus(self::STATUS_WU_1_SIDE, true);
                $this->currentMap->setStatus(Map::STATUS_WU_1_SIDE, true);
            }

            TaskManager::getInstance()->addTask(new Task($this, self::CHANGE_HOSTNAME, microtime(true) + 3));
        } else {
            $this->setStatus($this->currentMap->getStatus(), true);
            Logger::error("Map already engaged");
        }
    }

    private function isWarmupRound() {
        return ($this->getStatus() == self::STATUS_WU_1_SIDE)
                || ($this->getStatus() == self::STATUS_WU_2_SIDE)
                || ($this->getStatus() == self::STATUS_WU_KNIFE)
                || ($this->getStatus() == self::STATUS_END_KNIFE)
                || ($this->getStatus() == self::STATUS_WU_OT_1_SIDE)
                || ($this->getStatus() == self::STATUS_WU_OT_2_SIDE);
    }

    private function isMatchRound() {
        return ($this->getStatus() == self::STATUS_FIRST_SIDE)
                || ($this->getStatus() == self::STATUS_SECOND_SIDE)
                || ($this->getStatus() == self::STATUS_OT_FIRST_SIDE)
                || ($this->getStatus() == self::STATUS_OT_SECOND_SIDE);
    }

    public function sendRotateMessage() {
        if (!$this->isWarmupRound())
            return;

        if ($this->matchData["enable"] == 1) {
            if (time() - $this->lastMessage >= 8) {
                // Récupération du SIDE de l'équipe
                $teamA = strtoupper($this->side['team_a']);
                $teamB = strtoupper($this->side['team_b']);

                if ($this->ready[$this->side['team_a']])
                    $teamA = "\004$teamA\001";
                if ($this->ready[$this->side['team_b']])
                    $teamB = "\004$teamB\001";

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

                $messages [] = "\003$message - \005" . $this->matchData['team_a'] . " \001($teamA\001) \001VS \001($teamB\001) \005" . $this->matchData['team_b'];
                $messages [] = "\003Available commands: !help, !rules, !ready, !notready";
                foreach (\eBot\Config\Config::getInstance()->getPubs() as $pub) {
                    $messages [] = "\003$pub";
                }

                $message = $messages[$this->message++ % count($messages)];
                $this->lastMessage = time();
                $this->say($message);
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

        try {
            if (!$this->pluginCsay) {
                $message = str_replace(array("\001", "\002", "\003", "\004", "\005", "\006", "\007"), array("", "", "", "", "", "", ""), $message);
                $message = str_replace(";", ",", $message);
                $this->rcon->send('say "eBot: ' . addslashes($message) . '"');
            } else {
                $this->rcon->send('csay_all "' . "e\004Bot\001: " . addslashes($message) . '"');
            }
        } catch (\Exception $ex) {
            Logger::error("Say failed - " . $ex->getMessage());
        }
    }

    public function destruct() {
        TaskManager::getInstance()->removeAllTaskForObject($this);
        unset($this->rcon);
        $this->addLog("Destructing match " . $this->match_id);
    }

    public function getNeedDel() {
        return $this->needDel;
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
                case "eBot\Message\Type\JoinTeam":
                    return $this->processJoinTeam($message);
                case "eBot\Message\Type\Kill":
                    return $this->processKill($message);
                case "eBot\Message\Type\RoundRestart":
                    return $this->processRoundRestart($message);
                case "eBot\Message\Type\RoundScored":
                    return $this->processRoundScored($message);
                case "eBot\Message\Type\RoundStart":
                    return $this->processRoundStart($message);
                case "eBot\Message\Type\Say":
                    return $this->processSay($message);

                default:
                    $this->addLog("Message non traité: " . get_class($message));
                    break;
            }
        }
    }

    private function processChangeMap(\eBot\Message\Type\ChangeMap $message) {
        Logger::debug("Processing Change Map");

        $this->addLog("Loading maps " . $message->maps);
        $this->addMatchLog("Loading maps " . $message->maps);

        $ip = explode(":", $this->server_ip);
        try {
            $this->rcon = new Rcon($ip[0], $ip[1], $this->rconPassword);
            $this->rcon->send("echo eBot;");

            if ($this->matchData["config_password"] != "") {
                $this->rcon->send("sv_password \"" . $this->matchData["config_password"] . "\"");
            }

            $this->rcon->send("mp_teamname_1 \"" . $this->matchData["team_a"] . "\"");
            $this->rcon->send("mp_teamname_2 \"" . $this->matchData["team_b"] . "\"");
            $this->rcon->send("mp_teamflag_1 \"FR\"");
            $this->rcon->send("mp_teamflag_1 \"FR\"");
        } catch (\Exception $ex) {
            Logger::error("Reinit rcon failed - " . $ex->getMessage());
            TaskManager::getInstance()->addTask(new Task($this, self::REINIT_RCON, microtime(true) + 1));
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
        if ($text == "!stats") {
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
            if ($this->getStatus() == self::STATUS_KNIFE) {
                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->matchData['team_a'] : $this->matchData['team_b'];

                    if (!$this->restart['ct']) {
                        $this->restart['ct'] = true;
                        $this->say($team . " (CT) \003want to restart the knife");
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->matchData['team_a'] : $this->matchData['team_b'];

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

                    $this->startMatch();
                }
            }
        } elseif ($text == "!stop") {
            if (!$this->isWarmupRound()) {
                $this->addLog($message->getUserName() . " (" . $message->getUserTeam() . ") say stop");

                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->matchData['team_a'] : $this->matchData['team_b'];

                    if (!$this->stop['ct']) {
                        $this->stop['ct'] = true;
                        $this->say($team . " (CT) \003want to stop");
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->matchData['team_a'] : $this->matchData['team_b'];

                    if (!$this->stop['t']) {
                        $this->stop['t'] = true;
                        $this->say($team . " (T) \003want to stop");
                    }
                }

                $this->stopMatch();
            }
        } elseif ($text == "!ready") {
            if ($this->isWarmupRound()) {
                $this->addLog($message->getUserName() . " (" . $message->getUserTeam() . ") say ready");

                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->matchData['team_a'] : $this->matchData['team_b'];

                    if (!$this->ready['ct']) {
                        $this->ready['ct'] = true;
                        $this->say($team . " (CT) \003is now \004ready");
                    } else {
                        $this->say($team . " (CT) \003is already \004ready");
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->matchData['team_a'] : $this->matchData['team_b'];

                    if (!$this->ready['t']) {
                        $this->ready['t'] = true;
                        $this->say($team . " (T) \003is now \004ready");
                    } else {
                        $this->say($team . " (T) \003is already \004ready");
                    }
                }

                $this->startMatch();
            }
        } elseif ($text == "!pause") {
            if ($this->isMatchRound() && !$this->isPaused) {
                $this->addLog($message->getUserName() . " (" . $message->getUserTeam() . ") say pause");

                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->matchData['team_a'] : $this->matchData['team_b'];

                    if (!$this->pause['ct']) {
                        $this->pause['ct'] = true;
                        $this->say($team . " (CT) \003want to pause, write !pause to confirm");
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->matchData['team_a'] : $this->matchData['team_b'];

                    if (!$this->pause['t']) {
                        $this->pause['t'] = true;
                        $this->say($team . " (T) \003want to pause, write !pause to confirm");
                    }
                }

                $this->pauseMatch();
            }
        } elseif ($text == "!unpause") {
            if ($this->isMatchRound() && $this->isPaused) {
                $this->addLog($message->getUserName() . " (" . $message->getUserTeam() . ") say pause");

                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->matchData['team_a'] : $this->matchData['team_b'];

                    if (!$this->unpause['ct']) {
                        $this->unpause['ct'] = true;
                        $this->say($team . " (CT) \003want to remove pause, write !unpause to confirm");
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->matchData['team_a'] : $this->matchData['team_b'];

                    if (!$this->unpause['t']) {
                        $this->unpause['t'] = true;
                        $this->say($team . " (T) \003want to remove pause, write !unpause to confirm");
                    }
                }

                $this->unpauseMatch();
            }
        } elseif (($this->getStatus() == self::STATUS_END_KNIFE) && ($text == "!stay")) {
            if ($message->getUserTeam() == $this->winKnife) {
                $this->addLog($message->getUserName() . " want to stay, going to warmup");

                $this->setStatus(self::STATUS_WU_1_SIDE, true);
                $this->currentMap->setStatus(Map::STATUS_WU_1_SIDE, true);

                $this->say("nothing change, going to warmup");
            }
        } elseif (($this->getStatus() == self::STATUS_END_KNIFE) && ($text == "!switch")) {
            if ($message->getUserTeam() == $this->winKnife) {
                $this->addLog($message->getUserName() . " want to stay, going to warmup");

                $this->setStatus(self::STATUS_WU_1_SIDE, true);
                $this->currentMap->setStatus(Map::STATUS_WU_1_SIDE, true);

                $this->swapSides();

                $this->say("swaping teams");
                $this->rcon->send("mp_swapteams");
                $this->sendTeamNames();
            }
        } elseif ($text == "!notready") {
            if ($this->isWarmupRound()) {
                $this->addLog($message->getUserName() . " (" . $message->getUserTeam() . ") say notready");

                if ($message->getUserTeam() == "CT") {
                    $team = ($this->side['team_a'] == "ct") ? $this->matchData['team_a'] : $this->matchData['team_b'];

                    if ($this->ready['ct']) {
                        $this->ready['ct'] = false;
                        $this->say($team . " (CT) \003is now \004not ready");
                    } else {
                        $this->say($team . " (CT) \003is already \004not ready");
                    }
                } elseif ($message->getUserTeam() == "TERRORIST") {
                    $team = ($this->side['team_a'] == "t") ? $this->matchData['team_a'] : $this->matchData['team_b'];

                    if ($this->ready['t']) {
                        $this->ready['t'] = false;
                        $this->say($team . " (T) \003is now \004not ready");
                    } else {
                        $this->say($team . " (T) \003is already \004not ready");
                    }
                }
            }
        } elseif ($text == "!status") {
            if ($this->pluginCsay) {
                $this->addLog($message->getUserName() . " ask status");
                $this->rcon->send("csay_to_player " . $message->userId . " \"e\004Bot\001: Current status: \002" . $this->getStatusText() . "\"");
            }
        } elseif ($text == "!score") {
            if ($this->pluginCsay) {
                $this->addLog($message->getUserName() . " ask status");
                $this->rcon->send("csay_to_player " . $message->userId . " \"e\004Bot\001: \005" . $this->matchData["team_a"] . " \004" . $this->currentMap->getScore1() . " \001- \004" . $this->currentMap->getScore2() . " \005" . $this->matchData["team_b"] . "\"");
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

            $team = ($this->side['team_a'] == \strtolower($message->getTeamWin())) ? $this->matchData['team_a'] : $this->matchData['team_b'];

            $this->say("\005$team won the knife, !stay or !switch");

            return;
        }

        if (in_array($this->getStatus(), array(self::STATUS_FIRST_SIDE, self::STATUS_SECOND_SIDE, self::STATUS_OT_FIRST_SIDE, self::STATUS_OT_SECOND_SIDE))) {
            // Add point
            foreach ($this->players as $player)
                $player->saveKillRound();

            $teamWin = $this->currentMap->addRound($message->getTeamWin());

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
                                        mysql_query("UPDATE players SET nb1 = nb1 + 1 WHERE id = '" . $this->players[$id]->getId() . "'") or Logger::error("Can't update " . $this->players[$id]->getId() . " situation");
                                        $this->addLog("Situation spécial réussie 1v" . $this->specialSituation['situation'] . " (" . $this->players[$id]->get("name") . ")");
                                        $this->addMatchLog("<b>" . $this->players[$id]->get("name") . "</b> a mis un 1v" . $this->specialSituation['situation'] . " !");
                                        $this->players[$id]->inc("v1");
                                    }
                                }
                            } else {
                                $id = $this->specialSituation['id'];
                                if ($this->players[$id]) {
                                    $this->addMatchLog("<b>" . $this->players[$id]->get("name") . "</b> a mis un 1v" . $this->specialSituation['situation'] . " !");
                                    mysql_query("UPDATE players SET nb" . $this->specialSituation['situation'] . " = nb" . $this->specialSituation['situation'] . " + 1 WHERE id='" . $this->players[$id]->getId() . "'") or Logger::error("Can't update " . $this->players[$id]->getId() . " situation");
                                    $this->players[$id]->inc("v" . $this->specialSituation['situation']);
                                    $this->addLog("Situation spécial réussie 1v" . $this->specialSituation['situation'] . " (" . $this->players[$id]->get("name") . ")");
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
                }
            }

            if ($message->type == "bombeexploded") {
                if ($this->gameBombPlanter != null) {
                    $this->gameBombPlanter->inc("bombe");
                    $this->gameBombPlanter->inc("point", 2);
                    $this->gameBombPlanter->saveScore();
                }
            }

            $this->score["team_a"] = $this->currentMap->getScore1();
            $this->score["team_b"] = $this->currentMap->getScore2();

            if ($this->getNbRound() == $this->maxRound - 1) {
                // Ensure that halftime_pausetimer is set
                $this->rcon->send("mp_halftime_pausetimer 1");
            }

            $this->say("\005" . $this->matchData["team_a"] . " \004" . $this->currentMap->getScore1() . " \001- \004" . $this->currentMap->getScore2() . " \005" . $this->matchData["team_b"]);

            $this->addLog($this->matchData["team_a"] . " (" . $this->currentMap->getScore1() . ") - (" . $this->currentMap->getScore2() . ") " . $this->matchData["team_b"]);
            $this->addMatchLog("Un round a été marqué - " . $this->matchData["team_a"] . " (" . $this->currentMap->getScore1() . ") - (" . $this->currentMap->getScore2() . ") " . $this->matchData["team_b"]);

            @mysql_query("UPDATE `matchs` SET score_a = '" . $this->score["team_a"] . "', score_b ='" . $this->score["team_b"] . "' WHERE id='" . $this->match_id . "'") or $this->addLog("Can't match " . $this->match_id . " scores", Logger::ERROR);

            // ROUND SUMMARY            
            mysql_query("INSERT INTO round_summary 
                            (`match_id`,`map_id`,`bomb_planted`,`bomb_defused`,`bomb_exploded`,`ct_win`, `t_win`,`round_id`,`win_type`,`team_win`,`created_at`,`updated_at`)
                            VALUES 
                            ('" . $this->match_id . "','" . $this->currentMap->getMapId() . "', '" . ($this->gameBombPlanter != null) . "', '" . ($message->type == "bombdefused") . "', '" . ($message->type == "bombeexploded") . "', '" . ($message->getTeamWin() == "CT") . "', '" . ($message->getTeamWin() != "CT") . "', '" . ($this->getNbRound() - 1) . "','" . $message->type . "','" . $teamWin . "', NOW(), NOW())
                        ") or $this->addLog("Can't insert round summary match " . $this->match_id . "", Logger::ERROR);

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
                if (($this->score["team_a"] + $this->score["team_b"] == $this->maxRound * 2)
                        || ($this->score["team_a"] > $this->maxRound && !$this->config_full_score)
                        || ($this->score["team_b"] > $this->maxRound && !$this->config_full_score)) {

                    if (($this->score["team_a"] == $this->score["team_b"]) && ($this->config_ot)) {
                        $this->setStatus(self::STATUS_WU_OT_1_SIDE, true);
                        $this->currentMap->setStatus(Map::STATUS_WU_OT_1_SIDE, true);
                        $this->maxRound = \eBot\Config\Config::getInstance()->getNbRoundOvertime();
                        $this->currentMap->addOvertime();
                        $this->nbOT++;
                        $this->addLog("Going to overtime");
                        $this->say("Going to overtime");
                        $this->rcon->send("mp_restartgame 1");
                    } else {
                        $this->currentMap->setStatus(Map::STATUS_MAP_ENDED, true);

                        $this->lookEndingMatch();
                    }

                    $this->saveScore();
                }
            } elseif ($this->getStatus() == self::STATUS_OT_FIRST_SIDE) {
                $scoreToReach = $this->oldMaxround * 2 + $this->maxRound + ($this->maxRound * 2 * ($this->nbOT - 1));

                if ($this->score["team_a"] + $this->score["team_b"] == $scoreToReach) {
                    $this->setStatus(self::STATUS_WU_OT_2_SIDE, true);
                    $this->currentMap->setStatus(Map::STATUS_WU_OT_2_SIDE, true);
                    $this->saveScore();
                    $this->swapSides();
                    $this->sendTeamNames();

                    // Not needed anymore with last updates
                    // $this->rcon->send("mp_restartgame 1");

                    $this->rcon->send("mp_halftime_pausetimer 1");
                }
            } elseif ($this->getStatus() == self::STATUS_OT_SECOND_SIDE) {
                $scoreToReach = $this->oldMaxround * 2 + $this->maxRound * 2 + ($this->maxRound * 2 * ($this->nbOT - 1));
                $scoreToReach2 = $this->oldMaxround * 2 + $this->maxRound + ($this->maxRound * 2 * ($this->nbOT - 1));

                if (($this->score["team_a"] + $this->score["team_b"] == $scoreToReach)
                        || ($this->score["team_a"] > $scoreToReach2)
                        || ($this->score["team_b"] > $scoreToReach2)) {

                    if ($this->score["team_a"] == $this->score["team_b"]) {
                        $this->setStatus(self::STATUS_WU_OT_1_SIDE, true);
                        $this->currentMap->setStatus(Map::STATUS_WU_OT_1_SIDE, true);
                        $this->maxRound = \eBot\Config\Config::getInstance()->getNbRoundOvertime();
                        $this->currentMap->addOvertime();
                        $this->addLog("Going to overtime");
                        $this->rcon->send("mp_restartgame 1");
                    } else {
                        $this->currentMap->setStatus(Map::STATUS_MAP_ENDED, true);

                        $this->lookEndingMatch();
                    }
                    $this->saveScore();
                }
            }

            // Dispatching events
            $event = new \eBot\Events\Event\RoundScored();
            $event->setMatch($this);
            $event->setTeamA($this->matchData["team_a"]);
            $event->setTeamB($this->matchData["team_a"]);
            $event->setScoreA($this->score["team_a"]);
            $event->setScoreB($this->score["team_b"]);
            $event->setStatus($this->getStatus());
            \eBot\Events\EventDispatcher::getInstance()->dispatchEvent($event);
        }
    }

    private function lookEndingMatch() {
        $allFinish = true;
        foreach ($this->maps as $map) {
            if ($map->getStatus() != Map::STATUS_MAP_ENDED)
                $allFinish = false;
        }

        $this->rcon->send("tv_stoprecord");

        if (count($this->maps) == 1 || $allFinish) {
            $this->setStatus(self::STATUS_END_MATCH, true);

            $this->addLog("Match is closed");
            if ($this->score["team_a"] > $this->score["team_b"]) {
                $this->say($this->matchData["team_a"] . " win ! Final score: " . $this->score["team_a"] . "/" . $this->score["team_b"]);
                $this->addMatchLog($this->matchData["team_a"] . " win ! Final score: " . $this->score["team_a"] . "/" . $this->score["team_b"]);
            } elseif ($this->score["team_a"] < $this->score["team_b"]) {
                $this->say($this->matchData["team_b"] . " win ! Final score: " . $this->score["team_b"] . "/" . $this->score["team_a"]);
                $this->addMatchLog($this->matchData["team_b"] . " win ! Final score: " . $this->score["team_b"] . "/" . $this->score["team_a"]);
            } else {
                $this->say("Final score: " . $this->score["team_a"] . " - " . $this->score["team_b"] . " - Draw !");
                $this->addMatchLog("Final score: " . $this->score["team_a"] . " - " . $this->score["team_b"] . " - Draw !");
            }
            $this->rcon->send("exec server.cfg");
            $this->rcon->send("mp_teamname_1 \"\"; mp_teamname_2 \"\"; mp_teamflag_1 \"\"; mp_teamflag_1 \"\"");
        } else {
            // manage second map
            if ($this->score["team_a"] > $this->score["team_b"]) {
                $mapFor = "team2";
            } else {
                $mapFor = "team1";
            }

            $this->currentMap = null;

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

                Logger::debug("Setting need knife round on map");
                $this->currentMap->setNeedKnifeRound(true);
                $this->nbOT = 0;
                $this->score["team_a"] = 0;
                $this->score["team_b"] = 0;

                $this->addLog("Engaging next map " . $this->currentMap->getMapName());
                $this->addMatchLog("Engaging next map " . $this->currentMap->getMapName());
                TaskManager::getInstance()->addTask(new Task($this, self::TASK_ENGAGE_CURRENT_MAP, microtime(true) + 1));
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

    private function processKill(\eBot\Message\Type\Kill $message) {
        $this->processPlayer($message->getUserId(), $message->getUserName(), $message->getUserTeam(), $message->getUserSteamid());
        $this->processPlayer($message->getKilledUserId(), $message->getKilledUserName(), $message->getKilledUserTeam(), $message->getKilledUserSteamid());

        if (!$this->wait3rs && in_array($this->getStatus(), array(self::STATUS_FIRST_SIDE, self::STATUS_SECOND_SIDE, self::STATUS_OT_FIRST_SIDE, self::STATUS_OT_SECOND_SIDE))) {
            $killer = $this->findPlayer($message->userId, $message->userSteamid);
            $killed = $this->findPlayer($message->killedUserId, $message->killedUserSteamid);

            if ($this->firstFrag) {
                if ($killer != null) {
                    $killer->inc("firstKill");
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
                (`match_id`,`map_id`,`killer_name`,`killer_id`,`killed_name`,`killed_id`,`weapon`,`headshot`,`round_id`) 
                VALUES 
                ('" . $this->match_id . "','" . $this->currentMap->getMapId() . "', '" . addslashes($killer_name) . "', " . (($killer_id != null) ? $killer_id : "NULL") . ", '" . addslashes($killed_name) . "', " . (($killed_id != null) ? $killed_id : "NULL") . ", '" . $message->weapon . "', '" . $message->headshot . "','" . $this->getNbRound() . "')
                    ") or $this->addLog("Can't insert player_kill ".mysql_error(), Logger::ERROR);

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
        $this->addMatchLog($message->userName . " connected");
        $this->userToEnter[$message->userId] = $message->address;
    }

    private function processEnteredTheGame(\eBot\Message\Type\EnteredTheGame $message) {
        $this->addLog($message->userName . " entered the game");
    }

    private function processJoinTeam(\eBot\Message\Type\JoinTeam $message) {
        $this->processPlayer($message->getUserId(), $message->getUserName(), $message->joinTeam, $message->getUserSteamid());
        $this->addLog($message->userName . " join team " . $message->joinTeam);
        $this->addMatchLog($message->userName . " join team " . $message->joinTeam);
    }

    private function processDisconnected(\eBot\Message\Type\Disconnected $message) {
        $this->addLog($message->userName . " disconnected");
        $this->addMatchLog($message->userName . " disconnected");
        $player = $this->findPlayer($message->userId, $message->userSteamid);
        if ($player != null) {
            $player->setOnline(false);
        }
    }

    private $waitRoundStartRecord = false;

    private function processRoundRestart(\eBot\Message\Type\RoundRestart $message) {
        if ($this->wait3rs && $this->getStatus() == self::STATUS_FIRST_SIDE) {
            $this->waitRoundStartRecord = true;
        }
        $this->wait3rs = false;
    }

    private function processRoundStart(\eBot\Message\Type\RoundStart $message) {
        if ($this->waitRoundStartRecord) {
            Logger::log("Launching record match_" . $this->match_id . "_" . \eTools\Utils\Slugify::cleanTeamName($this->matchData['team_a']) . "_vs_" . \eTools\Utils\Slugify::cleanTeamName($this->matchData['team_b']) . ";");
            $this->rcon->send("tv_record match_" . $this->match_id . "_" . \eTools\Utils\Slugify::cleanTeamName($this->matchData['team_a']) . "_vs_" . \eTools\Utils\Slugify::cleanTeamName($this->matchData['team_b']) . ";");
            $this->waitRoundStartRecord = false;
        }

        $this->nbLast['nb_ct'] = $this->nbLast['nb_max_ct'];
        $this->nbLast['nb_t'] = $this->nbLast['nb_max_t'];
        $this->gameBombPlanter = null;
        $this->gameBombeDefuser = null;
        $this->firstFrag = true;
        $this->saveScore();
        $this->resetSpecialSituation();

        foreach ($this->players as $k => &$v) {
            $v->roundStart();
        }

        $this->countPlayers();

        $this->watchForSpecialSituation();
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

        $player->setOnline(true);
        $player->setUserName($user_name);
        $player->setCurrentTeam($team);
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
                        $this->addMatchLog("<b>Situation spécial ! 1v" . $nbAlive . " (" . $this->players[$id]->get("name") . ")</b>");
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
                        $this->addMatchLog("<b>Situation spécial ! 1v" . $nbAlive . " (" . $this->players[$id]->get("name") . ")</b>");
                    }
                }
            }
        } else {
            if (($this->specialSituation['side2'] != "both") && ($this->specialSituation['side'] != "both")) {
                if (($this->nbLast['nb_ct'] == 1) && ($this->nbLast['nb_t'] == 1)) {
                    if ($this->players[$this->specialSituation['id']]) {
                        $this->addLog("Situation spécial 1v1 ! - Le joueur " . $this->players[$this->specialSituation['id']]->get("name") . " est en 1v" . $this->specialSituation['situation']);
                        $this->addMatchLog("<b>Situation spécial 1v1 ! - Le joueur " . $this->players[$this->specialSituation['id']]->get("name") . " est en 1v" . $this->specialSituation['situation'] . "</b>", false);
                        $this->specialSituation['side2'] = "both";
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
        if ($this->pause["ct"] && $this->pause["t"] && $this->isMatchRound() && !$this->isPaused) {
            $this->isPaused = true;
            $this->say("Match is paused");
            $this->addMatchLog("Pausing match");
            $this->rcon->send("pause");

            $this->pause["ct"] = false;
            $this->pause["t"] = false;
            $this->unpause["ct"] = false;
            $this->unpause["t"] = false;
        }
    }

    private function unpauseMatch() {
        if ($this->unpause["ct"] && $this->unpause["t"] && $this->isMatchRound() && $this->isPaused) {
            $this->isPaused = false;
            $this->say("Match is unpaused, live !");
            $this->addMatchLog("Unpausing match");
            $this->rcon->send("pause");

            $this->pause["ct"] = false;
            $this->pause["t"] = false;
            $this->unpause["ct"] = false;
            $this->unpause["t"] = false;
        }
    }

    private function stopMatch() {
        if ($this->stop["ct"] && $this->stop["t"]) {
            if (in_array($this->getStatus(), array(self::STATUS_FIRST_SIDE, self::STATUS_SECOND_SIDE, self::STATUS_OT_FIRST_SIDE, self::STATUS_OT_SECOND_SIDE))) {
                if ($this->getStatus() == self::STATUS_FIRST_SIDE) {
                    $this->recupStatus(true);
                    mysql_query("DELETE FROM player_kill WHERE round_id >= 1 AND map_id='" . $this->currentMap->getMapId() . "'");
                    mysql_query("DELETE FROM player_kill WHERE round_id >= 1 AND map_id='" . $this->currentMap->getMapId() . "'");
                } elseif ($this->getStatus() == self::STATUS_SECOND_SIDE) {
                    mysql_query("DELETE FROM player_kill WHERE round_id > " . $this->maxRound . " AND map_id='" . $this->currentMap->getMapId() . "'");
                    mysql_query("DELETE FROM round_summary WHERE round_id > " . $this->maxRound . " AND map_id='" . $this->currentMap->getMapId() . "'");
                } elseif ($this->getStatus() == self::STATUS_OT_FIRST_SIDE) {
                    $scoreToReach = $this->oldMaxround * 2 + ($this->maxRound * 2 * ($this->nbOT - 1));
                    mysql_query("DELETE FROM player_kill WHERE round_id > " . $scoreToReach . " AND map_id='" . $this->currentMap->getMapId() . "'");
                    mysql_query("DELETE FROM round_summary WHERE round_id > " . $scoreToReach . " AND map_id='" . $this->currentMap->getMapId() . "'");
                } elseif ($this->getStatus() == self::STATUS_OT_SECOND_SIDE) {
                    $scoreToReach = $this->oldMaxround * 2 + $this->maxRound + ($this->maxRound * 2 * ($this->nbOT - 1));
                    mysql_query("DELETE FROM player_kill WHERE round_id > " . $scoreToReach . " AND map_id='" . $this->currentMap->getMapId() . "'");
                    mysql_query("DELETE FROM round_summary WHERE round_id > " . $scoreToReach . " AND map_id='" . $this->currentMap->getMapId() . "'");
                }

                $this->currentMap->removeLastScore();

                $this->setStatus($this->getStatus() - 1, true);
                $this->currentMap->setStatus($this->currentMap->getStatus() - 1, true);

                $this->addLog("Stopping current side, new status: " . $this->getStatusText());

                $this->ready["ct"] = false;
                $this->ready["t"] = false;
                $this->stop["ct"] = false;
                $this->stop["t"] = false;
                $this->pause["ct"] = false;
                $this->pause["t"] = false;
                $this->unpause["ct"] = false;
                $this->unpause["t"] = false;

                $this->score["team_a"] = $this->currentMap->getScore1();
                $this->score["team_b"] = $this->currentMap->getScore2();

                @mysql_query("UPDATE `matchs` SET score_a = '" . $this->score["team_a"] . "', score_b ='" . $this->score["team_b"] . "' WHERE id='" . $this->match_id . "'") or $this->addLog("Can't update scores", Logger::ERROR);

                $this->say("\001The match is stopped \005- \003" . $this->getStatusText());
                $this->rcon->send("mp_restartgame 1");
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

    private function startMatch() {
        if ($this->ready['ct'] && $this->ready['t']) {
            if ($this->getStatus() == self::STATUS_WU_KNIFE) {
                $this->stop['t'] = false;
                $this->stop['ct'] = false;
                $this->enable = true;

                $this->addMatchLog("<b>INFO:</b> Lancement du knife round");
                $this->addLog("Starting knife round");

                $this->setStatus(self::STATUS_KNIFE, true);
                $this->currentMap->setStatus(Map::STATUS_KNIFE, true);

                // FIX for warmup
                $this->rcon->send("exec " . $this->matchData["rules"] . ".cfg; mp_restartgame 3");
                $this->rcon->send("mp_warmuptime 0; mp_do_warmup_period 0; mp_warmup_pausetimer 0;");
                
                $this->say("KNIFE ROUND !");
                $this->say("KNIFE ROUND !");
                $this->say("KNIFE ROUND !");
            } else {
                // FIX for warmup
                $this->rcon->send("mp_warmup_pausetimer 0;");

                $this->stop['t'] = false;
                $this->stop['ct'] = false;
                $this->enable = true;
                $this->wait3rs = true;
                $this->nbRS = 0;

                $this->addMatchLog("<b>INFO:</b> Lancement des RS");
                $this->addLog("Launching RS");

                switch ($this->currentMap->getStatus()) {
                    case Map::STATUS_WU_1_SIDE:
                        $this->currentMap->setStatus(Map::STATUS_FIRST_SIDE, true);
                        $this->setStatus(self::STATUS_FIRST_SIDE, true);
                        $fichier = $this->matchData["rules"] . ".cfg; mp_maxrounds " . ($this->maxRound * 2);

                        // NEW
                        $this->rcon->send("exec $fichier; mp_warmuptime 0; mp_do_warmup_period 0; mp_restartgame 3; mp_halftime_pausetimer 1");
                        $this->say("Going live !!!");
                        break;
                    case Map::STATUS_WU_2_SIDE :
                        $this->currentMap->setStatus(Map::STATUS_SECOND_SIDE, true);
                        $this->setStatus(self::STATUS_SECOND_SIDE, true);
                        $fichier = $this->matchData["rules"] . ".cfg";

                        // NEW
                        $this->wait3rs = false; 
                        $this->rcon->send("mp_halftime_pausetimer 0");
                        break;
                    case Map::STATUS_WU_OT_1_SIDE :
                        $this->currentMap->setStatus(Map::STATUS_OT_FIRST_SIDE, true);
                        $this->setStatus(self::STATUS_OT_FIRST_SIDE, true);
                        $fichier = $this->matchData["rules"] . "_overtime.cfg; mp_maxrounds " . ($this->maxRound * 2);

                        // NEW
                        $this->rcon->send("exec $fichier; mp_warmuptime 0; mp_do_warmup_period 0; mp_restartgame 3; mp_halftime_pausetimer 1");
                        $this->say("Going live !!!");
                        break;
                    case Map::STATUS_WU_OT_2_SIDE :
                        $this->currentMap->setStatus(Map::STATUS_OT_SECOND_SIDE, true);
                        $this->setStatus(self::STATUS_OT_SECOND_SIDE, true);
                        $fichier = $this->matchData["rules"] . "_overtime.cfg";

                        // NEW
                        $this->wait3rs = false; 
                        $this->rcon->send("mp_halftime_pausetimer 0");
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

        return '<font color="' . $color . '">' . $user . '</font>';
    }

    private function swapSides() {
        if ($this->side['team_a'] == "ct") {
            $this->side['team_a'] = "t";
            $this->side['team_b'] = "ct";
        } else {
            $this->side['team_a'] = "ct";
            $this->side['team_b'] = "t";
        }
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

        $this->rcon->send("exec server.cfg");
        $this->rcon->send("mp_teamname_1 \"\"; mp_teamname_2 \"\"; mp_teamflag_1 \"\"; mp_teamflag_1 \"\"");

        mysql_query("UPDATE `matchs` SET enable = 0 WHERE id = '" . $this->match_id . "'");
        $this->needDel = true;
    }

    public function adminStop() {
        $this->addLog("Match stopped by admin");
        $this->addMatchLog("Match stopped by admin");
        $this->say("#redMatch stopped by admin");

        $this->rcon->send("mp_restartgame 1");

        $this->rcon->send("exec server.cfg");
        $this->rcon->send("mp_teamname_1 \"\"; mp_teamname_2 \"\"; mp_teamflag_1 \"\"; mp_teamflag_1 \"\"");

        mysql_query("UPDATE `matchs` SET enable = 0 WHERE id = '" . $this->match_id . "'");
        $this->needDel = true;
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
        }
    }

    public function adminForceKnife() {
        if ($this->getStatus() == self::STATUS_WU_KNIFE) {
            $this->addLog("Knife has been forced by the admin");
            $this->addMatchLog("Knife has been forced by the admin");
            $this->say("#redKnife has been forced by the admin");

            $this->ready["ct"] = true;
            $this->ready["t"] = true;

            $this->startMatch();
        }
    }

    public function adminForceStart() {
        if ($this->isWarmupRound()) {
            $this->addLog("The match has been forced by the admin");
            $this->addMatchLog("The match has been forced by the admin");
            $this->say("#redThe match has been forced by the admin");

            $this->ready["ct"] = true;
            $this->ready["t"] = true;

            $this->startMatch();
        }
    }
    
    public function adminPauseUnpause() {
        if ($this->isMatchRound() && $this->isPaused) {
            $this->isPaused = false;
            $this->say("Match is unpaused by admin, live !");
            $this->addMatchLog("Unpausing match by admin");
            $this->rcon->send("pause");

            $this->pause["ct"] = false;
            $this->pause["t"] = false;
            $this->unpause["ct"] = false;
            $this->unpause["t"] = false;
        } elseif ($this->isMatchRound() && !$this->isPaused) {
            $this->isPaused = true;
            $this->say("Match is paused by admin, live !");
            $this->addMatchLog("Pausing match by admin");
            $this->rcon->send("pause");

            $this->pause["ct"] = false;
            $this->pause["t"] = false;
            $this->unpause["ct"] = false;
            $this->unpause["t"] = false;
        }
    }


    public function adminStopBack() {
        if (!$this->isWarmupRound()) {
            $this->addLog("The match has been stopped by the admin");
            $this->addMatchLog("The match has been stopped by the admin");
            $this->say("#redThe match has been stopped by the admin");
            $this->say("#redBack to warmup");

            $this->stop["ct"] = true;
            $this->stop["t"] = true;

            $this->stopMatch();
        }
    }

    private function sendTeamNames() {
        if ($this->currentMap->getCurrentSide() == "ct") {
            $this->rcon->send("mp_teamname_1 \"" . $this->matchData["team_a"] . "\"");
            $this->rcon->send("mp_teamname_2 \"" . $this->matchData["team_b"] . "\"");
        } else {
            $this->rcon->send("mp_teamname_1 \"" . $this->matchData["team_a"] . "\"");
            $this->rcon->send("mp_teamname_2 \"" . $this->matchData["team_b"] . "\"");
        }

        $this->rcon->send("mp_teamflag_1 \"FR\"");
        $this->rcon->send("mp_teamflag_1 \"FR\"");
    }

}

?>
