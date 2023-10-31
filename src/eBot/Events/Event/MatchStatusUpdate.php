<?php

namespace eBot\Events\Event;

use eBot\Events\Event;

class MatchStatusUpdate extends Event
{
    protected $match;
    protected $status;
    protected $statusText;
}
