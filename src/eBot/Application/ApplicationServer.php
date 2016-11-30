<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Application;

use eTools\Utils\Logger;
use eTools\Application\AbstractApplication;
use eTools\Socket\UDPSocket as Socket;
use eBot\Manager\MessageManager;
use eBot\Manager\PluginsManager;
use eBot\Manager\MatchManagerServer;
use eBot\Config\Config;

class ApplicationServer extends AbstractApplication {

    const VERSION = "3.0";

    private $socket = null;
    private $websocket = null;
    private $clientsConnected = false;
    public $instance = array();
    private $mysqli_link = null;

    public function run() {
        // Loading Logger instance
        Logger::getInstance();
        Logger::getInstance()->setName("#0");
        Logger::log($this->getName());

        // Loading eBot configuration
        Logger::log("Loading config");
        Config::getInstance()->printConfig();

        // Initializing database
		$this->mysqli_link = $this->initDatabase();

        // Registring components
        Logger::log("Registering MatchManagerServer");
		MatchManagerServer::getInstance();

        Logger::log("Registering Messages");
        MessageManager::createFromConfigFile();

        Logger::log("Registering PluginsManager");
        PluginsManager::getInstance();

        Logger::log("Spawning instance");
        $config = parse_ini_file(APP_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.server.ini");
        $instance = 1;
        if (is_numeric($config['NUMBER'])) {
            $instance = $config['NUMBER'];
        }

        for ($i = 1; $i <= $instance; $i++) {
            $descriptorspec = array(
                0 => STDIN,
                1 => STDOUT,
                2 => STDOUT
            );
            $process = proc_open(PHP_BINDIR . '/php '.EBOT_DIRECTORY.'/bootstrap_client.php ' . $i, $descriptorspec, $pipes);
            $status = proc_get_status($process);
            $this->instance[] = $process;
            Logger::log("Spawned instance " . $status['pid']);
        }

        // Starting application
        Logger::log("Starting eBot Application");

        try {
            $this->socket = new Socket(Config::getInstance()->getBot_ip(), Config::getInstance()->getBot_port());
        } catch (Exception $ex) {
            Logger::error("Unable to bind socket");
            die();
        }

        try {
            $this->websocket['match'] = new \WebSocket("ws://" . \eBot\Config\Config::getInstance()->getBot_ip() . ":" . (\eBot\Config\Config::getInstance()->getBot_port()) . "/match");
            $this->websocket['match']->open();
            $this->websocket['rcon'] = new \WebSocket("ws://" . \eBot\Config\Config::getInstance()->getBot_ip() . ":" . (\eBot\Config\Config::getInstance()->getBot_port()) . "/rcon");
            $this->websocket['rcon']->open();
            $this->websocket['logger'] = new \WebSocket("ws://" . \eBot\Config\Config::getInstance()->getBot_ip() . ":" . (\eBot\Config\Config::getInstance()->getBot_port()) . "/logger");
            $this->websocket['logger']->open();
            $this->websocket['livemap'] = new \WebSocket("ws://" . \eBot\Config\Config::getInstance()->getBot_ip() . ":" . (\eBot\Config\Config::getInstance()->getBot_port()) . "/livemap");
            $this->websocket['livemap']->open();
            $this->websocket['aliveCheck'] = new \WebSocket("ws://" . \eBot\Config\Config::getInstance()->getBot_ip() . ":" . (\eBot\Config\Config::getInstance()->getBot_port()) . "/alive");
            $this->websocket['aliveCheck']->open();
        } catch (Exception $ex) {
            Logger::error("Unable to create Websocket.");
            die();
        }

        PluginsManager::getInstance()->startAll();

        $time = time();
        while (true) {
            $data = $this->socket->recvfrom($ip);
            if ($data) {
                if (!preg_match("/L+\s+\d+\/\d+\/\d+/", $data)) {
                    if ($data == '__true__') {
                        $this->clientsConnected = true;
                        for ($i = 1; $i <= count($this->instance); $i++) {
                            $this->socket->sendto($data, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $i);
                        }
                    } elseif ($data == '__false__') {
                        $this->clientsConnected = false;
                        for ($i = 1; $i <= count($this->instance); $i++) {
                            $this->socket->sendto($data, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $i);
                        }
                    } elseif ($data == '__aliveCheck__') {
                        $this->websocket['aliveCheck']->sendData('__isAlive__');
                        for ($i = 1; $i <= count($this->instance); $i++) {
                            $this->socket->sendto($data, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $i);
                        }
                    } elseif (preg_match("!^removeMatch (?<id>\d+)$!", $data, $preg)) {
                        Logger::log("Removing ".$preg['id']);
                        MatchManagerServer::getInstance()->removeMatch($preg['id']);
                    } else {
                        $origData = $data;
                        $data = json_decode($data, true);
                        $authkey = \eBot\Manager\MatchManagerServer::getInstance()->getAuthkey($data[1]);
                        $text = \eTools\Utils\Encryption::decrypt($data[0], $authkey, 256);
                        if ($text) {
                            if (preg_match("!^(?<id>\d+) stopNoRs (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManagerServer::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $this->socket->sendto($origData, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $match['i']);
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) stop (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManagerServer::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $this->socket->sendto($origData, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $match['i']);
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) executeCommand (?<ip>\d+\.\d+\.\d+\.\d+\:\d+) (?<command>.*)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManagerServer::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $this->socket->sendto($origData, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $match['i']);
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) passknife (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManagerServer::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $this->socket->sendto($origData, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $match['i']);
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) forceknife (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManagerServer::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $this->socket->sendto($origData, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $match['i']);
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) forceknifeend (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManagerServer::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $this->socket->sendto($origData, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $match['i']);
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) forcestart (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManagerServer::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $this->socket->sendto($origData, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $match['i']);
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) stopback (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManagerServer::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $this->socket->sendto($origData, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $match['i']);
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) pauseunpause (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManagerServer::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $this->socket->sendto($origData, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $match['i']);
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) fixsides (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManagerServer::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $this->socket->sendto($origData, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $match['i']);
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) streamerready (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManagerServer::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $this->socket->sendto($origData, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $match['i']);
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) goBackRounds (?<ip>\d+\.\d+\.\d+\.\d+\:\d+) (?<round>\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManagerServer::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $this->socket->sendto($origData, \eBot\Config\Config::getInstance()->getBot_ip(), \eBot\Config\Config::getInstance()->getBot_port() + $match['i']);
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } else {
                                Logger::error($text . " not managed");
                            }
                        }
                    }
                }
            }
            if ($time + 5 < time()) {
                $time = time();
                $this->websocket['match']->send(json_encode(array("message" => "ping")));
                $this->websocket['logger']->send(json_encode(array("message" => "ping")));
                $this->websocket['rcon']->send(json_encode(array("message" => "ping")));
                $this->websocket['livemap']->send(json_encode(array("message" => "ping")));
                $this->websocket['aliveCheck']->send(json_encode(array("message" => "ping")));
            }

            \eTools\Task\TaskManager::getInstance()->runTask();
        }
    }

    private function initDatabase() {
        $conn = @\mysqli_connect(Config::getInstance()->getMysql_ip(), Config::getInstance()->getMysql_user(), Config::getInstance()->getMysql_pass());
        if (!$conn) {
            Logger::error("Can't login into database " . Config::getInstance()->getMysql_user() . "@" . Config::getInstance()->getMysql_ip());
            exit(1);
        }

        if (!\mysqli_select_db($conn, Config::getInstance()->getMysql_base())) {
            Logger::error("Can't select database " . Config::getInstance()->getMysql_base());
            exit(1);
        }

        return $conn;
    }

    public function getName() {
        return "eBot CS:Go version " . $this->getVersion();
    }

    public function getVersion() {
        return self::VERSION;
    }

    public function getSocket() {
        return $this->socket;
    }

    public function getWebSocket($room) {
        return $this->websocket[$room];
    }

}

?>
