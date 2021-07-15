<?php
require_once "db.class.php";
require_once "log.class.php";

class accounts {
    private static function GeneratePass() {
        $chars = str_split('ABCDEFGHJKLMNOPQRSTUVWX-+#@$1234567890abcdefghjklmnopqrstuvwx-+#@$1234567890');
        shuffle($chars);
        $pass = '';
        while (strlen($pass) < 12) {
            $pass = $pass . $chars[rand(0, count($chars)-1)];
        }
        return $pass;        
    }
    
    static function GetIdsByPathArray($array,$hd) {
        $res = DB::prepare("SELECT unic_id FROM users_table WHERE homedir = ? LIMIT 1");
        $answer = array();
        foreach ($array as $value) {
            $id = '';
            $res->execute(array($hd.$value)) or die('Query failed!');
            if ($res->rowCount() > 0) $id = $res->fetchColumn();
            $answer[] = array( "path" => $value, "id" => $id );
        }
        return $answer;
    }
    
    static function Create($path) {
        $name = date("d-m-Y-H-i-s", strtotime("now"));
        $kill_date = date("d.m.Y", strtotime("+1 year"));
        if (strpos(strrev($path), '/') === 0) $path = substr($path,0,-1);
        DB::beginTransaction();
        $res = DB::prepare("INSERT INTO users_table(login, password, groupname, uid, gid, homedir, shell, can_write, can_share, kill_date) VALUES (?,?,?,?,?,?,?,1,1,STR_TO_DATE(?,'%d.%m.%Y'));");
        $res->execute(array($name,self::GeneratePass(),PROFTPD_GROUP_NAME,PROFTPD_UID,PROFTPD_GID,$path,PROFTPD_SHELL,$kill_date)) or die('Query failed!');
        $name = DB::lastInsertId();
        DB::commit();
        LOG::write("[ADD ACCOUNT] $name $path");
        return $name;
    }
    
    static function Delete($id) {
        $res = DB::prepare("DELETE FROM users_table WHERE unic_id=?;");
        $res->execute(array($id)) or die('Query failed!');
        LOG::write("[DEL ACCOUNT] $id");
    }
    
    static function Modify($data) {
        $answer = 'ok';
        if (strlen($data['date']) == 0) $data['date'] = null;
        $res = DB::prepare("UPDATE users_table SET login=?, password=?, kill_date=?, can_write=?, can_share=? WHERE unic_id=?;");
        $res->execute(array($data['login'],$data['pass'],$data['date'],$data['write'],$data['share'],$data['id'])) or $answer = $res->errorInfo()[2];
        LOG::write("[MOD ACCOUNT] ".$data['id']);
        return $answer;
    }    
    
    static function GetInfo($id) {
        $res = DB::prepare("SELECT unic_id, login, password, can_write, can_share, kill_date FROM users_table WHERE unic_id = ? LIMIT 1");
        $answer = array();
        $res->execute(array($id)) or die('Query failed!');
        if ($res->rowCount() > 0) {
            $row = $res->fetchObject();
            $answer = array(
                "id" => $row->unic_id, 
                "login" => $row->login,
                "pass" => $row->password,
                "write" => $row->can_write,
                "share" => $row->can_share,
                "date" => (is_null($row->kill_date)) ? '':$row->kill_date
            );   
        }
        return $answer;
    }
}

