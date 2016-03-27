<?php
namespace eBot\Message\CSGO;

use eBot\Message\Message;
use eBot\Message\Type\Attacked as Object;
class Attacked extends Message {
    public function __construct() {
        parent::__construct('/^"(?P<attackerName>.*)[<](?P<attackerUserId>\d+)[>][<](?P<attackerSteamId>.*)[>][<](?P<attackerTeam>CT|TERRORIST|Unassigned|Spectator)[>]" \[(?P<attackerPosX>[\-]?[0-9]+) (?P<attackerPosY>[\-]?[0-9]+) (?P<attackerPosZ>[\-]?[0-9]+)\] attacked "(?P<victimName>.*)[<](?P<victimUserId>\d+)[>][<](?P<victimSteamId>.*)[>][<](?P<victimTeam>CT|TERRORIST|Unassigned|Spectator)[>]" \[(?P<victimPosX>[\-]?[0-9]+) (?P<victimPosY>[\-]?[0-9]+) (?P<victimPosZ>[\-]?[0-9]+)\] with "(?P<attackerWeapon>[a-zA-Z0-9_]+)" \(damage "(?P<attackerDamage>[0-9]+)"\) \(damage_armor "(?P<attackerDamageArmor>[0-9]+)"\) \(health "(?P<victimHealth>[0-9]+)"\) \(armor "(?P<victimArmor>[0-9]+)"\) \(hitgroup "(?P<attackerHitGroup>.*)"\)/');
    }

    public function process() {
        $o = new Object();
        $o->setAttackerName($this->datas['attackerName']);
        $o->setAttackerUserId($this->datas['attackerUserId']);
        $o->setAttackerSteamId($this->datas['attackerSteamId']);
        $o->setAttackerTeam($this->datas['attackerTeam']);
        $o->setAttackerPosX($this->datas['attackerPosX']);
        $o->setAttackerPosY($this->datas['attackerPosY']);
        $o->setAttackerPosZ($this->datas['attackerPosZ']);
        $o->setAttackerWeapon($this->datas['attackerWeapon']);
        $o->setAttackerDamage($this->datas['attackerDamage']);
        $o->setAttackerDamageArmor($this->datas['attackerDamageArmor']);
        $o->setAttackerHitGroup($this->datas['attackerHitGroup']);

        $o->setVictimName($this->datas['victimName']);
        $o->setVictimUserId($this->datas['victimUserId']);
        $o->setVictimSteamId($this->datas['victimSteamId']);
        $o->setVictimTeam($this->datas['victimTeam']);
        $o->setVictimPosX($this->datas['victimPosX']);
        $o->setVictimPosY($this->datas['victimPosY']);
        $o->setVictimPosZ($this->datas['victimPosZ']);
        $o->setVictimHealth($this->datas['victimHealth']);
        $o->setVictimArmor($this->datas['victimArmor']);

        return $o;
    }
}

?>
