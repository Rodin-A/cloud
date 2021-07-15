<?php
require_once "config.php";
require_once "log.class.php";
require_once "access.class.php";
require_once "shares.class.php";
require_once "lib/ZipStreamer/ZipStreamer.php";
require_once "templates.class.php";
require_once "utils.class.php";
session_name(PHP_SID);
session_start();

$token = null;

if (isset($_GET['token'])) {
    global $token;
    $token = $_GET['token'];
    if(strlen($token) != 16) die('File not found!');
    $path = shares::GetPathByToken($token);
    if (!file_exists($path)) {
        echo templates::HtmlByTemplate("get_file_not_found",array());
        exit(0);
    }
    if (isset($_GET['download'])) {
        file_download( shares::GetPathByToken($token) );
    } else {
        shares::ProlongFileLifetimeByToken($token);
        $info = pathinfo($path);
        //foreach ($_SERVER as $parm => $value)  echo "$parm = '$value'\n";
        echo templates::HtmlByTemplate("get_file",array(
            "file_name" => $info['basename'],
            "file_size" => utils::BytesToHumanReadStr(filesize($path)),
            "file_ext" => $info['extension'],
            "file_date" => shares::GetKillDateByToken($token),
            "href" => $_SERVER['REQUEST_URI'],
            "qr" => $_SERVER['HTTP_HOST']."/$token"
        ));
        exit(0);
    }
}

$US = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
if (!isset($_SESSION['unic_id']) || strcmp($_SESSION['user_stamp'], $US) !==0) {
    session_destroy();
    header("Location: /login.php");
    exit;
}

function GetFiles($dir) {
   $result = array();
   $cdir = scandir($dir);
   foreach ($cdir as $value) {
       $cpath = $dir . DIRECTORY_SEPARATOR . $value;
       if (!in_array($value,array(".",".."))) {
           if (is_dir($cpath)) {
               $result = array_merge($result, GetFiles($cpath));
           } else {
               $result[] = $cpath;
           }
       }
   }
   return $result;
}

function GetCurFile() {
    $LC = "";
    if (!isset($_GET['file']))
        return $LC;
    $LC = $_GET['file'];
    $LC = mb_convert_encoding($LC,FS_CP,BROWSER_CP);
    if (strpos($LC, '/') === 0) // Убираем слэш в начале
            $LC = substr($LC, 1);

    if (strlen($LC) > 0) {
        if (!access::PathIsCorrect($LC))
            return "";
        return $LC;            
    }
    
    return "";
}

function file_download($file) {
  if (file_exists($file)) {
    // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
    // если этого не сделать файл будет читаться в память полностью!
    if (ob_get_level()) {
      ob_end_clean();
    }
    $fn = basename($file);
    $disposition = "filename=\"".mb_convert_encoding($fn,'ASCII',FS_CP)."\"";
    $fn = mb_convert_encoding($fn,'UTF-8',FS_CP);
    $disposition .= "; filename*=utf-8''".rawurlencode($fn);

    global $token;
    LOG::write("[Download] $file", $token);
    
    // заставляем браузер показать окно сохранения файла
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; '.$disposition);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    // читаем файл и отправляем его пользователю
    readfile($file);
    exit;
  } else {
      die('File not found!');
  }
}

if (isset($_POST['data'])) {
    $json = json_decode($_POST['data']);
    if (json_last_error() !== JSON_ERROR_NONE)
        die('wrong data!');    
    $files = $json->files;
    $folders = $json->dirs;
    $file_list = array();
    $HD = access::GetHomeDir();
    $zipName = 'LNCM'.date("d.m.Y", time());
    
    if (is_array($folders)) {
        foreach ($folders as $value) {
            $value = mb_convert_encoding($value,FS_CP,BROWSER_CP);
            if (access::PathIsCorrect($value)) {
                $cpath = realpath($HD.$value);
                $file_list = array_merge($file_list, GetFiles($cpath));                    
            }
        }
    }
    if (is_array($files)) {
        foreach ($files as $value) {
            $value = mb_convert_encoding($value,FS_CP,BROWSER_CP);
            if (access::PathIsCorrect($value)) {
                $cpath = realpath($HD.$value);
                if (is_file($cpath))
                    $file_list[] = $cpath;               
            }                        
        }
    }
    
    if (count($file_list) < 1)
        die('Files not found!');
        
    if (count($file_list) == 1)
        file_download( $file_list[0] );
        
    $PD = explode('/',$file_list[0]);
    foreach ($file_list as $value) {
        $PD = array_intersect($PD, explode('/',$value));
    }
    
    $PD = array_diff($PD, explode('/',$HD));
    
    $size = 0;
    foreach ($file_list as $file) {
        $size += filesize($file);
        if ($size > MAX_ZIP_SIZE) {
            die('Размер выделенных файлов превышает допустимый! ('.MAX_ZIP_SIZE / pow(1024, 3).' Gb) <input type="button" name="Back" value="Back" onclick="history.back();" />');
        } 
    }
    
    if (count($PD)>0) {
        $zipName = mb_convert_encoding(end($PD),BROWSER_CP,FS_CP);       
    }
    
    $opt = array(
      'zip64' => false,
      'comment' => 'Downloaded from cloud.niihimmash.com '.date("d.m.Y H:i", time())
    );
        
    # create new zip stream object
    $zip = new \ZipStreamer\ZipStreamer($opt);
    $zip->sendHeaders($zipName.'.zip');

    # add same files again without a folder
    foreach ($file_list as $file) {
        LOG::write("[Zip] $file");
        $fn = str_replace($HD,'',$file);
        $fn = mb_convert_encoding($fn, 'UTF-8', FS_CP);
        $fh = fopen($file,"r");
        $zip->addFileFromStream($fh,$fn);
        fclose($fh);
    }

    # finish archive
    $zip->finalize();

} else {
    file_download( access::GetAbsolutePath(GetCurFile(),true) );
}

