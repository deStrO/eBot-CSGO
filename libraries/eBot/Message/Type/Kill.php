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

class Kill extends Type {

    public $userId = "";
	public $userName = "";
	public $userTeam = "";
	public $userSteamid = "";
    
    public $killedUserId = "";
	public $killedUserName = "";
	public $killedUserTeam = "";
	public $killedUserSteamid = "";
    
    public $weapon;
    public $headshot;
    
    public function __construct() {
        $this->setName("Kill");
    }

}

?>
