<?php

class utils
{
    static function BytesToHumanReadStr($bytes) {
        if (!$bytes) return 0;
        $bytes = floatval($bytes);
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => 1099511627776//pow(1024, 4)
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => 1073741824//pow(1024, 3)
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => 1048576//pow(1024, 2)
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1
            )
        );

        $result = '';
        foreach($arBytes as $arItem)
        {
            if($bytes >= $arItem["VALUE"])
            {
                $result = $bytes / $arItem["VALUE"];
                $result = str_replace(".", "," , strval(round($result, 2)))." ".$arItem["UNIT"];
                break;
            }
        }
        return $result;
    }
}