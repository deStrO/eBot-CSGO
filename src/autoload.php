<?php
/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

define('APP_ROOT', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

spl_autoload_register("ebot_autoload");

function ebot_autoload($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $path = APP_ROOT . 'libraries' . DIRECTORY_SEPARATOR . $class . '.php';
    if (file_exists($path))
        require_once $path;
    else 
        echo "Can't load $class".PHP_EOL;
}

?>
