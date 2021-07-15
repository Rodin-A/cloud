<?php
require_once "config.php";
require_once "db.class.php";
require_once "log.class.php";
require_once "access.class.php";
require_once "utils.class.php";
session_name(PHP_SID);
session_start();


$US = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
if (!isset($_SESSION['unic_id']) || strcmp($_SESSION['user_stamp'], $US) !==0) {
    session_destroy();
    exit;
}

function Error($msg) {
    echo json_encode(array( 'err' => true, 'msg' => $msg ));
    exit(0);
}

$action = (isset($_GET['action'])) ? $_GET['action'] : ''; 
switch($action) {
    case "start":
        Start();
        break;
    case "upload":
        Upload();
        break;
    case "success":
        Finish();
        break;
}

function Finish() {
    $upl_id = (isset($_POST['id'])) ? $_POST['id'] : '';
    if (!is_numeric($upl_id)) Error("Wrong upl_id");
    $source = TMP_DIR."{$_SESSION['unic_id']}-$upl_id";
    if (!file_exists($source)) Error("Source file not exist!");
    $res = DB::prepare("SELECT * FROM uploads WHERE upl_id=? AND owner_id=?;");
    $res->execute(array($upl_id,$_SESSION['unic_id'])) or Error('Query failed!');
    if ($res->rowCount() != 1) Error("Wrong data!");
    $row = $res->fetchObject();
    $dest = $row->dir.$row->name;
    $dest = mb_convert_encoding($dest,FS_CP,BROWSER_CP);
    $i = 1;
    while (file_exists($dest)) {
        $dest = $row->dir.$i."_".$row->name;
        $i++;
    }
    //if (!move_uploaded_file($source, $dest)) Error("Can't move file!");
    if (system("mv $source ".escapeshellarg($dest)) > 0) {
        LOG::write("[ERROR] Upload. Can't move file! $dest");
        Error("Can't move file!");
    }
    LOG::write("[UPLOAD] $dest");
    DB::exec("DELETE FROM uploads WHERE upl_id=".$row->upl_id);
    echo "OK";
}

function Upload() {
    $upl_id = (isset($_GET['id'])) ? $_GET['id'] : '';
    $offset = (isset($_GET['offset'])) ? $_GET['offset'] : '';
    if (!is_numeric($upl_id)) Error("Wrong upl_id");
    if (!is_numeric($offset)) Error("Wrong offset");
    if( isset($_FILES['CHUNK']) and !$_FILES['CHUNK']['error'] ){
        $name = TMP_DIR."{$_SESSION['unic_id']}-$upl_id";
        $chink = file_get_contents($_FILES['CHUNK']['tmp_name']);
        $f = fopen($name, 'cb');
        fseek($f, $offset, SEEK_SET);
        fwrite($f, $chink);
        fclose($f);        
    } else {
        Error("Chink upload error!");
    }        
}

function Start() {
    if (!$_SESSION['is_writable']) {
        LOG::write("[SECURITY] Attempting to upload file without write access.");
        Error("No write access!");
    }
    $dir = access::GetAbsolutePath($_SESSION['curdir'], false);
    if (!is_writable($dir)) {
        LOG::write("[ERROR] Can't write to $dir");
        Error("No write access!");
    }
    if(!file_exists(TMP_DIR)) {
        if (!mkdir(TMP_DIR, CHMOD)) {
            LOG::write("[ERROR] Can't create ".TMP_DIR);
            Error("No write access!");    
        }
    }
    if (!is_writable(TMP_DIR)) {
        LOG::write("[ERROR] Can't write to ".TMP_DIR);
        Error("No write access!");        
    }
    
    if (!isset($_POST['name'])) Error("Name not set!");    
    if (!isset($_POST['size'])) Error("Size not set!");
    
    $name = (isset($_POST['name'])) ? $_POST['name'] : '';
    $size = (isset($_POST['size'])) ? $_POST['size'] : '';
    
    if (!is_numeric($size)) Error("Size not number!"); 
    
    $free = disk_free_space(TMP_DIR);
    if ($size > $free) {
        LOG::write("[ERROR] Can't upload file. No free space in TMP_DIR! Size=".utils::BytesToHumanReadStr($size)." Free=".utils::BytesToHumanReadStr($free));
        Error("No free space!");
    }

    if (strlen($name) > 100) {
        LOG::write("[SECURITY] Attempting to upload file with long name [$name]");
        Error("Слишком длинное имя(более 100 символов)");
    }

    $reg = '/(\/|\\|\^|\*|\>|\<|\:|\?|\|)|(^\.)/';
    if (preg_match($reg,$name)) {
        LOG::write("[SECURITY] Attempting to upload file with illegal name [$name]");
        Error("Имя файла содержит недопустимые символы");
    }

    DB::beginTransaction();
    $res = DB::prepare("INSERT INTO uploads(owner_id,name,dir) VALUES (?,?,?);");
    $res->execute(array($_SESSION['unic_id'],$name,$dir)) or Error('Query failed!');
    $upl_id = DB::lastInsertId();
    DB::commit();
    LOG::write("[UPLOAD] Start. File: ($name) Size: ".utils::BytesToHumanReadStr($size));
    echo json_encode(array( 'id' => $upl_id, 'err' => false ));
}

