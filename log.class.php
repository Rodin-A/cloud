<?php
require_once ("config.php");
class LOG
{
    private $fHandle;    // file handler for logging
    private static $logger;     // self handle

    private function __construct() {
        $this->fHandle = fopen(LOG_FILE,'ab');
    }

    function __destruct() {
        if($this->fHandle) fclose($this->fHandle);
    }

    private function do_write($str) {
        fwrite($this->fHandle,$str);
    }
 
    static public function write($str, $login = null) {
        if(!self::$logger) {
            self::$logger = new self();
        }

        if (!$login) $login = isset($_SESSION['login']) ? $_SESSION['login'] : 'NULL';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'NULL';
        $date = date('d.m.y H:i:s');
        self::$logger->do_write("$date - $ip - $login - $str\n");
    }
}
