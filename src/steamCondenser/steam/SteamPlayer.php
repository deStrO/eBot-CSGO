<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'exceptions/SteamCondenserException.php';

/**
 * This class represents a player connected to a game server
 *
 * @author  Sebastian Staudt
 * @package steam-condenser
 */
class SteamPlayer {

    /**
     * @var int
     */
    private $clientPort;

    /**
     * @var float
     */
    private $connectTime;

    /**
     * @var bool
     */
    private $extended;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $ipAddress;

    /**
     * @var int
     */
    private $loss;

   /**
    * @var string
    */
    private $name;

    /**
     * @var int
     */
    private $ping;

    /**
     * @var int
     */
    private $rate;

    /**
     * @var int
     */
    private $realId;

    /**
     * @var int
     */
    private $score;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $steamId;

    /**
     * Creates a new player instancewith the given information
     *
     * @param int $id The ID of the player on the server
     * @param string $name The name of the player
     * @param int $score The score of the player
     * @param float $connectTime The time the player is connected to the
     *        server
     */
    public function __construct($id, $name, $score, $connectTime) {
        $this->connectTime = $connectTime;
        $this->id = $id;
        $this->name = $name;
        $this->score = $score;
        $this->extended = false;
    }

    /**
     * Extends a player object with information retrieved from a RCON call to
     * the status command
     *
     * @param string $playerData The player data retrieved from
     *        <var>rcon status</var>
     * @throws SteamCondenserException if the information belongs to another
     *         player
     */
    public function addInformation($playerData) {
        if($playerData['name'] != $this->name) {
            throw new SteamCondenserException('Information to add belongs to a different player.');
        }

        $this->extended = true;
        $this->realId   = intval($playerData['userid']);
        if(array_key_exists('state', $playerData)) {
            $this->state    = $playerData['state'];
        }
        $this->steamId  = $playerData['uniqueid'];

        if(!$this->isBot()) {
            $this->loss = intval($playerData['loss']);
            $this->ping = intval($playerData['ping']);

            if(array_key_exists('adr', $playerData)) {
                $address = explode(':', $playerData['adr']);
                $this->ipAddress  = $address[0];
                $this->clientPort = intval($address[1]);
            }

            if(array_key_exists('rate', $playerData)) {
                $this->rate = $playerData['rate'];
            }
        }
    }

    /**
     * Returns the client port of this player
     *
     * @return int The client port of the player
     */
    public function getClientPort() {
        return $this->clientPort;
    }

    /**
     * Returns the time this player is connected to the server
     *
     * @return float The connection time of the player
     */
    public function getConnectTime() {
        return $this->connectTime;
    }

    /**
     * Returns the ID of this player
     *
     * @return int The ID of this player
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the IP address of this player
     *
     * @return string The IP address of this player
     */
    public function getIpAddress() {
        return $this->ipAddress;
    }

    /**
     * Returns the packet loss of this player's connection
     *
     * @return string The packet loss of this player's connection
     */
    public function getLoss() {
        return $this->loss;
    }

    /**
     * Returns the nickname of this player
     *
     * @return string The name of this player
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the ping of this player
     *
     * @return int The ping of this player
     */
    public function getPing() {
        return $this->ping;
    }

    /**
     * Returns the rate of this player
     *
     * @return int The rate of this player
     */
    public function getRate() {
        return $this->rate;
    }

    /**
     * Returns the real ID (as used on the server) of this player
     *
     * @return int The real ID of this player
     */
    public function getRealId() {
        return $this->realId;
    }

    /**
     * Returns the score of this player
     *
     * @return int The score of this player
     */
    public function getScore() {
        return $this->score;
    }

    /**
     * Returns the connection state of this player
     *
     * @return string The connection state of this player
     */
    public function getState() {
        return $this->state;
    }

    /**
     * Returns the SteamID of this player
     *
     * @return string The SteamID of this player
     */
    public function getSteamId() {
        return $this->steamId;
    }

    /**
     * Returns whether this player is a bot
     *
     * @return bool <var>true</var> if this player is a bot
     */
    public function isBot() {
        return $this->steamId == 'BOT';
    }

    /**
     * Returns whether this player object has extended information gathered
     * using RCON
     *
     * @return bool <var>true</var> if extended information for this player
     *         is available
     */
    public function isExtended() {
        return $this->extended;
    }

    /**
     * Returns a string representation of this player
     *
     * @return string A string representing this player
     */
    public function __toString() {
        if($this->extended) {
            return "#{$this->realId} \"{$this->name}\", SteamID: {$this->steamId} Score: {$this->score}, Time: {$this->connectTime}";
        } else {
            return "#{$this->id} \"{$this->name}\", Score: {$this->score}, Time: {$this->connectTime}";
        }
    }
}
