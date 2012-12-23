<?php
/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eTools\Rcon;

use \eTools\Rcon;
use eTools\Utils\Logger;

class CSGO extends Rcon {

    /**
     *
     * @var \SourceServer 
     */
    private $server = null;

    public function auth() {
        $this->server = new \SourceServer($this->getIp(), $this->getPort());
        try {
            $this->server->rconAuth($this->getRcon());
            $this->status = true;
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->status = false;
            return false;
        }
    }

    public function send($cmd) {
        if ($this->server != null) {
            if ($this->status) {
                try {
                    return $this->server->rconExec($cmd);
                } catch (\Exception $e) {
                    Logger::error("Error while doing $cmd " . $e->getMessage());
                    return false;
                }
            }
        }
        
        return false;
    }

}

?>
