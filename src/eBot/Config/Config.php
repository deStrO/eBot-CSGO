<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Config;

use \eTools\Utils\Singleton;
use \eTools\Utils\Logger;

/**
 * @method Config getInstance() Description
 */
class Config extends Singleton {

    private $mysql_ip;
    private $mysql_port;
    private $mysql_user;
    private $mysql_pass;
    private $mysql_base;
    private $bot_ip;
    private $bot_port;
    private $messages = array();
    private $record_name = "ebot";
    private $delay_busy_server = 90;
    private $nb_max_matchs = 0;
    private $ot_rounds;
    private $pubs;
    private $maps;
    private $lo3_method;
    private $ko3_method;
    private $pause_method;
    private $config_stop_disabled = false;
    private $config_knife_method = false;

    public function __construct() {
        Logger::debug("Loading " . APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini");
        if (file_exists(APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini")) {
            $config = parse_ini_file(APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini");

            $this->mysql_ip = $config["MYSQL_IP"];
            $this->mysql_port = $config["MYSQL_PORT"];
            $this->mysql_user = $config["MYSQL_USER"];
            $this->mysql_pass = $config["MYSQL_PASS"];
            $this->mysql_base = $config["MYSQL_BASE"];

            $this->bot_ip = $config["BOT_IP"];
            $this->bot_port = $config["BOT_PORT"];

            $this->delay_busy_server = $config["DELAY_BUSY_SERVER"];
            $this->ot_rounds = $config["OT_ROUNDS"];

            $this->pubs = $config["PUB"];

            $this->maps = $config["MAP"];

            $this->lo3_method = $config["LO3_METHOD"];
            $this->ko3_method = $config["KO3_METHOD"];

            $this->pause_method = $config["PAUSE_METHOD"];

            $this->config_stop_disabled = (bool) $config['COMMAND_STOP_DISABLED'];
            $this->config_knife_method = ($config['RECORD_METHOD'] == "knifestart") ? "knifestart" : "matchstart";

            Logger::debug("Configuration loaded");
        }
    }

    public function printConfig() {
        Logger::log("MySQL: " . $this->mysql_ip . ":" . $this->mysql_port . " " . $this->mysql_user . ":" . \str_repeat("*", \strlen($this->mysql_pass)) . "@" . $this->mysql_base);
        Logger::log("Socket: " . $this->bot_ip . ":" . $this->bot_port);
        Logger::log("OverTime rounds: " . $this->ot_rounds);
        Logger::log("Pub's set:");
        foreach ($this->pubs as $pub) {
            Logger::log("-> ".$pub);
        }
        Logger::log("Maps set:");
        foreach ($this->maps as $map) {
            Logger::log("-> ".$map);
        }
    }

    public function getMysql_ip() {
        return $this->mysql_ip;
    }

    public function setMysql_ip($mysql_ip) {
        $this->mysql_ip = $mysql_ip;
    }

    public function getMysql_port() {
        return $this->mysql_port;
    }

    public function setMysql_port($mysql_port) {
        $this->mysql_port = $mysql_port;
    }

    public function getMysql_user() {
        return $this->mysql_user;
    }

    public function setMysql_user($mysql_user) {
        $this->mysql_user = $mysql_user;
    }

    public function getMysql_pass() {
        return $this->mysql_pass;
    }

    public function setMysql_pass($mysql_pass) {
        $this->mysql_pass = $mysql_pass;
    }

    public function getMysql_base() {
        return $this->mysql_base;
    }

    public function setMysql_base($mysql_base) {
        $this->mysql_base = $mysql_base;
    }

    public function getBot_ip() {
        return $this->bot_ip;
    }

    public function setBot_ip($bot_ip) {
        $this->bot_ip = $bot_ip;
    }

    public function getBot_port() {
        return $this->bot_port;
    }

    public function setBot_port($bot_port) {
        $this->bot_port = $bot_port;
    }

    public function getMessages() {
        return $this->messages;
    }

    public function setMessages($messages) {
        $this->messages = $messages;
    }

    public function getRecord_name() {
        return $this->record_name;
    }

    public function setRecord_name($record_name) {
        $this->record_name = $record_name;
    }

    public function getDelay_busy_server() {
        return $this->delay_busy_server;
    }

    public function setDelay_busy_server($delay_busy_server) {
        $this->delay_busy_server = $delay_busy_server;
    }

    public function getNb_max_matchs() {
        return $this->nb_max_matchs;
    }

    public function setNb_max_matchs($nb_max_matchs) {
        $this->nb_max_matchs = $nb_max_matchs;
    }

    public function getNbRoundOvertime() {
        return $this->ot_rounds;
    }

    public function setNbRoundOvertime($ot_rounds) {
        $this->ot_rounds = $ot_rounds;
    }

    public function getPerf_link() {
        return $this->perf_link;
    }

    public function setPerf_link($perf_link) {
        $this->perf_link = $perf_link;
    }

    public function getPerf_link_on_update() {
        return $this->perf_link_on_update;
    }

    public function setPerf_link_on_update($perf_link_on_update) {
        $this->perf_link_on_update = $perf_link_on_update;
    }

    public function getPubs() {
        return $this->pubs;
    }

    public function setPubs($pubs) {
        $this->pubs = $pubs;
    }

    public function getMaps() {
        return $this->maps;
    }

    public function setMaps($maps) {
        $this->maps = $maps;
    }

    public function getLo3Method() {
        return $this->lo3_method;
    }

    public function setLo3Method($lo3_method) {
        $this->lo3_method = $lo3_method;
    }

    public function getPauseMethod() {
        return $this->pause_method;
    }

    public function setPauseMethod($pause_method) {
        $this->pause_method = $pause_method;
    }

    public function getKo3Method() {
        return $this->ko3_method;
    }

    public function setKo3Method($ko3_method) {
        $this->ko3_method = $ko3_method;
    }

    public function getCryptKey() {
        return $this->crypt_key;
    }

    public function getConfigStopDisabled() {
        return $this->config_stop_disabled;
    }

    public function setConfigStopDisabled($config_stop_disabled) {
        $this->config_stop_disabled = $config_stop_disabled;
    }

    public function getConfigKnifeMethod() {
        return $this->config_knife_method;
    }

    public function setConfigKnifeMethod($config_knife_method) {
        $this->config_knife_method = $config_knife_method;
    }




}

?>
