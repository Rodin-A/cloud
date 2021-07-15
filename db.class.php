<?php
require_once ("config.php");

class DB
{
    static private $db;    // db handler
 
    static private function init() {
        if (self::$db) return;
        try {
            self::$db = new PDO(PDO_DSN.";dbname=".MYSQL_BASE.";charset=utf8",
                MYSQL_LOGIN, MYSQL_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES utf8'));
            
            //self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //self::$db->exec("SET NAMES utf8"); -- не нужно если сработает PDO::MYSQL_ATTR_INIT_COMMAND?
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            die('DB connection error!');
        }
    }
 
    static public function query($sql) {
        self::init();
        return self::$db->query($sql);
    }
 
    static public function exec($sql) {
        self::init();
        return self::$db->exec($sql);
    }

    static public function beginTransaction() {
        self::init();
        self::$db->beginTransaction();
    }

    static public function commit() {
        self::init();
        self::$db->commit();
    }

    static public function rollBack() {
        self::init();
        self::$db->rollBack();
    }
 
    // one column result
    static public function column($sql) {
        self::init();
        return self::$db->query($sql)->fetchColumn();
    }
 
    // intval one column result
    static public function columnInt($sql) {
        self::init();
        return intval(self::$db->query($sql)->fetchColumn());
    }
 
    static public function prepare($sql) {
        self::init();
        return self::$db->prepare($sql);
    }
 
    static public function lastInsertId() {
        self::init();
        return self::$db->lastInsertId();
    }
 
    // prepares and execute one SQL
    static public function execute($sql, $ar) {
        self::init();
        return self::$db->prepare($sql)->execute($ar);
    }
 
    // returns error info on db handler (not stmt handler)
    static public function error() {
        self::init();
        $ar = self::$db->errorInfo();
        return $ar[2] . ' (' . $ar[1] . '/' . $ar[0] . ')';
    }
 
    // returns one row fetched in FETCH_ASSOC mode
    static public function fetchAssoc($sql) {
        self::init();
        return self::$db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }
 
    // returns one row fetched in FETCH_NUM mode
    static public function fetchNum($sql) {
        self::init();
        return self::$db->query($sql)->fetch(PDO::FETCH_NUM);
    }
}  
