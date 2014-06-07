<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eTools\Utils;

use \eTools\Utils\Singleton;

class Logger extends Singleton {

    private $log_enabled = false;
    private $log_path;
    private $log_path_admin;
    private $name = "";

    const DEBUG = 1;
    const LOG = 2;
    const ERROR = 3;

    public static $level = Logger::LOG;

    public function __construct() {
        $options = getopt("", array("logger::"));  
        $file = APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "logger.ini";
        if (@$options['logger']) {
            if (file_exists(APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . $options['logger'])) {
                $file = APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . $options['logger'];
            }
        }
                
        if (file_exists($file)) {
            $config = parse_ini_file($file);
            $this->log_path = $config["LOG_PATH"];
            $this->log_path_admin = $config["LOG_PATH_ADMIN"];
            $this->log_enabled = (boolean) $config["LOG"];

            if (!file_exists($this->log_path)) {
                @mkdir($this->log_path);
            }

            if (!file_exists($this->log_path_admin)) {
                @mkdir($this->log_path_admin);
            }
        }
    }

    public function getLogEnabled() {
        return $this->log_enabled;
    }

    public function setLogEnabled($log_enabled) {
        $this->log_enabled = $log_enabled;
    }

    public function getLogPath() {
        return $this->log_path;
    }

    public function setLogPath($log_path) {
        $this->log_path = $log_path;
    }

    public function getLogPathAdmin() {
        return $this->log_path_admin;
    }

    public function setLogPathAdmin($log_path_admin) {
        $this->log_path_admin = $log_path_admin;
    }

    public static function logToHtmlFile($content, $file, $onlyAdmin) {
        
    }

    public static function log($content) {
        if (!self::getInstance()->getLogEnabled())
            return;

        if (self::$level > Logger::LOG)
            return;

        $name = "";
        if (self::getInstance()->getName() != "") {
            $name = " [" . self::getInstance()->getName() . "] ";
        }

        $d = debug_backtrace();
        if (@$d[1]) {
            if ($d[1]['class']) {
                echo date('Y-m-d H:i:s') . $name . " - LOG    [" . $d[1]['class'] . "] $content\r\n";
            } else {
                echo date('Y-m-d H:i:s') . $name . " - LOG    [" . $d[1]['function'] . "] $content\r\n";
            }
        }
        else
            echo date('Y-m-d H:i:s') . $name . " - LOG    " . $content . "\r\n";
    }

    public static function error($content) {
        if (!self::getInstance()->getLogEnabled())
            return;

        if (self::$level > Logger::ERROR)
            return;

        $name = "";
        if (self::getInstance()->getName() != "") {
            $name = " [" . self::getInstance()->getName() . "] ";
        }

        $d = debug_backtrace();
        if (@$d[1]) {
            if ($d[1]['class']) {
                echo date('Y-m-d H:i:s') . $name . " - ERROR  [" . $d[1]['class'] . "] $content\r\n";
            } else {
                echo date('Y-m-d H:i:s') . $name . " - ERROR  [" . $d[1]['function'] . "] $content\r\n";
            }
        }
        else
            echo date('Y-m-d H:i:s') . $name . " - ERROR  " . $content . "\r\n";
    }

    public static function debug($content) {
        if (!self::getInstance()->getLogEnabled())
            return;

        if (self::$level > Logger::DEBUG)
            return;

        $name = "";
        if (self::getInstance()->getName() != "") {
            $name = " [" . self::getInstance()->getName() . "] ";
        }

        $d = debug_backtrace();
        if (@$d[1]) {
            if ($d[1]['class']) {
                if (strpos($d[1]['class'], "Task") === false)
                    echo date('Y-m-d H:i:s') . $name . " - DEBUG  [" . $d[1]['class'] . "] $content\r\n";
            } else {
                echo date('Y-m-d H:i:s') . $name . " - DEBUG  [" . $d[1]['function'] . "] $content\r\n";
            }
        }
        else
            echo date('Y-m-d H:i:s') . $name . " - DEBUG  " . $content . "\r\n";
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

}

?>
