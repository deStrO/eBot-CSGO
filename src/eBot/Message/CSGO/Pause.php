<?php
/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Message\CSGO;

use eBot\Message\Message;
use eBot\Message\Type\Pause as MessageObject;

class Pause extends Message
{

    public function __construct()
    {
        parent::__construct('!Match pause is (?P<state>disabled|enabled) - (?P<reason>.*)!');
    }

    public function process()
    {
        $o = new MessageObject();
        $o->setEnabled($this->datas['state'] === 'enabled');
        $o->setReason($this->datas['reason']);

        return $o;
    }
}
