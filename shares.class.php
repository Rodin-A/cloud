<?php
require_once "db.class.php";
require_once "log.class.php";

class shares {
    private static function GenerateToken() {
        $chars = str_split('ABCDEFGHJKLMNOPQRSTUVWX1234567890abcdefghjklmnopqrstuvwx1234567890');
        shuffle($chars);
        $token = '';
        while (strlen($token) < 16) {
            $token = $token . $chars[rand(0, count($chars)-1)];
        }
        $res = DB::prepare("SELECT share_id FROM shares WHERE token=? LIMIT 1;");
        $res->execute(array($token)) or die('Query failed!');
        if ($res->rowCount() != 0) $token = self::GenerateToken();
        return $token;        
    }
    
    static function CreateFileShare($path) {
        if (!is_file($path)) die('Wrong param');
        $token = self::GenerateToken();
        $kill_date = date("d.m.Y", strtotime("+".FILE_LIFETIME));
        $res = DB::prepare("INSERT INTO shares(token, path, owner_id, kill_date) VALUES (?,?,?,STR_TO_DATE(?,'%d.%m.%Y'));");
        $res->execute(array($token,$path,$_SESSION['unic_id'],$kill_date)) or die('Query failed!');
        LOG::write("[ADD FILE SHARE] $token $path");
        return $token;
    }
    
    static function CreateDirShare($path) {
        if (!is_dir($path)) die('Wrong param');
        $token = self::GenerateToken();
        $kill_date = date("d.m.Y", strtotime("+".DIR_LIFETIME));
        if (strpos(strrev($path), '/') === 0) $path = substr($path,0,-1);
        DB::beginTransaction();
        $res = DB::prepare("INSERT INTO users_table(login, password, groupname, uid, gid, homedir, shell, can_write, can_share, kill_date) VALUES (?,?,?,?,?,?,?,0,0,STR_TO_DATE(?,'%d.%m.%Y'));");
        $res->execute(array($token,substr(md5($token.crc32($token)),-20),PROFTPD_GROUP_NAME,PROFTPD_UID,PROFTPD_GID,$path,PROFTPD_SHELL,$kill_date)) or die('Query failed!');
        $account_id = DB::lastInsertId();       
        DB::exec("INSERT INTO shares(token, path, account_id, owner_id) VALUES ('$token','$path',$account_id,'".$_SESSION['unic_id']."');");
        DB::commit();
        LOG::write("[ADD DIR SHARE] $token $path");
        return $token;
    }
    
    static function GetPathByToken($token) {
        $res = DB::prepare("SELECT path FROM shares WHERE account_id is null AND token=? LIMIT 1;");
        $res->execute(array($token)) or die('Query failed!');
        $path = '';
        if ($res->rowCount() > 0) $path = $res->fetchColumn();
        return $path;     
    }

    static function GetKillDateByToken($token) {
        $res = DB::prepare("SELECT account_id, DATE_FORMAT(kill_date,'%d.%m.%Y') as kill_date FROM shares WHERE token=? LIMIT 1;");
        $res->execute(array($token)) or die('Query failed!');
        if ($res->rowCount() == 0) die('Wrong token');
        $row = $res->fetchObject();
        if (isset($row->account_id)) {
            $res = DB::prepare("SELECT DATE_FORMAT(kill_date,'%d.%m.%Y') as kill_date FROM users_table WHERE unic_id=? LIMIT 1;");
            $res->execute(array($row->account_id)) or die('Query failed!');
            if ($res->rowCount() == 0) die('Wrong token');
            $row = $res->fetchObject();
        }
        return (is_null($row->kill_date)) ? 'Бессрочный':$row->kill_date;
    }

    static function TokenExist($token) {
        $res = DB::prepare("SELECT account_id FROM shares WHERE token=? LIMIT 1;");
        $res->execute(array($token)) or die('Query failed!');
        if ($res->rowCount() < 1) return false;
        return true;
    }
    
    static function is_account($token) {
        $res = DB::prepare("SELECT account_id FROM shares WHERE token=? LIMIT 1;");
        $res->execute(array($token)) or die('Query failed!');
        if ($res->rowCount() == 0) die('Token not found!');
        if (!empty($res->fetchColumn())) return true;
        return false;
    }
    
    static function is_shared($path) {
        if (strpos(strrev($path), '/') === 0) $path = substr($path,0,-1); 
        $res = DB::prepare("SELECT token FROM shares WHERE path=? LIMIT 1;");
        $res->execute(array($path)) or die('Query failed!');
        if ($res->rowCount() > 0) return true;
        return false;
    }
    
    static function DeleteShareByToken($token) {
        $res = DB::prepare("SELECT share_id, path, account_id FROM shares WHERE token=? AND owner_id=? LIMIT 1;");
        $res->execute(array($token, $_SESSION['unic_id'])) or die('Query failed!');
        if ($res->rowCount() > 0) {
            $row = $res->fetchObject();
            DB::beginTransaction();
            DB::exec("DELETE FROM shares WHERE share_id=".$row->share_id.";" );
            if (isset($row->account_id)) DB::exec("DELETE FROM users_table WHERE unic_id=".$row->account_id.";");
            LOG::write("[DEL SHARE] $token ".$row->path);
            DB::commit();
        }
    }
    
    static function DeleteShareByPath($path) {
        if (strpos(strrev($path), '/') === 0) $path = substr($path,0,-1);
        $res = DB::prepare("SELECT share_id, account_id, token FROM shares WHERE path=? AND owner_id=? LIMIT 1;");
        $res->execute(array($path, $_SESSION['unic_id'])) or die('Query failed!');
        if ($res->rowCount() > 0) {
            $row = $res->fetchObject();
            DB::beginTransaction();
            DB::exec("DELETE FROM shares WHERE share_id=".$row->share_id.";" );
            if (isset($row->account_id)) DB::exec("DELETE FROM users_table WHERE unic_id=".$row->account_id.";");
            LOG::write("[DEL SHARE] ".$row->token." $path");
            DB::commit();
        }
    }
    
    static function ToggleWriteAccess($token, $write) {
        if (!self::is_account($token)) return false;
        $res = DB::prepare("SELECT share_id, path, account_id FROM shares WHERE token=? AND owner_id=? LIMIT 1;");
        $res->execute(array($token, $_SESSION['unic_id'])) or die('Query failed!');
        if ($res->rowCount() > 0) {
            $row = $res->fetchObject();
            $write = $write ? '1':'0';
            if (isset($row->account_id)) DB::exec("UPDATE users_table SET can_write='$write' WHERE unic_id=".$row->account_id.";");
            LOG::write("[MOD SHARE] $token Write: $write ".$row->path);
        }
        return true;        
    }

    static function ProlongFileLifetimeByToken($token) {
        $kd = self::GetKillDateByToken($token);
        if($kd == 'Бессрочный') return;
        $kd = strtotime($kd);
        if ( strtotime("+".FILE_LIFETIME_PROLONGATION) > $kd ) {
            $kd = strtotime("+".FILE_LIFETIME);
            $res = DB::prepare("UPDATE shares SET kill_date=? WHERE token=? AND kill_date is not null LIMIT 1");
            $res->execute(array(date("Y-m-d", $kd),$token)) or die('Query failed!');
        }
    }
}
