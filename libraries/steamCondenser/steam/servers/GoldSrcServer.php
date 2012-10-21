<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/servers/GameServer.php';
require_once STEAM_CONDENSER_PATH . 'steam/servers/MasterServer.php';
require_once STEAM_CONDENSER_PATH . 'steam/sockets/GoldSrcSocket.php';

/**
 * This class represents a GoldSrc game server and can be used to query
 * information about and remotely execute commands via RCON on the server
 *
 * A GoldSrc game server is an instance of the Half-Life Dedicated Server
 * (HLDS) running games using Valve's GoldSrc engine, like Half-Life
 * Deathmatch, Counter-Strike 1.6 or Team Fortress Classic.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage servers
 * @see        SourceServer
 */
class GoldSrcServer extends GameServer {

    /**
     * @var bool
     */
    protected $isHLTV;

    /**
     * @var string
     */
    protected $rconPassword;

    /**
     * Returns a master server instance for the default master server for
     * GoldSrc games
     *
     * @return MasterServer The GoldSrc master server
     */
    public static function getMaster() {
        return new MasterServer(MasterServer::GOLDSRC_MASTER_SERVER);
    }

    /**
     * Creates a new instance of a GoldSrc server object
     *
     * @param string $address Either an IP address, a DNS name or one of them
     *        combined with the port number. If a port number is given, e.g.
     *        'server.example.com:27016' it will override the second argument.
     * @param int $port The port the server is listening on
     * @param bool $isHLTV HLTV servers need special treatment, so this is used
     *        to determine if the server is a HLTV server
     * @throws SteamCondenserException if an host name cannot be resolved
     */
    public function __construct($address, $port = 27015, $isHLTV = false) {
        parent::__construct($address, $port);

        $this->isHLTV = $isHLTV;
    }

    /**
     * Initializes the sockets to communicate with the GoldSrc server
     *
     * @see GoldSrcSocket
     */
    public function initSocket() {
        $this->socket = new GoldSrcSocket($this->ipAddress, $this->port, $this->isHLTV);
    }

    /**
     * Saves the password for authenticating the RCON communication with the
     * server
     *
     * @param string $password The RCON password of the server
     * @return bool GoldSrc's RCON does not preauthenticate connections so
     *         this method always returns <var>true</var>
     * @see rconAuth()
     */
    public function rconAuth($password) {
        $this->rconPassword = $password;

        return true;
    }

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
    public function rconExec($command) {
        return trim($this->socket->rconExec($this->rconPassword, $command));
    }
}
