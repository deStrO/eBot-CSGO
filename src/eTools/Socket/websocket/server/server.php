<?php

ini_set('display_errors', '1');

require(__DIR__ . '/lib/SplClassLoader.php');

$classLoader = new SplClassLoader('WebSocket', __DIR__ . '/lib');
$classLoader->register();

$server = new \WebSocket\Server($websocket_ip, $websocket_port, false);

// server settings:
$server->setMaxClients(100);
$server->setCheckOrigin(false);

// Hint: Status application should not be removed as it displays usefull server informations:
$server->registerApplication('match', \WebSocket\Application\match::getInstance());
$server->registerApplication('rcon', \WebSocket\Application\rcon::getInstance());
$server->registerApplication('logger', \WebSocket\Application\logger::getInstance());
$server->registerApplication('livemap', \WebSocket\Application\livemap::getInstance());
$server->registerApplication('alive', \WebSocket\Application\aliveCheck::getInstance());

$server->run();
