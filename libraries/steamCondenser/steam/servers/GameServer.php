<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'exceptions/SteamCondenserException.php';
require_once STEAM_CONDENSER_PATH . 'exceptions/TimeoutException.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/A2S_INFO_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/A2S_PLAYER_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/A2S_RULES_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/A2S_SERVERQUERY_GETCHALLENGE_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/servers/Server.php';

/**
 * This class is subclassed by classes representing different game server
 * implementations and provides the basic functionality to communicate with
 * them using the common query protocol
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage servers
 */
abstract class GameServer extends Server {

    const REQUEST_CHALLENGE = 0;
    const REQUEST_INFO      = 1;
    const REQUEST_PLAYER    = 2;
    const REQUEST_RULES     = 3;

    /**
     * @var int The challenge number to communicate with the server
     */
    protected $challengeNumber;

    /**
     * @var array Basic information about this server
     */
    protected $infoHash;

    /**
     * @var int The response time of this server
     */
    protected $ping;

    /**
     * @var array The players playing on this server
     */
    protected $playerHash;

    /**
     * @var bool whether the RCON connection is already authenticated
     */
    protected $rconAuthenticated;

    /**
     * @var array The settings applied on the server
     */
    protected $rulesHash;

    /**
     * @var SteamSocket The socket of to communicate with the server
     */
    protected $socket;

    /**
     * Parses the player attribute names supplied by <var>rcon status</var>
     *
     * @param string $statusHeader The header line provided by <var>rcon
     *        status</var>
     * @return array Split player attribute names
     * @see splitPlayerStatus()
     */
    protected function getPlayerStatusAttributes($statusHeader) {
        $statusAttributes = array();
        foreach(preg_split("/\s+/", $statusHeader) as $attribute) {
            if($attribute == 'connected') {
                $statusAttributes[] = 'time';
            } else if($attribute == 'frag') {
                $statusAttributes[] = 'score';
            } else {
                $statusAttributes[] = $attribute;
            }
        }

        return $statusAttributes;
    }

    /**
     * Splits the player status obtained with <var>rcon status</var>
     *
     * @param array $attributes The attribute names
     * @param string $playerStatus The status line of a single player
     * @return array The attributes with the corresponding values for this
     *         player
     * @see getPlayerStatusAttributes()
     */
    protected function splitPlayerStatus($attributes, $playerStatus) {
        if($attributes[0] != 'userid') {
            $playerStatus = preg_replace('/^\d+ +/', '', $playerStatus);
        }

        $firstQuote = strpos($playerStatus, '"');
        $lastQuote  = strrpos($playerStatus, '"');
        $data = array(
            substr($playerStatus, 0, $firstQuote),
            substr($playerStatus, $firstQuote + 1, $lastQuote - 1 - $firstQuote),
            substr($playerStatus, $lastQuote + 1)
        );

        $data = array_merge(
            array_filter(preg_split("/\s+/", trim($data[0]))),
            array($data[1]),
            preg_split("/\s+/", trim($data[2]))
        );
        $data = array_values($data);

        if(sizeof($attributes) > sizeof($data) &&
           in_array('state', $attributes)) {
            array_splice($data, 3, 0, array(null, null, null));
        } elseif(sizeof($attributes) < sizeof($data)) {
            unset($data[1]);
            $data = array_values($data);
        }

        $playerData = array();
        for($i = 0; $i < sizeof($data); $i ++) {
            $playerData[$attributes[$i]] = $data[$i];
        }

        return $playerData;
    }

    /**
     * Creates a new instance of a game server object
     *
     * @param string $address Either an IP address, a DNS name or one of them
     *        combined with the port number. If a port number is given, e.g.
     *        'server.example.com:27016' it will override the second argument.
     * @param int $port The port the server is listening on
     * @throws SteamCondenserException if an host name cannot be resolved
     */
    public function __construct($address, $port = 27015) {
        parent::__construct($address, $port);

        $this->rconAuthenticated = false;
    }

    /**
     * Returns the last measured response time of this server
     *
     * If the latency hasn't been measured yet, it is done when calling this
     * method for the first time.
     *
     * If this information is vital to you, be sure to call
     * {@link updatePing()} regularly to stay up-to-date.
     *
     * @return int The latency of this server in milliseconds
     * @see updatePing()
     */
    public function getPing() {
        if($this->ping == null) {
            $this->updatePing();
        }

        return $this->ping;
    }

    /**
     * Returns a list of players currently playing on this server
     *
     * If the players haven't been fetched yet, it is done when calling this
     * method for the first time.
     *
     * As the players and their scores change quite often be sure to update
     * this list regularly by calling {@link updatePlayers()} if you rely on
     * this information.
     *
     * @param string $rconPassword The RCON password of this server may be
     *        provided to gather more detailed information on the players, like
     *     STEAM_IDs.
     * @return array The players on this server
     * @see updatePlayers()
     */
    public function getPlayers($rconPassword = null) {
        if($this->playerHash == null) {
            $this->updatePlayers($rconPassword);
        }

        return $this->playerHash;
    }

    /**
     * Returns the settings applied on the server. These settings are also
     * called rules.
     *
     * If the rules haven't been fetched yet, it is done when calling this
     * method for the first time.
     *
     * As the rules usually don't change often, there's almost no need to
     * update this hash. But if you need to, you can achieve this by calling
     * {@link updateRules()}.
     *
     * @return array The currently active server rules
     * @see updateRules()
     */
    public function getRules() {
        if($this->rulesHash == null) {
            $this->updateRules();
        }

        return $this->rulesHash;
    }

    /**
     * Returns an associative array with basic information on the server.
     *
     * If the server information haven't been fetched yet, it is done when
     * calling this method for the first time.
     *
     * The server information usually only changes on map change and when
     * players join or leave. As the latter changes can be monitored by calling
     * {@link updatePlayers()}, there's no need to call
     * {@link updateServerInfo()} very often.
     *
     * @return array Server attributes with their values
     * @see updateServerInfo()
     */
    public function getServerInfo() {
        if($this->infoHash == null) {
            $this->updateServerInfo();
        }

        return $this->infoHash;
    }

    /**
     * Initializes this server object with basic information
     *
     * @see updateChallengeNumber()
     * @see updatePing()
     * @see updateServerInfo()
     */
    public function initialize() {
        $this->updatePing();
        $this->updateServerInfo();
        $this->updateChallengeNumber();
    }

    /**
     * Receives a response from the server
     *
     * @return SteamPacket The response packet replied by the server
     */
    protected function getReply() {
        return $this->socket->getReply();
    }

    /**
     * Sends the specified request to the server and handles the returned
     * response
     *
     * Depending on the given request type this will fill the various data
     * attributes of the server object.
     *
     * @param int $requestType The type of request to send to the server
     * @param bool $repeatOnFailure Whether the request should be repeated, if
     *        the replied packet isn't expected. This is useful to handle
     *        missing challenge numbers, which will be automatically filled in,
     *        although not requested explicitly.
     * @throws SteamCondenserException if either the request type or the
     *        response packet is not known
     */
    protected function handleResponseForRequest($requestType, $repeatOnFailure = true) {
        switch($requestType) {
            case self::REQUEST_CHALLENGE:
                $expectedResponse = 'S2C_CHALLENGE_Packet';
                $requestPacket    = new A2S_PLAYER_Packet();
                break;
            case self::REQUEST_INFO:
                $expectedResponse = 'S2A_INFO_BasePacket';
                $requestPacket    = new A2S_INFO_Packet();
                break;
            case self::REQUEST_PLAYER:
                $expectedResponse = 'S2A_PLAYER_Packet';
                $requestPacket    = new A2S_PLAYER_Packet($this->challengeNumber);
                break;
            case self::REQUEST_RULES:
                $expectedResponse = 'S2A_RULES_Packet';
                $requestPacket    = new A2S_RULES_Packet($this->challengeNumber);
                break;
            default:
                throw new SteamCondenserException('Called with wrong request type.');
        }

        $this->sendRequest($requestPacket);

        $responsePacket = $this->getReply();

        if($responsePacket instanceof S2A_INFO_BasePacket) {
            $this->infoHash = $responsePacket->getInfoHash();
        } elseif($responsePacket instanceof S2A_PLAYER_Packet) {
            $this->playerHash = $responsePacket->getPlayerHash();
        } elseif($responsePacket instanceof S2A_RULES_Packet) {
            $this->rulesHash = $responsePacket->getRulesArray();
        } elseif($responsePacket instanceof S2C_CHALLENGE_Packet) {
            $this->challengeNumber = $responsePacket->getChallengeNumber();
        } else {
            throw new SteamCondenserException('Response of type ' . get_class($responsePacket) . ' cannot be handled by this method.');
        }

        if(!($responsePacket instanceof $expectedResponse)) {
            trigger_error("Expected {$expectedResponse}, got " . get_class($responsePacket) . '.');
            if($repeatOnFailure) {
                $this->handleResponseForRequest($requestType, false);
            }
        }
    }

    /**
     * Returns whether the RCON connection to this server is already
     * authenticated
     *
     * @return bool <var>true</var> if the RCON connection is authenticated
     * @see rconAuth()
     */
    public function isRconAuthenticated() {
        return $this->rconAuthenticated;
    }

    /**
     * Authenticates the connection for RCON communication with the server
     *
     * @param string $password The RCON password of the server
     * @return bool whether authentication was successful
     * @see rconAuth()
     * @throws SteamCondenserException if a problem occurs while parsing the
     *         reply
     * @throws TimeoutException if the request times out
     */
    abstract public function rconAuth($password);

    /**
     * Remotely executes a command on the server via RCON
     *
     * @param string $command The command to execute on the server via RCON
     * @return string The output of the executed command
     * @see rconExec()
     * @throws SteamCondenserException if a problem occurs while parsing the
     *         reply
     * @throws TimeoutException if the request times out
     */
    abstract public function rconExec($command);

    /**
     * Sends a request packet to the server
     *
     * @param SteamPacket $requestData The request packet to send to the server
     */
    protected function sendRequest(SteamPacket $requestData) {
        $this->socket->send($requestData);
    }

    /**
     * Sends a A2S_SERVERQUERY_GETCHALLENGE request to the server and updates
     * the challenge number used to communicate with this server
     *
     * There's usually no need to call this method explicitly, because
     * {@link handleResponseForRequest()} will automatically get the challenge
     * number when the server assigns a new one.
     *
     * @see handleResponseForRequest()
     * @see initialize()
     */
    public function updateChallengeNumber() {
        $this->handleResponseForRequest(self::REQUEST_CHALLENGE);
    }

    /**
     * Sends a A2S_INFO request to the server and measures the time needed for
     * the reply
     *
     * If this information is vital to you, be sure to call this method
     * regularly to stay up-to-date.
     *
     * @return int The latency of this server in milliseconds
     * @see getPing()
     * @see initialize()
     */
    public function updatePing() {
        $this->sendRequest(new A2S_INFO_Packet());
        $startTime = microtime(true);
        $this->getReply();
        $endTime = microtime(true);
        $this->ping = intval(round(($endTime - $startTime) * 1000));

        return $this->ping;
    }

    /**
     * Sends a A2S_PLAYERS request to the server and updates the players' data
     * for this server
     *
     * As the players and their scores change quite often be sure to update
     * this list regularly by calling this method if you rely on this
     * information.
     *
     * @param string $rconPassword The RCON password of this server may be
     *        provided to gather more detailed information on the players, like
     *        STEAM_IDs.
     * @see getPlayers()
     * @see handleResponseForRequest()
     */
    public function updatePlayers($rconPassword = null) {
        $this->handleResponseForRequest(self::REQUEST_PLAYER);

        if(!$this->rconAuthenticated) {
            if($rconPassword == null) {
                return;
            }
            $this->rconAuth($rconPassword);
        }

        $players = array();
        foreach(explode("\n", $this->rconExec('status')) as $line) {
            if(strpos($line, '#') === 0 && $line != '#end') {
                $players[] = trim(substr($line, 1));
            }
        }
        $attributes = $this->getPlayerStatusAttributes(array_shift($players));

        foreach($players as $player) {
            $playerData = $this->splitPlayerStatus($attributes, $player);
            if(array_key_exists($playerData['name'], $this->playerHash)) {
                $this->playerHash[$playerData['name']]->addInformation($playerData);
            }
        }
    }

    /**
     * Sends a A2S_RULES request to the server and updates the rules of this
     * server
     *
     * As the rules usually don't change often, there's almost no need to
     * update this hash. But if you need to, you can achieve this by calling
     * this method.
     *
     * @see getRules()
     * @see handleResponseForRequest()
     */
    public function updateRules() {
        $this->handleResponseForRequest(self::REQUEST_RULES);
    }

    /**
     * Sends a A2S_INFO request to the server and updates this server's basic
     * information
     *
     * The server information usually only changes on map change and when
     * players join or leave. As the latter changes can be monitored by calling
     * {@link updatePlayers()}, there's no need to call this method very often.
     *
     * @see getServerInfo()
     * @see handleResponseForRequest()
     * @see initialize()
     */
    public function updateServerInfo() {
        $this->handleResponseForRequest(self::REQUEST_INFO);
    }

    /**
     * Returns a human-readable text representation of the server
     *
     * @return string Available information about the server in a
     *         human-readable format
     */
    public function __toString() {
        $returnString = '';

        $returnString .= "Ping: {$this->ping}\n";
        $returnString .= "Challenge number: {$this->challengeNumber}\n";

        if($this->infoHash != null) {
            $returnString .= "Info:\n";
            foreach($this->infoHash as $key => $value) {
                if(is_array($value)) {
                    $returnString .= "  {$key}:\n";
                    foreach($value as $subKey => $subValue) {
                        $returnString .= "    {$subKey} = {$subValue}\n";
                    }
                } else {
                    $returnString .= "  {$key}: {$value}\n";
                }
            }
        }

        if($this->playerHash != null) {
            $returnString .= "Players:\n";
            foreach($this->playerHash as $player) {
                $returnString .= "  {$player}\n";
            }
        }

        if($this->rulesHash != null) {
            $returnString .= "Rules:\n";
            foreach($this->rulesHash as $key => $value) {
                $returnString .= "  {$key}: {$value}\n";
            }
        }

        return $returnString;
    }
}
