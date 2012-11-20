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
use eBot\Manager\MatchManager;
use eBot\Config\Config;

class Application extends AbstractApplication {

    const VERSION = "3.0";

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
        Logger::log("Registring MatchManager");
        MatchManager::getInstance();

        Logger::log("Registring Messages");
        MessageManager::createFromConfigFile();

        Logger::log("Registring PluginsManager");
        PluginsManager::getInstance();

        // Starting application
        Logger::log("Starting eBot Application");

        try {
            $socket = new Socket(Config::getInstance()->getBot_ip(), Config::getInstance()->getBot_port());
        } catch (Exception $ex) {
            Logger::error("Unable to bind socket");
            die();
        }

        PluginsManager::getInstance()->startAll();

        while (true) {
            $data = $socket->recvfrom($ip);

            if ($data) {
                if (preg_match("!^\xFE\xFE\xFE\xFE(?<text>.*)\xFD\xFD\xFD\xFD$!", $data, $match)) {
                    $text = \eTools\Utils\Encryption::getInstance()->decrypt($match["text"]);
                    if ($text) {
                        if (preg_match("!^stopNoRs (?<ip>.*)$!", $text, $match)) {
                            $match = \eBot\Manager\MatchManager::getInstance()->getMatch($match["ip"]);
                            if ($match) {
                                $match->adminStopNoRs();
                            } else {
                                Logger::error($match["ip"] . " is not managed !");
                            }
                        } elseif (preg_match("!^stop (?<ip>.*)$!", $text, $match)) {
                            $match = \eBot\Manager\MatchManager::getInstance()->getMatch($match["ip"]);
                            if ($match) {
                                $match->adminStop();
                            } else {
                                Logger::error($match["ip"] . " is not managed !");
                            }
                        } elseif (preg_match("!^passknife (?<ip>.*)$!", $text, $match)) {
                            $match = \eBot\Manager\MatchManager::getInstance()->getMatch($match["ip"]);
                            if ($match) {
                                $match->adminPassKnife();
                            } else {
                                Logger::error($match["ip"] . " is not managed !");
                            }
                        } elseif (preg_match("!^forceknife (?<ip>.*)$!", $text, $match)) {
                            $match = \eBot\Manager\MatchManager::getInstance()->getMatch($match["ip"]);
                            if ($match) {
                                $match->adminForceKnife();
                            } else {
                                Logger::error($match["ip"] . " is not managed !");
                            }
                        } elseif (preg_match("!^forceknifeend (?<ip>.*)$!", $text, $match)) {
                            $match = \eBot\Manager\MatchManager::getInstance()->getMatch($match["ip"]);
                            if ($match) {
                                $match->adminForceKnifeEnd();
                            } else {
                                Logger::error($match["ip"] . " is not managed !");
                            }
                        } elseif (preg_match("!^forcestart (?<ip>.*)$!", $text, $match)) {
                            $match = \eBot\Manager\MatchManager::getInstance()->getMatch($match["ip"]);
                            if ($match) {
                                $match->adminForceStart();
                            } else {
                                Logger::error($match["ip"] . " is not managed !");
                            }
                        } elseif (preg_match("!^stopback (?<ip>.*)$!", $text, $match)) {
                            $match = \eBot\Manager\MatchManager::getInstance()->getMatch($match["ip"]);
                            if ($match) {
                                $match->adminStopBack();
                            } else {
                                Logger::error($match["ip"] . " is not managed !");
                            }
                        } elseif (preg_match("!^pauseunpause (?<ip>.*)$!", $text, $m)) {
                            preg_match("!(?<ip>(\d+).(\d+).(\d+).(\d+):(\d+))!", $m["ip"], $m2);
                            $match = \eBot\Manager\MatchManager::getInstance()->getMatch($m2["ip"]);
                            if ($match) {
                                $match->adminPauseUnpause();
                            } else {
                                Logger::error($m["ip"] . " is not managed !");
                            }
                        } elseif (preg_match("!^goBackRounds (?<ip>.*) (?<round>\d+)$!", $text, $match)) {
                            $match = \eBot\Manager\MatchManager::getInstance()->getMatch($match["ip"]);
                            if ($match) {
                                $match->adminGoBackRounds();
                            } else {
                                Logger::error($match["ip"] . " is not managed !");
                            }
                        } else {
                            Logger::error($text . " not managed");
                        }
                    }
                } else {
                    $line = substr($data, 7);

                    if (\eBot\Manager\MatchManager::getInstance()->getMatch($ip)) {
                        file_put_contents("logs/$ip", $line, FILE_APPEND);
                        $line = trim(substr($line, 23));
                        \eBot\Manager\MatchManager::getInstance()->getMatch($ip)->processMessage($line);
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

}

?>
