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
class Config extends Singleton
{

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
    private $advertising = array();
    private $maps;
    private $workshop;
    private $lo3_method;
    private $ko3_method;
    private $demo_download;
    private $pause_method;
    private $config_stop_disabled = false;
    private $config_knife_method = false;
    private $delay_ready = false;
    private $damage_report = true;
    private $remember_recordmsg = false;
    private $external_log_ip = "";
    private $node_startup_method = "node";

    public function __construct()
    {
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

            $this->maps = $config["MAP"];
            $this->workshop = $config["WORKSHOP"];

            $this->lo3_method = $config["LO3_METHOD"];
            $this->ko3_method = $config["KO3_METHOD"];

            $this->demo_download = (bool)$config["DEMO_DOWNLOAD"];
            $this->external_log_ip = $config['EXTERNAL_LOG_IP'];
            if (isset($config['NODE_STARTUP_METHOD']))
                $this->node_startup_method = $config['NODE_STARTUP_METHOD'];

            $this->pause_method = $config["PAUSE_METHOD"];

            $this->config_stop_disabled = (bool)$config['COMMAND_STOP_DISABLED'];
            $this->config_knife_method = ($config['RECORD_METHOD'] == "knifestart") ? "knifestart" : "matchstart";
            $this->delay_ready = (bool)$config['DELAY_READY'];

            if (isset($config['DAMAGE_REPORT']) && is_bool((bool)$config['DAMAGE_REPORT']))
                $this->damage_report = (bool)$config['DAMAGE_REPORT'];

            if (isset($config['REMIND_RECORD']) && is_bool((bool)$config['REMIND_RECORD']))
                $this->remember_recordmsg = (bool)$config['REMIND_RECORD'];

            Logger::debug("Configuration loaded");
        }
    }

    public function scanAdvertising()
    {
        unset($this->advertising);
        $q = \mysql_query("SELECT a.`season_id`, a.`message`, s.`name` FROM `advertising` a LEFT JOIN `seasons` s ON a.`season_id` = s.`id` WHERE a.`active` = 1");
        while ($row = mysql_fetch_array($q, MYSQL_ASSOC)) {
            $this->advertising['message'][] = $row['message'];
            if ($row['season_id'] == null) {
                $row['season_id'] = 0;
                $row['name'] = "General";
            }
            $this->advertising['season_id'][] = intval($row['season_id']);
            $this->advertising['season_name'][] = $row['name'];
        }
        array_multisort($this->advertising['season_id'], SORT_ASC, $this->advertising['season_name'], $this->advertising['message']);
    }

    public function printConfig()
    {
        Logger::log("MySQL: " . $this->mysql_ip . ":" . $this->mysql_port . " " . $this->mysql_user . ":" . \str_repeat("*", \strlen($this->mysql_pass)) . "@" . $this->mysql_base);
        Logger::log("Socket: " . $this->bot_ip . ":" . $this->bot_port);
        Logger::log("Advertising by Season:");
        for ($i = 0; $i < count($this->advertising['message']); $i++) {
            Logger::log("-> " . $this->advertising['season_name'][$i] . ": " . $this->advertising['message'][$i]);
        }
        Logger::log("Maps:");
        foreach ($this->maps as $map) {
            Logger::log("-> " . $map);
        }
    }

    public function getRememberRecordmsgConfig()
    {
        return $this->remember_recordmsg;
    }

    public function setRememberRecordmsgConfig($remember_recordmsg)
    {
        $this->remember_recordmsg = $remember_recordmsg;
    }

    public function getDamageReportConfig()
    {
        return $this->damage_report;
    }

    public function setDamageReportConfig($damage_report)
    {
        $this->damage_report = $damage_report;
    }

    public function getMysql_ip()
    {
        return $this->mysql_ip;
    }

    public function setMysql_ip($mysql_ip)
    {
        $this->mysql_ip = $mysql_ip;
    }

    public function getMysql_port()
    {
        return $this->mysql_port;
    }

    public function setMysql_port($mysql_port)
    {
        $this->mysql_port = $mysql_port;
    }

    public function getMysql_user()
    {
        return $this->mysql_user;
    }

    public function setMysql_user($mysql_user)
    {
        $this->mysql_user = $mysql_user;
    }

    public function getMysql_pass()
    {
        return $this->mysql_pass;
    }

    public function setMysql_pass($mysql_pass)
    {
        $this->mysql_pass = $mysql_pass;
    }

    public function getMysql_base()
    {
        return $this->mysql_base;
    }

    public function setMysql_base($mysql_base)
    {
        $this->mysql_base = $mysql_base;
    }

    public function getBot_ip()
    {
        return $this->bot_ip;
    }

    public function setBot_ip($bot_ip)
    {
        $this->bot_ip = $bot_ip;
    }

    public function getBot_port()
    {
        return $this->bot_port;
    }

    public function setBot_port($bot_port)
    {
        $this->bot_port = $bot_port;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    public function getRecord_name()
    {
        return $this->record_name;
    }

    public function setRecord_name($record_name)
    {
        $this->record_name = $record_name;
    }

    public function getDelay_busy_server()
    {
        return $this->delay_busy_server;
    }

    public function setDelay_busy_server($delay_busy_server)
    {
        $this->delay_busy_server = $delay_busy_server;
    }

    public function getNb_max_matchs()
    {
        return $this->nb_max_matchs;
    }

    public function setNb_max_matchs($nb_max_matchs)
    {
        $this->nb_max_matchs = $nb_max_matchs;
    }

    public function getNbRoundOvertime()
    {
        return $this->ot_rounds;
    }

    public function setNbRoundOvertime($ot_rounds)
    {
        $this->ot_rounds = $ot_rounds;
    }

    public function getPerf_link()
    {
        return $this->perf_link;
    }

    public function setPerf_link($perf_link)
    {
        $this->perf_link = $perf_link;
    }

    public function getPerf_link_on_update()
    {
        return $this->perf_link_on_update;
    }

    public function setPerf_link_on_update($perf_link_on_update)
    {
        $this->perf_link_on_update = $perf_link_on_update;
    }

    public function getAdvertising($seasonID)
    {
        for ($i = 0; $i < count($this->advertising['season_id']); $i++) {
            if (($this->advertising['season_id'][$i] == $seasonID) || ($this->advertising['season_id'][$i] == 0)) {
                $output['season_id'][] = $this->advertising['season_id'][$i];
                $output['season_name'][] = $this->advertising['season_name'][$i];
                $output['message'][] = $this->advertising['message'][$i];
            }
        }
        return $output;
    }

    public function setAdvertising($pubs)
    {
        $this->advertising = $pubs;
    }

    public function getMaps()
    {
        return $this->maps;
    }

    public function setMaps($maps)
    {
        $this->maps = $maps;
    }

    public function getWorkshop()
    {
        return $this->workshop;
    }

    public function getWorkshopByMap($mapname)
    {
        if (!empty($this->workshop[$mapname]))
            return $this->workshop[$mapname];
        else return false;
    }

    public function setWorkshop($workshop)
    {
        $this->workshop = $workshop;
    }

    public function getLo3Method()
    {
        return $this->lo3_method;
    }

    public function setLo3Method($lo3_method)
    {
        $this->lo3_method = $lo3_method;
    }

    public function getPauseMethod()
    {
        return $this->pause_method;
    }

    public function setPauseMethod($pause_method)
    {
        $this->pause_method = $pause_method;
    }

    public function getKo3Method()
    {
        return $this->ko3_method;
    }

    public function setKo3Method($ko3_method)
    {
        $this->ko3_method = $ko3_method;
    }

    public function getDemoDownload()
    {
        return $this->demo_download;
    }

    public function setDemoDownload($demo_download)
    {
        $this->demo_download = $demo_download;
    }

    public function getCryptKey()
    {
        return $this->crypt_key;
    }

    public function getConfigStopDisabled()
    {
        return $this->config_stop_disabled;
    }

    public function setConfigStopDisabled($config_stop_disabled)
    {
        $this->config_stop_disabled = $config_stop_disabled;
    }

    public function getConfigKnifeMethod()
    {
        return $this->config_knife_method;
    }

    public function setConfigKnifeMethod($config_knife_method)
    {
        $this->config_knife_method = $config_knife_method;
    }

    public function getDelayReady()
    {
        return $this->delay_ready;
    }

    public function setDelayReady($delay_ready)
    {
        $this->delay_ready = $delay_ready;
    }

    /**
     * @return string
     */
    public function getExternalLogIp()
    {
        return $this->external_log_ip;
    }

    /**
     * @param string $external_log_ip
     */
    public function setExternalLogIp($external_log_ip)
    {
        $this->external_log_ip = $external_log_ip;
    }


    public function getLogAddressIp()
    {
        if ($this->external_log_ip != "") {
            return $this->external_log_ip;
        }

        return $this->bot_ip;
    }

    /**
     * @return string
     */
    public function getNodeStartupMethod()
    {
        return $this->node_startup_method;
    }

    /**
     * @param string $node_startup_method
     */
    public function setNodeStartupMethod($node_startup_method)
    {
        $this->node_startup_method = $node_startup_method;
    }

}

?>
