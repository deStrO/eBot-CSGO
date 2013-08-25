<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Message\Type;

use eBot\Message\Type;

class Say extends Type {

    const SAY_TEAM = 1;
    const SAY = 0;

    public $userId = "";
    public $userName = "";
    public $userTeam = "";
    public $userSteamid = "";
    public $text;
    public $type;

    public function __construct() {
        $this->setName("Say");
    }

}

?>
