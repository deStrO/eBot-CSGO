<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */
$check["php"] = (function_exists('version_compare') && version_compare(phpversion(), '5.3.1', '>='));
$check["php5.4"] = (function_exists('version_compare') && version_compare(phpversion(), '5.4', '>='));
$check["mysql"] = extension_loaded('mysql');
$check["spl"] = extension_loaded('spl');
$check["sockets"] = extension_loaded("sockets");

define('EBOT_DIRECTORY', __DIR__);
define('APP_ROOT', __DIR__ . DIRECTORY_SEPARATOR);
require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once 'steam-condenser.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'websocket' . DIRECTORY_SEPARATOR . 'websocket.client.php';

echo "
      ____        _
     |  _ \      | |
  ___| |_) | ___ | |_
 / _ \  _ < / _ \| __|
|  __/ |_) | (_) | |_
 \___|____/ \___/ \__|
 " . PHP_EOL;

echo "PHP Compatibility Test" . PHP_EOL;
echo "-----------------------------------------------------" . PHP_EOL;
echo "| PHP 5.3.1 or newer    -> required  -> " . ($check["php"] ? ("[\033[0;32m Yes \033[0m]" . phpversion()) : "[\033[0;31m No \033[0m]") . PHP_EOL;
echo "| Standard PHP Library  -> required  -> " . ($check["spl"] ? "[\033[0;32m Yes \033[0m]" : "[\033[0;31m No \033[0m]") . PHP_EOL;
echo "| MySQL                 -> required  -> " . ($check["mysql"] ? "[\033[0;32m Yes \033[0m]" : "[\033[0;31m No \033[0m]") . PHP_EOL;
echo "| Sockets               -> required  -> " . ($check["sockets"] ? "[\033[0;32m Yes \033[0m]" : "[\033[0;31m No \033[0m]") . PHP_EOL;
echo "-----------------------------------------------------" . PHP_EOL;

if (!$check["php5.4"]) {
    echo "| We recommand to use PHP5.4 to get better performance !" . PHP_EOL;
    echo '-----------------------------------------------------' . PHP_EOL;
}

unset($check["php5.4"]);

if (in_array(false, $check)) {
    echo "| Your php configuration missed, please make sure that you have all feature !" . PHP_EOL;
    echo '-----------------------------------------------------' . PHP_EOL;
    exit();
}

// better checking if timezone is set
if (!ini_get('date.timezone')) {
    $timezone = @date_default_timezone_get();
    echo '| Timezone is not set in php.ini. Please edit it and change/set "date.timezone" appropriately. '
    . 'Setting to default: \'' . $timezone . '\'' . PHP_EOL;
    echo '-----------------------------------------------------' . PHP_EOL;
    date_default_timezone_set($timezone);
}

// enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
gc_enable();

function handleShutdown() {
    global $webSocketProcess;

    if (PHP_OS == "Linux") {
        proc_terminate($webSocketProcess,9);
        foreach (\eBot\Application\ApplicationServer::getInstance()->instance as $proc) {
            proc_terminate($proc,9);
        }
    }

    $error = error_get_last();
    if (!empty($error)) {
        $info = "[SHUTDOWN] date: " . date("d.m.y H:m", time()) . " file: " . $error['file'] . " | ln: " . $error['line'] . " | msg: " . $error['message'] . PHP_EOL;
        file_put_contents(APP_ROOT . 'logs' . DIRECTORY_SEPARATOR . 'error.log', $info, FILE_APPEND);
    }
}

echo "| Registerung Shutdown function !" . PHP_EOL;
register_shutdown_function('handleShutdown');


// Starting ebot Websocket Server
if (PHP_OS == "Linux") {
    echo "| Starting eBot Websocket-Server !" . PHP_EOL;
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("file", "websocket.log", "a"),
        2 => array("file", "websocket.error", "a")
    );
    $webSocketProcess = proc_open('node ' . __DIR__ . '/websocket_server.js ' . \eBot\Config\Config::getInstance()->getBot_ip() . ' ' . \eBot\Config\Config::getInstance()->getBot_port(), $descriptorspec, $pipes);
    if (is_resource($webSocketProcess)) {
        fclose($pipes[0]);
        usleep(400000);
        $status = proc_get_status($webSocketProcess);
        if (!$status['running']) {
            echo '| WebSocket server crashed' . PHP_EOL;
            echo '-----------------------------------------------------' . PHP_EOL;
            die();
        }
        echo "| WebSocket has been started" . PHP_EOL;
    }
} else {
    echo "| You are under windows, please run websocket_server.bat before starting ebot" . PHP_EOL;
    sleep(5);
}

echo '-----------------------------------------------------' . PHP_EOL;

error_reporting(E_ALL);
\eBot\Application\ApplicationServer::getInstance()->run();
