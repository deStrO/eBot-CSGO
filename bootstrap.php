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
$check["mcrypt"] = extension_loaded('mcrypt');
$check["mysql"] = extension_loaded('mysql');
$check["spl"] = extension_loaded('spl');
$check["sockets"] = extension_loaded("sockets");

echo "
      ____        _   
     |  _ \      | |  
  ___| |_) | ___ | |_ 
 / _ \  _ < / _ \| __|
|  __/ |_) | (_) | |_ 
 \___|____/ \___/ \__|
 " . PHP_EOL;

echo 'PHP Compatibility Test' . PHP_EOL;
echo '-----------------------------------------------------' . PHP_EOL;
echo '| PHP 5.3.1 or newer    -> required  -> ' . ($check["php"] ? ('[ Yes ] ' . phpversion()) : '[ No  ]') . PHP_EOL;
echo '| Standard PHP Library  -> required  -> ' . ($check["spl"] ? '[ Yes ]' : '[ No  ]') . PHP_EOL;
echo '| MySQL                 -> required  -> ' . ($check["mysql"] ? '[ Yes ]' : '[ No  ]') . PHP_EOL;
echo '| Sockets               -> required  -> ' . ($check["sockets"] ? '[ Yes ]' : '[ No  ]') . PHP_EOL;
echo '| MCrypt                -> required  -> ' . ($check["mcrypt"] ? '[ Yes ]' : '[ No  ]') . PHP_EOL;
echo '-----------------------------------------------------' . PHP_EOL;

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

include __DIR__ . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'steamCondenser' . DIRECTORY_SEPARATOR . 'steam-condenser.php';

// enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
gc_enable();

define('EBOT_DIRECTORY', __DIR__);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'autoload.php';

\eBot\Application\Application::getInstance()->run();
?>
