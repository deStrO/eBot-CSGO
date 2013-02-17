<?php
/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

define('EBOT_DIRECTORY', __DIR__);
define('APP_ROOT', __DIR__ . DIRECTORY_SEPARATOR);
use eBot\Config\Config;

$websocket_ip = Config::getInstance()->getBot_ip();
$websocket_port = Config::getInstance()->getBot_port();

require(__DIR__ . '/src/eTools/Socket/websocket/server/server.php');

?>