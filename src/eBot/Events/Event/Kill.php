<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Events\Event;

use eBot\Events\Event;
use eBot\Match\Match;

/**
 * @method Match getMatch()
 * @method string getUserId()
 * @method string getUserName()
 * @method string getUserTeam()
 * @method string getUserSteamid()
 * @method string getKilledUserId()
 * @method string getKilledUserName()
 * @method string getKilledUserTeam()
 * @method string getKilledUserSteamid()
 * @method string getWeapon()
 * @method bool getHeadshot()
 */
class Kill extends Event
{
    protected $match;
    protected $userId = "";
    protected $userName = "";
    protected $userTeam = "";
    protected $userSteamid = "";
    protected $killedUserId = "";
    protected $killedUserName = "";
    protected $killedUserTeam = "";
    protected $killedUserSteamid = "";
    protected $weapon;
    protected $headshot;

}
