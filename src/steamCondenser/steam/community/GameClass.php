<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * An abstract class implementing basic functionality for classes representing
 * player classes
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
abstract class GameClass {

    /**
     * @var String
     */
    protected $name;

    /**
     * @var float
     */
    protected $playtime;


    /**
     * Returns the name of this class
     *
     * @return string The name of this class
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the time in minutes the player has played with this class
     *
     * @return int The time this class has been played
     */
    public function getPlayTime() {
        return $this->playtime;
    }

}
