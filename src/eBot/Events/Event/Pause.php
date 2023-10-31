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

class Pause extends Event
{
    protected $match;
    protected $pause = true;
    protected $type;
    protected $team;
    protected $teamName;
    protected $admin = false;
}
