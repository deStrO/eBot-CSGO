<?php
/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eTools\Task;

use \eTools\Utils\Logger;

class Task {

    const NOT_RUNNING = 0;
    const RUNNING = 1;
    const FINISH = 2;

    private $obj;
    private $functionName;
    private $time;
    private $status;
    private $param = array();

    public function __construct(&$obj, $functionName, $time) {
        Logger::debug("Creating task for " . get_class($obj) . "::taskExecute($functionName) at $time");
        $this->obj = $obj;
        $this->functionName = $functionName;
        $this->time = $time;
        $this->status = self::NOT_RUNNING;
        if (func_num_args() > 3) {
            for ($i = 3; $i < func_num_args(); $i++) {
                $this->param[] = func_get_arg($i);
            }
        }
    }

    public function run() {
        if ((microtime(true) > $this->time) && ($this->status == 0)) {
            $param = $this->param;
            array_unshift($param, $this->functionName);
            $this->status = self::RUNNING;
            call_user_func_array(array($this->obj, "taskExecute"), $param);
            $this->status = self::FINISH;
        }
    }

    public function getStatus() {
        return $this->status;
    }

    public function & getObjet() {
        return $this->obj;
    }

    public function getFunctionName() {
        return $this->functionName;
    }

    public function getParam() {
        return $this->param;
    }

    public function isThisObject($obj) {
        return $obj == $this->obj;
    }

    public function getTime() {
        return $this->time;
    }

}

?>
