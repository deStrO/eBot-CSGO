<?php

namespace eBot\Events\Event;

use eBot\Events\Event;

class MatchEnd extends Event
{
    protected $match;
    protected $score1;
    protected $score2;
}
