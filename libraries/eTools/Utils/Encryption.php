<?php
/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eTools\Utils;

use \eTools\Utils\Singleton;

class Encryption extends Singleton {

    private $CRYPT_CKEY = "98qW5L4DnS11qYj98P5kL1P6";
    private $CRYPT_CIV = "HBq6Jl4q";
    private $CRYPT_CBIT_CHECK = 32;

    public function getCRYPT_CKEY() {
        return $this->CRYPT_CKEY;
    }

    public function setCRYPT_CKEY($CRYPT_CKEY) {
        $this->CRYPT_CKEY = $CRYPT_CKEY;
    }

    public function getCRYPT_CIV() {
        return $this->CRYPT_CIV;
    }

    public function setCRYPT_CIV($CRYPT_CIV) {
        $this->CRYPT_CIV = $CRYPT_CIV;
    }

    public function getCRYPT_CBIT_CHECK() {
        return $this->CRYPT_CBIT_CHECK;
    }

    public function setCRYPT_CBIT_CHECK($CRYPT_CBIT_CHECK) {
        $this->CRYPT_CBIT_CHECK = $CRYPT_CBIT_CHECK;
    }

    public function encrypt($text) {
        $text_num = str_split($text, $this->CRYPT_CBIT_CHECK);
        $text_num = $this->CRYPT_CBIT_CHECK - strlen($text_num[count($text_num) - 1]);

        for ($i = 0; $i < $text_num; $i++)
            $text = $text . chr($text_num);

        $cipher = mcrypt_module_open(MCRYPT_TRIPLEDES, '', 'cbc', '');
        mcrypt_generic_init($cipher, $this->CRYPT_CKEY, $this->CRYPT_CIV);

        $decrypted = mcrypt_generic($cipher, $text);
        mcrypt_generic_deinit($cipher);

        return base64_encode($decrypted);
    }

    public function decrypt($encrypted_text) {
        $cipher = mcrypt_module_open(MCRYPT_TRIPLEDES, '', 'cbc', '');
        mcrypt_generic_init($cipher, $this->CRYPT_CKEY, $this->CRYPT_CIV);

        $decrypted = mdecrypt_generic($cipher, base64_decode($encrypted_text));
        mcrypt_generic_deinit($cipher);

        $last_char = substr($decrypted, -1);

        for ($i = 0; $i < ($this->CRYPT_CBIT_CHECK - 1); $i++) {
            if (chr($i) == $last_char) {
                $decrypted = substr($decrypted, 0, strlen($decrypted) - $i);
                break;
            }
        }

        return $decrypted;
    }

}

?>
