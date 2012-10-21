<?php
/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eTools\Task;

use \eTools\Utils\Singleton;
use \eTools\Task\Task;
use \eTools\Utils\Logger;

class TaskManager extends Singleton {

    private $tasklist = array();

    public function __construct() {
        $this->tasklist = array();
    }

    /**
     *
     * @param Task $task
     * @param boolean $uniq
     */
    public function addTask(&$task, $uniq = false) {
        if (get_class($task) == "eTools\Task\Task") {
            Logger::debug("Try adding task for " . get_class($task->getObjet()));
            $ok = true;

            if ($uniq) {
                $paramTask = $task->getParam();

                foreach ($this->tasklist as $v) {
                    if ($v->getObjet() == $task->getObjet()) {
                        if ($v->getStatus() == Task::NOT_RUNNING) {
                            if ($v->getFunctionName() == $task->getFunctionName()) {
                                $param = $v->getParam();
                                if (count($param) == count($paramTask)) {
                                    $ok = false;
                                    foreach ($param as $k => $v) {
                                        if ($paramTask[$k] != $param[$k]) {
                                            $ok = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($ok) {
                Logger::debug("Added task " . get_class($task->getObjet()));
                array_push($this->tasklist, $task);
            }
        }
    }

    private $nbTask = 0;

    public function runTask() {
        foreach ($this->tasklist as $k => &$v) {
            if ($v->getStatus() == Task::NOT_RUNNING) {
                $v->run();
            }

            if ($v->getStatus() == Task::FINISH) {
                unset($this->tasklist[$k]);
            }
        }
    }

    public function removeAllTaskForObject(&$obj) {
        foreach ($this->tasklist as $k => &$v) {
            if ($v->isThisObject($obj) || ($v->getObjet() == $obj)) {
                unset($this->tasklist[$k]);
            }
        }
    }

}

?>
