<?php
require_once "db.class.php";

class guard {
    private static function clean() {
        DB::exec("DELETE FROM fail_logins WHERE timestamp < CURRENT_TIMESTAMP - INTERVAL 1 HOUR");
    }
         
    static function LoginFailed() {
        self::clean();
        $ip = $_SERVER['REMOTE_ADDR'];
        DB::exec("INSERT INTO fail_logins (ip) VALUES ('$ip')");
    }
    
    static function inBan($limit = 15) {
        self::clean();
        $ip = $_SERVER['REMOTE_ADDR'];
        $res = DB::query("SELECT Count(*) FROM fail_logins WHERE ip = '$ip'");
        $count = $res->fetchColumn();
        if ($limit > $count) return false;
        return true;
    }    
}
