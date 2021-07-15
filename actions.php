<?php
require_once "config.php";
require_once "log.class.php";
require_once "access.class.php";
require_once "shares.class.php";
require_once "accounts.class.php";
session_name(PHP_SID);
session_start();

$US = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
if (!isset($_SESSION['unic_id']) || strcmp($_SESSION['user_stamp'], $US) !==0) {
    session_destroy();
    exit;
}

$action = (isset($_POST['action'])) ? $_POST['action'] : ''; 
switch($action) {
    case "add":
        CreateFolder();
        break;
    case "del":
        delete();
        break;
    case "share":
        share();
        break;
    case "share-del":
        delete_share();
        break;
    case "share-write":
        share_write();
        break;
    case "share-get-date":
        share_getDate();
        break;
    case "accounts-get-id":
        Accounts_getId();
        break;
    case "account-create":
        Account_Create();
        break;
    case "account-get":
        Account_Get();
        break;
    case "account-del":
        Account_Del();
        break;
    case "account-mod":
        Account_Mod();
        break;
}

function Accounts_getId() {
    if(!access::isAdmin()) return;
    if (!isset($_POST['paths'])) return;
    echo json_encode(accounts::GetIdsByPathArray( $_POST['paths'], access::GetHomeDir() ));
}

function Account_Create() {
    if(!access::isAdmin()) return;
    if (!isset($_POST['path'])) return;
    $path = $_POST['path'];
    $path = mb_convert_encoding($path,FS_CP,BROWSER_CP);
    $path = access::GetAbsolutePath($path, false);
    echo accounts::Create($path);    
}

function Account_Get() {
    if(!access::isAdmin()) return;
    if (!isset($_POST['id'])) return;
    echo json_encode( accounts::GetInfo( $_POST['id'] ));
}

function Account_Del() {
    if(!access::isAdmin()) return;
    if (!isset($_POST['id'])) return;
    accounts::Delete( $_POST['id'] );
}

function Account_Mod() {
    if(!access::isAdmin()) return;
    if (!isset($_POST['data'])) return;
    echo accounts::Modify( $_POST['data'] );
}

function CreateFolder() {
    if (!isset($_POST['name'])) return;
    if (!$_SESSION['is_writable']) {
        LOG::write("[SECURITY] No write access to create folder ".$_POST['name']);
        return;
    }
    
    $path = access::GetAbsolutePath($_SESSION['curdir'], false);
    $name = trim($_POST['name']);
    $name = mb_convert_encoding($name,FS_CP,BROWSER_CP);
     
    $reg = '/(\/|\\|\^|\*|\>|\<|\:|\?|\|)|(^\.)/';
    if (preg_match($reg,$name)) return;
    if (strlen($name) > 50) return;
    
    $path = $path.$name;
   
    if(file_exists($path)) return;
    mkdir($path, CHMOD);
    chmod($path, CHMOD);
            
    LOG::write("[MKDIR] $path");
}

function share() { 
    if(!$_SESSION['can_share']) return;
    if (!isset($_POST['path'])) return;
    if (!isset($_POST['is_file'])) return;
    $is_file = filter_var($_POST['is_file'], FILTER_VALIDATE_BOOLEAN);
    if (!is_bool($is_file)) return;   
    $path = $_POST['path'];
    $path = mb_convert_encoding($path,FS_CP,BROWSER_CP);
    $path = access::GetAbsolutePath($path, $is_file);
    if (shares::is_shared($path)) return;
    if($is_file) {
        $token = shares::CreateFileShare($path);
    } else {
        $token = shares::CreateDirShare($path);        
    }
    echo $token;
}

function delete_share() {
    if(!$_SESSION['can_share']) return;
    if (!isset($_POST['token'])) return;
    shares::DeleteShareByToken($_POST['token']);        
}

function share_write() {
    if(!$_SESSION['can_share']) return;
    if (!isset($_POST['token'])) return;
    if (!isset($_POST['write'])) return;
    $write = ($_POST['write'] === 'true');
    if (shares::ToggleWriteAccess($_POST['token'],$write)) {
        echo 'ok';    
    } else {
        echo 'err';
    }
}

function share_getDate() {
    if(!$_SESSION['can_share']) return;
    if (!isset($_POST['token'])) return;
    echo shares::GetKillDateByToken($_POST['token']);
}

function delete() {
    if (!isset($_POST['data'])) return;
    if (!$_SESSION['is_writable']) {
        LOG::write("[SECURITY] No write access to delete ".$_POST['data']);
        return;
    }
    $json = json_decode($_POST['data']);
    if (json_last_error() !== JSON_ERROR_NONE) return;    
    $files = $json->files;
    $folders = $json->dirs;
                   
    if (is_array($folders)) {
        foreach ($folders as $value) {
            $value = mb_convert_encoding($value,FS_CP,BROWSER_CP);
            $path = access::GetAbsolutePath($value,false);
            $it = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
            $fls = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach($fls as $file) {
                $p = $file->getRealPath();
                if ($file->isDir()){
                    rmdir($p);
                } else {
                    unlink($p);
                }
                shares::DeleteShareByPath($p);
                LOG::write("[DEL] $p");
            }
            rmdir($path);
            shares::DeleteShareByPath($path);
            LOG::write("[DEL] $path");
        }
    }
    if (is_array($files)) {
        foreach ($files as $value) {
            $value = mb_convert_encoding($value,FS_CP,BROWSER_CP);
            $path = access::GetAbsolutePath($value,true);
            unlink($path);
            shares::DeleteShareByPath($path);
            LOG::write("[DEL] $path");
        }
    }
}

