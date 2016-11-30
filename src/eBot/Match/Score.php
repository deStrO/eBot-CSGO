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

class Score {

    private $typeScore = "normal";
    private $score1Side1 = 0;
    private $score1Side2 = 0;
    private $score2Side1 = 0;
    private $score2Side2 = 0;
    private $id = 0;
	private $mysqli_link = null;

    public function __construct($mysqli_link, $scoreData) {
        Logger::debug("Creating score " . $scoreData["id"]);
		$this->mysqli_link = $mysqli_link;
        $this->setTypeScore($scoreData["type_score"]);
        $this->setScore1Side1($scoreData["score1_side1"]);
        $this->setScore1Side2($scoreData["score1_side2"]);
        $this->setScore2Side1($scoreData["score2_side1"]);
        $this->setScore2Side2($scoreData["score2_side2"]);
        $this->setId($scoreData["id"]);
    }

    public function getTypeScore() {
        return $this->typeScore;
    }

    public function setTypeScore($typeScore) {
        $this->typeScore = $typeScore;
    }

    public function getScore1Side1() {
        return $this->score1Side1;
    }

    public function setScore1Side1($score1Side1) {
        $this->score1Side1 = $score1Side1;
    }

    public function getScore1Side2() {
        return $this->score1Side2;
    }

    public function setScore1Side2($score1Side2) {
        $this->score1Side2 = $score1Side2;
    }

    public function getScore2Side1() {
        return $this->score2Side1;
    }

    public function setScore2Side1($score2Side1) {
        $this->score2Side1 = $score2Side1;
    }

    public function getScore2Side2() {
        return $this->score2Side2;
    }

    public function setScore2Side2($score2Side2) {
        $this->score2Side2 = $score2Side2;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function addScore($score_teamA, $score_teamB, $nbMaxRound) {
        if ($this->getScore1Side1() + $this->getScore2Side1() < $nbMaxRound) {
            $this->score1Side1 += $score_teamA;
            $this->score2Side1 += $score_teamB;
        } else {
            $this->score1Side2 += $score_teamA;
            $this->score2Side2 += $score_teamB;
        }
        
        $this->saveScore();
    }

    public function saveScore() {
        mysqli_query($this->mysqli_link, "UPDATE maps_score SET score1_side1='" . $this->score1Side1 . "',score1_side2='" . $this->score1Side2 . "',score2_side1='" . $this->score2Side1 . "',score2_side2='" . $this->score2Side2 . "' WHERE id='" . $this->id . "' ");
    }

}

?>
