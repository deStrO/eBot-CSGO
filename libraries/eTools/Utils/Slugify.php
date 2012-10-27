<?php

namespace eTools\Utils;

class Slugify {

    public function cleanTeamName($mycontent) {
        $mycontent = str_replace("é", "e", $mycontent);
        $mycontent = str_replace("è", "e", $mycontent);
        $mycontent = str_replace("ê", "e", $mycontent);
        $mycontent = str_replace("à", "a", $mycontent);
        $mycontent = str_replace("â", "a", $mycontent);
        $mycontent = str_replace("î", "i", $mycontent);
        $mycontent = str_replace("ù", "u", $mycontent);

        $mycontent = str_replace("'", " ", $mycontent);
        $mycontent = str_replace("-", " ", $mycontent);
        $mycontent = str_replace(":", " ", $mycontent);
        $mycontent = str_replace("!", " ", $mycontent);
        $mycontent = str_replace("?", " ", $mycontent);
        $mycontent = str_replace(";", " ", $mycontent);
        $mycontent = str_replace(",", " ", $mycontent);
        $mycontent = str_replace("`", " ", $mycontent);
        $mycontent = str_replace("|", " ", $mycontent);
        $mycontent = str_replace(".", " ", $mycontent);

        $mycontent = str_replace("[", " ", $mycontent);
        $mycontent = str_replace("]", " ", $mycontent);
        $mycontent = preg_replace("/[^a-z A-Z0-9]/i", '', $mycontent);
        $mycontent = str_replace(" ", "-", $mycontent);
        $mycontent = preg_replace('/--+/', '-', $mycontent);
        if (strlen($mycontent) > 1 && $mycontent[0] == '-')
            $mycontent = substr($mycontent, 1);
        if (strlen($mycontent) > 1 && $mycontent[strlen($mycontent) - 1] == '-')
            $mycontent = substr($mycontent, 0, strlen($mycontent) - 1);
        return strtolower($mycontent);
    }

}

?>
