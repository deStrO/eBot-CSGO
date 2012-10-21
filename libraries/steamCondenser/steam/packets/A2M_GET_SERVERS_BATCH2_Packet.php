<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/SteamPacket.php';

/**
 * This packet class represents a A2M_GET_SERVERS_BATCH2 request sent to a
 * master server
 *
 * It is used to receive a list of game servers matching the specified filters.
 *
 * Filtering:
 * Instead of filtering the results sent by the master server locally, you
 * should at least use the following filters to narrow down the results sent by
 * the master server. Receiving all servers from the master server is taking
 * quite some time.
 *
 * Available filters:
 * <ul>
 *  <li><var>\type\d</var>: Request only dedicated servers</li>
 *  <li><var>\secure\1</var>: Request only secure servers</li>
 *  <li><var>\gamedir\[mod]</var>: Request only servers of a specific mod</li>
 *  <li><var>\map\[mapname]</var>: Request only servers running a specific
 *      map</li>
 *  <li><var>\linux\1</var>: Request only linux servers</li>
 *  <li><var>\emtpy\1</var>: Request only <b>non</b>-empty servers</li>
 *  <li><var>\full\1</var>: Request only servers <b>not</b> full</li>
 *  <li><var>\proxy\1</var>: Request only spectator proxy servers</li>
 * </ul>
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage packets
 * @see MasterServer::getServers()
 */
class A2M_GET_SERVERS_BATCH2_Packet extends SteamPacket {

    private $filter;
    private $regionCode;
    private $startIp;

    /**
     * Creates a new A2M_GET_SERVERS_BATCH2 request object, filtering by the
     * given paramters
     *
     * @param int $regionCode The region code to filter servers by region.
     * @param string $startIp This should be the last IP received from the
     *        master server or 0.0.0.0
     * @param string $filter The filters to apply in the form
     *        ("\filtername\value...")
     */
    public function __construct($regionCode = MasterServer::REGION_ALL, $startIp = '0.0.0.0', $filter = '') {
        parent::__construct(SteamPacket::A2M_GET_SERVERS_BATCH2_HEADER);

        $this->filter = $filter;
        $this->regionCode = $regionCode;
        $this->startIp = $startIp;
    }

    /**
     * Returns the raw data representing this packet
     *
     * @return string A string containing the raw data of this request packet
     */
    public function __toString() {
        return chr($this->headerData) . chr($this->regionCode) . $this->startIp . "\0" . $this->filter . "\0";
    }
}
