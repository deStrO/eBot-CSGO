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
use eTools\Socket\websocket\client\WebSocket as WebSocket;
use eBot\Manager\MessageManager;
use eBot\Manager\PluginsManager;
use eBot\Manager\MatchManager;
use eBot\Config\Config;

class Application extends AbstractApplication {

    const VERSION = "3.0";

    private $socket = null;
    private $websocket = null;
    private $clientsConnected = false;

    public function run() {
        // Loading Logger instance
        Logger::getInstance();
        Logger::log($this->getName());

        // Loading eBot configuration
        Logger::log("Loading config");
        Config::getInstance()->printConfig();

        // Initializing database
        $this->initDatabase();

        // Registring components
        Logger::log("Registering MatchManager");
        MatchManager::getInstance();

        Logger::log("Registering Messages");
        MessageManager::createFromConfigFile();

        Logger::log("Registering PluginsManager");
        PluginsManager::getInstance();

        // Starting application
        Logger::log("Starting eBot Application");

        try {
            $this->socket = new Socket(Config::getInstance()->getBot_ip(), Config::getInstance()->getBot_port());
        } catch (Exception $ex) {
            Logger::error("Unable to bind socket");
            die();
        }

        try {
            $this->websocket['match'] = new WebSocket;
            $this->websocket['match']->connect(Config::getInstance()->getBot_ip(), (Config::getInstance()->getBot_port()), '/match');
            $this->websocket['rcon'] = new WebSocket;
            $this->websocket['rcon']->connect(Config::getInstance()->getBot_ip(), (Config::getInstance()->getBot_port()), '/rcon');
            $this->websocket['logger'] = new WebSocket;
            $this->websocket['logger']->connect(Config::getInstance()->getBot_ip(), (Config::getInstance()->getBot_port()), '/logger');
            $this->websocket['livemap'] = new WebSocket;
            $this->websocket['livemap']->connect(Config::getInstance()->getBot_ip(), (Config::getInstance()->getBot_port()), '/livemap');
            $this->websocket['aliveCheck'] = new WebSocket;
            $this->websocket['aliveCheck']->connect(Config::getInstance()->getBot_ip(), (Config::getInstance()->getBot_port()), '/alive');
        } catch (Exception $ex) {
            Logger::error("Unable to create Websocket.");
            die();
        }

        PluginsManager::getInstance()->startAll();

        while (true) {
            $data = $this->socket->recvfrom($ip);
            if ($data) {
                if (!preg_match("/L+\s+\d+\/\d+\/\d+/", $data)) {
                    if ($data == '__true__') {
                        $this->clientsConnected = true;
                    } elseif ($data == '__false__') {
                        $this->clientsConnected = false;
                    } elseif ($data == '__aliveCheck__') {
                        $this->websocket['aliveCheck']->sendData('__isAlive__');
                    } else {
                        $text = \eTools\Utils\Encryption::decrypt($data, utf8_encode(Config::getInstance()->getCryptKey()), 256);
                        if ($text) {
                            if (preg_match("!^(?<id>\d+) stopNoRs (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManager::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $reply = $match->adminStopNoRs();
                                    if ($reply) {
                                        $send = json_encode(array('button', 'stop', $preg["id"]));
                                        $this->websocket['match']->sendData($send);
                                    }
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) stop (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManager::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $reply = $match->adminStop();
                                    if ($reply) {
                                        $send = json_encode(array('button', 'stop', $preg["id"]));
                                        $this->websocket['match']->sendData($send);
                                    }
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) executeCommand (?<ip>\d+\.\d+\.\d+\.\d+\:\d+) (?<command>.*)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManager::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $reply = $match->adminExecuteCommand($preg["command"]);
                                    if ($reply) {
                                        $send = json_encode(array($preg["id"], $reply));
                                        $this->websocket['rcon']->sendData($send);
                                    }
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) passknife (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManager::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $reply = $match->adminPassKnife();
                                    if ($reply) {
                                        $send = json_encode(array('button', $match->getStatus(), $preg["id"]));
                                        $this->websocket['match']->sendData($send);
                                    }
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) forceknife (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManager::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $reply = $match->adminForceKnife();
                                    if ($reply) {
                                        $send = json_encode(array('button', $match->getStatus(), $preg["id"]));
                                        $this->websocket['match']->sendData($send);
                                    }
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) forceknifeend (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManager::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $reply = $match->adminForceKnifeEnd();
                                    if ($reply) {
                                        $send = json_encode(array('button', $match->getStatus(), $preg["id"]));
                                        $this->websocket['match']->sendData($send);
                                    }
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) forcestart (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManager::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $reply = $match->adminForceStart();
                                    if ($reply) {
                                        $send = json_encode(array('button', $match->getStatus(), $preg["id"]));
                                        $this->websocket['match']->sendData($send);
                                    }
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) stopback (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManager::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $reply = $match->adminStopBack();
                                    if ($reply) {
                                        $send = json_encode(array('button', $match->getStatus(), $preg["id"]));
                                        $this->websocket['match']->sendData($send);
                                    }
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) pauseunpause (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManager::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $reply = $match->adminPauseUnpause();
                                    if ($reply) {
                                        $send = json_encode(array('button', $match->getStatus(), $preg["id"]));
                                        $this->websocket['match']->sendData($send);
                                    }
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) fixsides (?<ip>\d+\.\d+\.\d+\.\d+\:\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManager::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $reply = $match->adminFixSides();
                                    if ($reply) {
                                        $send = json_encode(array('button', $match->getStatus(), $preg["id"]));
                                        $this->websocket['match']->sendData($send);
                                    }
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } elseif (preg_match("!^(?<id>\d+) goBackRounds (?<ip>\d+\.\d+\.\d+\.\d+\:\d+) (?<round>\d+)$!", $text, $preg)) {
                                $match = \eBot\Manager\MatchManager::getInstance()->getMatch($preg["ip"]);
                                if ($match) {
                                    $reply = $match->adminGoBackRounds();
                                    if ($reply) {
                                        $send = json_encode(array('button', $match->getStatus(), $preg["id"]));
                                        $this->websocket['match']->sendData($send);
                                    }
                                } else {
                                    Logger::error($preg["ip"] . " is not managed !");
                                }
                            } else {
                                Logger::error($text . " not managed");
                            }
                        }
                    }
                } else {
                    $line = substr($data, 7);

                    if (\eBot\Manager\MatchManager::getInstance()->getMatch($ip)) {
                        file_put_contents(APP_ROOT . "/logs/$ip", $line, FILE_APPEND);
                        $line = trim(substr($line, 23));
                        \eBot\Manager\MatchManager::getInstance()->getMatch($ip)->processMessage($line);
                        if ($this->clientsConnected) {
                            $line = substr($data, 7, strlen($data)-8);
                            $send = json_encode(array(\eBot\Manager\MatchManager::getInstance()->getMatch($ip)->getMatchId(), $line));
                            $this->websocket['logger']->sendData($send);
                        }
                    }
                }
            }

            \eBot\Manager\MatchManager::getInstance()->sendPub();
            \eTools\Task\TaskManager::getInstance()->runTask();
        }
    }

    private function initDatabase() {
        $conn = @\mysql_connect(Config::getInstance()->getMysql_ip(), Config::getInstance()->getMysql_user(), Config::getInstance()->getMysql_pass());
        if (!$conn) {
            Logger::error("Can't login into database " . Config::getInstance()->getMysql_user() . "@" . Config::getInstance()->getMysql_ip());
            exit(1);
        }

        if (!\mysql_select_db(Config::getInstance()->getMysql_base(), $conn)) {
            Logger::error("Can't select database " . Config::getInstance()->getMysql_base());
            exit(1);
        }
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
