<?php

namespace eBot\Manager;

use eBot\Config\Config;
use eTools\Utils\Singleton;
use eTools\Task\Taskable;
use eTools\Task\Task;
use eTools\Task\TaskManager;
use eTools\Utils\Logger;
use eBot\Match\Match;

class ReportManager extends Singleton implements Taskable
{
    public function __construct()
    {
        TaskManager::getInstance()->addTask(new Task($this, "sendReport", microtime(true) + 10), true);
    }

    public function taskExecute($name)
    {
        if ($name == "sendReport") {
            $this->sendReport();
        }
    }

    private function sendReport()
    {
        $report = array(
            'hash' => $this->getHash(),
            'ip' => Config::getInstance()->getBot_ip(),
            'matches' => MatchManager::getInstance()->getMatchesCount()
        );

        try {
            $this->rest_helper('http://www.esport-tools.net/ebot/report/send', json_encode($report), 'POST');
            Logger::log('Report sent!');
        } catch (\Exception $e) {
            Logger::error('Unable to send the report to the eSport-tools.net website');
        }

        TaskManager::getInstance()->addTask(new Task($this, "sendReport", microtime(true) + 60 * 5), true);
    }

    private function rest_helper($url, $params = null, $verb = 'GET', $format = 'json')
    {
        $cparams = array(
            'http' => array(
                'method' => $verb,
                'ignore_errors' => true
            )
        );
        if ($params !== null) {
            $paramsU = http_build_query($params);
            if ($verb == 'POST') {
                $cparams['http']['content'] = $params;
            } else {
                $url .= '?' . $paramsU;
            }
        }

        $context = stream_context_create($cparams);
        $fp = fopen($url, 'rb', false, $context);
        if (!$fp) {
            $res = false;
        } else {
            $res = stream_get_contents($fp);
        }

        if ($res === false) {
            throw new \Exception("$verb $url failed: $php_errormsg");
        }

        switch ($format) {
            case 'json':
                $r = json_decode($res);
                if ($r === null) {
                    throw new \Exception("failed to decode $res as json");
                }
                return $r;

            case 'xml':
                $r = simplexml_load_string($res);
                if ($r === null) {
                    throw new \Exception("failed to decode $res as xml");
                }
                return $r;
        }
        return $res;
    }

    private function getHash()
    {
        $hash = md5(Config::getInstance()->getBot_ip() . ':' . Config::getInstance()->getBot_port() . ':' . Config::getInstance()->getMysql_ip() . ':' . Config::getInstance()->getMysql_user() . ':' . Config::getInstance()->getMysql_pass());
        return $hash;

    }
}