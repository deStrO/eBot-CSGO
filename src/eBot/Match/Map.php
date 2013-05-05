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

class Map {

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
    const STATUS_MAP_ENDED = 13;

    private $map_id = 0;
    private $map_name = "de_dust2_se";
    private $score1 = 0;
    private $score2 = 0;
    private $current_side = "ct";
    private $status = self::STATUS_NOT_STARTED;
    private $maps_for = "default";
    private $nb_ot = 0;
    private $need_knife_round;
    private $scores = array();
    private $currentScore = null;
    private $nbMaxRound = 15;
    private $tvRecordFile = "";

    public function __construct($mapData) {
        Logger::debug("Creating maps " . $mapData["id"]);
        $this->setMapId($mapData["id"]);
        $this->setMapName($mapData["map_name"]);
        $this->setScore1($mapData["score_1"]);
        $this->setScore2($mapData["score_2"]);
        $this->setCurrentSide($mapData["current_side"]);
        $this->setStatus($mapData["status"]);
        $this->setMapsFor($mapData["maps_for"]);
        $this->setNbOt($mapData["nb_ot"]);
        $this->setTvRecordFile($mapData['tv_record_file']);

        Logger::log("Maps loaded " . $this->getMapName() . " (score: " . $this->getScore1() . " - " . $this->getScore2() . ") - Current left side: " . $this->getCurrentSide() . " - Current status: " . $this->getStatusText());

        $query = mysql_query("SELECT * FROM maps_score WHERE map_id = '" . $this->map_id . "' ORDER BY created_at DESC");
        while ($r = mysql_fetch_array($query)) {
            $this->scores[] = new Score($r);
        }

        if (count($this->scores) == 0) {
            mysql_query("INSERT INTO maps_score (`map_id`,`type_score`,`score1_side1`,`score1_side2`,`score2_side1`,`score2_side2`, `created_at`,`updated_at`) VALUES ('" . $mapData["id"] . "', 'normal',0,0,0,0, NOW(), NOW())");
            $r = mysql_fetch_array(mysql_query("SELECT * FROM maps_score WHERE id='" . \mysql_insert_id() . "'"));

            $this->scores[] = new Score($r);
        }
    }

    public function addRound($team) {
        $score_teamA = 0;
        $score_teamB = 0;

        if ($team == "CT") {
            if ($this->getCurrentSide() == "ct") {
                $score_teamA++;
                $team = "a";
            } else {
                $score_teamB++;
                $team = "b";
            }
        } else {
            if ($this->getCurrentSide() == "t") {
                $team = "a";
                $score_teamA++;
            } else {
                $team = "b";
                $score_teamB++;
            }
        }

        end($this->scores);
        $score = current($this->scores);
        if ($score) {
            $score->addScore($score_teamA, $score_teamB, $this->nbMaxRound);
        } else {
            Logger::error("Can't find score");
        }

        $this->score1 += $score_teamA;
        $this->score2 += $score_teamB;

        @mysql_query("UPDATE `maps` SET score_1 = '" . $this->score1 . "', score_2 = '" . $this->score2 . "' WHERE id='" . $this->map_id . "'");

        return $team;
    }

    public function getCurrentScore() {
        end($this->scores);
        $score = current($this->scores);
        if ($score) {
            return $score;
        } else {
            Logger::error("Can't find score");
            return null;
        }
    }

    public function removeLastScore() {
        end($this->scores);
        $score = current($this->scores);
        if ($score) {
            if ($this->getStatus() == self::STATUS_SECOND_SIDE || $this->getStatus() == self::STATUS_OT_SECOND_SIDE) {
                $score->setScore1Side2(0);
                $score->setScore2Side2(0);
                $score->saveScore();
                $this->calculScores();
            } elseif ($this->getStatus() == self::STATUS_FIRST_SIDE || $this->getStatus() == self::STATUS_OT_FIRST_SIDE) {
                $score->setScore1Side1(0);
                $score->setScore2Side1(0);
                $score->saveScore();
                $this->calculScores();
            } else {
                Logger::error("Bad status to remove last score");
            }
        } else {
            Logger::error("Can't find score");
        }
    }

    public function calculScores() {
        $a = 0;
        $b = 0;
        foreach ($this->scores as $score) {
            $a += $score->getScore1Side1() + $score->getScore1Side2();
            $b += $score->getScore2Side1() + $score->getScore2Side2();
        }

        $this->setScore1($a);
        $this->setScore2($b);
        @mysql_query("UPDATE `maps` SET score_1 = '" . $this->score1 . "', score_2 = '" . $this->score2 . "' WHERE id='" . $this->map_id . "'");
    }

    public function addOvertime() {
        mysql_query("INSERT INTO maps_score (`map_id`,`type_score`,`score1_side1`,`score1_side2`,`score2_side1`,`score2_side2`, `created_at`,`updated_at`) VALUES ('" . $this->map_id . "', 'ot',0,0,0,0, NOW(), NOW())");
        $r = mysql_fetch_array(mysql_query("SELECT * FROM maps_score WHERE id='" . \mysql_insert_id() . "'"));

        $this->scores[] = new Score($r);

        $this->nb_ot++;
        mysql_query("UPDATE `maps` SET nb_ot = '" . $this->nb_ot . "' WHERE id = '" . $this->map_id . "'");
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
            case self::STATUS_MAP_ENDED:
                return "Finished";
        }
    }

    public function getNbRound() {
        return $this->getScore1() + $this->getScore2() + 1;
    }

    public function getMapId() {
        return $this->map_id;
    }

    public function setMapId($map_id) {
        if ($map_id == "")
            return;
        $this->map_id = $map_id;
    }

    public function getMapName() {
        return $this->map_name;
    }

    public function setMapName($map_name) {
        if ($map_name == "")
            return;
        $this->map_name = $map_name;
    }

    public function getScore1() {
        return $this->score1;
    }

    public function setScore1($score1) {
        if (!is_numeric($score1))
            $score1 = 0;
        $this->score1 = $score1;
    }

    public function getScore2() {
        return $this->score2;
    }

    public function setScore2($score2) {
        if (!is_numeric($score2))
            $score2 = 0;
        $this->score2 = $score2;
    }

    public function getCurrentSide() {
        return $this->current_side;
    }

    public function setCurrentSide($current_side, $save = false) {
        if ($current_side == "ct" || $current_side == "t") {
            $this->current_side = $current_side;

            if ($save) {
                mysql_query("UPDATE `maps` SET current_side='" . $current_side . "' WHERE id='" . $this->map_id . "'") or Logger::error("Error while updating current side " . mysql_error());
            }
        }
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($newStatus, $save = false) {
        $this->status = $newStatus;

        if ($save) {
            Logger::debug("Updating status to " . $this->getStatusText() . " in database");
            mysql_query("UPDATE `maps` SET status='" . $newStatus . "' WHERE id='" . $this->map_id . "'");
        }
    }

    public function getMapsFor() {
        return $this->maps_for;
    }

    public function setMapsFor($maps_for) {
        $this->maps_for = $maps_for;
    }

    public function getNeedKnifeRound() {
        return $this->need_knife_round;
    }

    public function setNeedKnifeRound($need) {
        $this->need_knife_round = $need;
    }

    public function getNbOt() {
        return $this->nb_ot;
    }

    public function setNbOt($nb_ot) {
        $this->nb_ot = $nb_ot;
    }

    public function getNbMaxRound() {
        return $this->nbMaxRound;
    }

    public function setNbMaxRound($nbMaxRound) {
        $this->nbMaxRound = $nbMaxRound;
    }
    
    public function getTvRecordFile() {
        return $this->tvRecordFile;
    }

    public function setTvRecordFile($tvRecordName) {
        $this->tvRecordFile = $tvRecordName;
    }


}

?>
