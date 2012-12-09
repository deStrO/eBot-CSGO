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

class ThrewStuff extends Type {

    public $userId = "";
    public $userName = "";
    public $userTeam = "";
    public $userSteamid = "";
    public $posX = 0;
    public $posY = 0;
    public $posZ = 0;
    public $stuff = 0;
    
    public function __construct() {
        $this->setName("ThrewStuff");
    }

}

?>
