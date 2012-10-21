<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'exceptions/RCONBanException.php';
require_once STEAM_CONDENSER_PATH . 'exceptions/RCONNoAuthException.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/SteamPacket.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/rcon/RCONGoldSrcRequest.php';
require_once STEAM_CONDENSER_PATH . 'steam/sockets/SteamSocket.php';

/**
 * This class represents a socket used to communicate with game servers based
 * on the GoldSrc engine (e.g. Half-Life, Counter-Strike)
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage sockets
 */
class GoldSrcSocket extends SteamSocket {

    /**
     * @var boolean
     */
    private $isHLTV;

    /**
     * @var long
     */
    private $rconChallenge = -1;

    /**
     * Creates a new socket to communicate with the server on the given IP
     * address and port
     *
     * @param string $ipAddress Either the IP address or the DNS name of the
     *        server
     * @param int $portNumber The port the server is listening on
     * @param bool $isHLTV <var>true</var> if the target server is a HTLV
     *        instance. HLTV behaves slightly different for RCON commands, this
     *        flag increases compatibility.
     */
    public function __construct($ipAddress, $portNumber = 27015, $isHLTV = false) {
        parent::__construct($ipAddress, $portNumber);
        $this->isHLTV = $isHLTV;
    }

    /**
     * Reads a packet from the socket
     *
     * The Source query protocol specifies a maximum packet size of 1,400
     * bytes. Bigger packets will be split over several UDP packets. This
     * method reassembles split packets into single packet objects.
     *
     * @return SteamPacket The packet replied from the server
     */
    public function getReply() {
        $bytesRead = $this->receivePacket(1400);

        if($this->buffer->getLong() == -2) {
            do {
                $requestId = $this->buffer->getLong();
                $packetCountAndNumber = $this->buffer->getByte();
                $packetCount = $packetCountAndNumber & 0xF;
                $packetNumber = ($packetCountAndNumber >> 4) + 1;

                $splitPackets[$packetNumber - 1] = $this->buffer->get();

                trigger_error("Received packet $packetNumber of $packetCount for request #$requestId");

                if(sizeof($splitPackets) < $packetCount) {
                    try {
                        $bytesRead = $this->receivePacket();
                    } catch(TimeoutException $e) {
                        $bytesRead = 0;
                    }
                } else {
                    $bytesRead = 0;
                }
            } while($bytesRead > 0 && $this->buffer->getLong() == -2);

            $packet = SteamPacketFactory::reassemblePacket($splitPackets);
        } else {
            $packet = SteamPacketFactory::getPacketFromData($this->buffer->get());
        }

        trigger_error("Received packet of type \"" . get_class($packet) . "\"");

        return $packet;
    }

    /**
     * Executes the given command on the server via RCON
     *
     * @param string $password The password to authenticate with the server
     * @param string $command The command to execute on the server
     * @return RCONGoldSrcResponse The response replied by the server
     * @see rconChallenge()
     * @see rconSend()
     * @throws RCONBanException if the IP of the local machine has been banned
     *         on the game server
     * @throws RCONNoAuthException if the password is incorrect
     */
    public function rconExec($password, $command) {
        if($this->rconChallenge == -1 || $this->isHLTV) {
            $this->rconGetChallenge();
        }

        $this->rconSend("rcon {$this->rconChallenge} $password $command");
        $this->rconSend("rcon {$this->rconChallenge} $password");
        if($this->isHLTV) {
            try {
                $response = $this->getReply()->getResponse();
            } catch(TimeoutException $e) {
                $response = '';
            }
        } else {
            $response = $this->getReply()->getResponse();
        }

        if(trim($response) == 'Bad rcon_password.') {
            throw new RCONNoAuthException();
        } elseif(trim($response) == 'You have been banned from this server.') {
            throw new RCONBanException();
        }

        do {
            $responsePart = $this->getReply()->getResponse();
            $response .= $responsePart;
        } while(strlen($responsePart) > 0);

        return $response;
    }

    /**
     * Requests a challenge number from the server to be used for further
     * requests
     *
     * @throws RCONBanException if the IP of the local machine has been banned
     *         on the game server
     * @see rconSend()
     */
    public function rconGetChallenge() {
        $this->rconSend('challenge rcon');
        $response = trim($this->getReply()->getResponse());

        if($response == 'You have been banned from this server.') {
            throw new RCONBanException();
        }

        $this->rconChallenge = floatval(substr($response, 14));
    }

    /**
     * Wraps the given command in a RCON request packet and send it to the
     * server
     *
     * @param string $command The RCON command to send to the server
     */
    public function rconSend($command) {
        $this->send(new RCONGoldSrcRequest($command));
    }
}
