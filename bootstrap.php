<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */
$check["php"] = (function_exists('version_compare') && version_compare(phpversion(), '7.4', '>='));
$check["mysql"] = extension_loaded('mysqli');
$check["redis"] = extension_loaded('redis');
$check["json"] = extension_loaded('json');
$check["spl"] = extension_loaded('spl');
$check["sockets"] = extension_loaded("sockets");

define('EBOT_DIRECTORY', __DIR__);
define('APP_ROOT', __DIR__ . DIRECTORY_SEPARATOR);

require_once APP_ROOT . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
//require_once APP_ROOT . 'websocket' . DIRECTORY_SEPARATOR . 'websocket.client.php';
require_once APP_ROOT . 'websocket' . DIRECTORY_SEPARATOR . 'websocket.hotfix.php';

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
echo "| PHP 7.4 or newer      -> required  -> " . ($check["php"] ? ("[\033[0;32m Yes \033[0m] " . phpversion()) : "[\033[0;31m No \033[0m]") . PHP_EOL;
echo "| Standard PHP Library  -> required  -> " . ($check["spl"] ? "[\033[0;32m Yes \033[0m]" : "[\033[0;31m No \033[0m]") . PHP_EOL;
echo "| MySQL                 -> required  -> " . ($check["mysql"] ? "[\033[0;32m Yes \033[0m]" : "[\033[0;31m No \033[0m]") . PHP_EOL;
echo "| Sockets               -> required  -> " . ($check["sockets"] ? "[\033[0;32m Yes \033[0m]" : "[\033[0;31m No \033[0m]") . PHP_EOL;
echo "| Redis                 -> required  -> " . ($check["redis"] ? "[\033[0;32m Yes \033[0m]" : "[\033[0;31m No \033[0m]") . PHP_EOL;
echo "| JSON                  -> required  -> " . ($check["json"] ? "[\033[0;32m Yes \033[0m]" : "[\033[0;31m No \033[0m]") . PHP_EOL;
echo "-----------------------------------------------------" . PHP_EOL;

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

    if (PHP_OS == "Linux" && $webSocketProcess)
        proc_terminate($webSocketProcess, 9);

    $error = error_get_last();
    if (!empty($error)) {
        $info = "[SHUTDOWN] date: " . date("d.m.y H:m", time()) . " file: " . $error['file'] . " | ln: " . $error['line'] . " | msg: " . $error['message'] . PHP_EOL;
        file_put_contents(APP_ROOT . 'logs' . DIRECTORY_SEPARATOR . 'error.log', $info, FILE_APPEND);
    }
}

echo "| Register Shutdown function !" . PHP_EOL;
register_shutdown_function('handleShutdown');

error_reporting(E_ERROR);

$config = \eBot\Config\Config::getInstance();

if ($config->getWebsocketSecretKey() === 'generatestrongsecretkey') {
    echo "| You must set a WEBSOCKET_SECRET_KEY in config.ini file and the same in app_user.yml on web configuration" . PHP_EOL;
    echo '-----------------------------------------------------' . PHP_EOL;
    exit();

}
if ($config->getNodeStartupMethod() != "none") {
    // Starting ebot Websocket Server
    if (PHP_OS == "Linux") {
        echo "| Starting eBot Websocket-Server !" . PHP_EOL;
        echo "| Using ".$config->getNodeStartupMethod().PHP_EOL;
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("file", APP_ROOT . "logs" . DIRECTORY_SEPARATOR . "websocket.log", "a"),
            2 => array("file", APP_ROOT . "logs" . DIRECTORY_SEPARATOR . "websocket.error", "a")
        );
	    $webSocketProcess = proc_open($config->getNodeStartupMethod() . ' ' . APP_ROOT . 'websocket_server.mjs ' . \eBot\Config\Config::getInstance()->getBot_ip() . ' ' . \eBot\Config\Config::getInstance()->getBot_port() . ' ' . (\eBot\Config\Config::getInstance()->isSSLEnabled() ? 'TRUE': 'FALSE') . ' ' . \eBot\Config\Config::getInstance()->getSSLCertificatePath() . ' ' . \eBot\Config\Config::getInstance()->getSSLKeyPath(), $descriptorspec, $pipes);
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
} else {
    echo "| WebSocket Server will be started manually!" . PHP_EOL;
}

echo '-----------------------------------------------------' . PHP_EOL;

\eBot\Application\Application::getInstance()->run();
