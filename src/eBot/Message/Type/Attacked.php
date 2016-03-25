<?php

namespace eBot\Message\Type;

use eBot\Message\Type;
class Attacked extends Type {
    public $attackerName = "";
    public $attackerUserId = "";
    public $attackerSteamId = "";
    public $attackerTeam = "";
    public $attackerPosX = 0;
    public $attackerPosY = 0;
    public $attackerPosZ = 0;
    public $attackerWeapon = "";
    public $attackerDamage = 0;
    public $attackerDamageArmor = 0;
    public $attackerHitGroup = "";

    public $victimName = "";
    public $victimUserId = "";
    public $victimSteamId = "";
    public $victimTeam = "";
    public $victimPosX = 0;
    public $victimPosY = 0;
    public $victimPosZ = 0;
    public $victimHealth = 0;
    public $victimArmor = 0;

    public function __construct() {
        $this->setName("Attacked");
    }   
}

?>
