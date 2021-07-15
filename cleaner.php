<?php
//Этот скрипт прописать в root crontab
if (isset($_SERVER['HTTP_HOST'])) die ('Запуск только из консоли php -q %script_name%');
require_once "db.class.php";
require_once "log.class.php";
set_time_limit(3600);    // Максимальное время выполнения скрипта 1 час

function removeDirRec($dir) {
  if (strpos(strrev($dir), DIRECTORY_SEPARATOR) !== 0)
    $dir .= DIRECTORY_SEPARATOR;
  $cdir = scandir($dir);
  foreach ($cdir as $value) {
     $cpath = $dir . $value;
     if (!in_array($value,array(".",".."))) {
         echo "=== $cpath\n";
         if (is_dir($cpath)) {
             removeDirRec($cpath);
         } else {
             unlink($cpath);
         }
     }
  }
  return rmdir($dir);
}

function GetHomeDir($basepath) {
if (strpos(strrev($basepath), DIRECTORY_SEPARATOR) !== 0) // Добавляем слэш в конце
    $basepath .= DIRECTORY_SEPARATOR;
$basepath = realpath($basepath);
if ($basepath === false || strpos($basepath, HI_DIR) !== 0) {
    error_log("Cloud cleaner security error. Attempting to exceed the allowable directory. Path: ".$basepath, 1,
    CONFIG_ADM_MAIL);
    die('Access Denied');
}
if (strpos(strrev($basepath), DIRECTORY_SEPARATOR) !== 0) // Добавляем слэш в конце
    $basepath .= DIRECTORY_SEPARATOR;
return $basepath;
}

$query = "SELECT `unic_id`, `homedir`, `kill_date` FROM `users_table` WHERE `kill_date` is not null;";
$result = DB::query($query) or die ("Query failed: " . DB::error());
while ($row = $result->fetchObject()) {
  if (time() > strtotime($row->kill_date)) {
    $dir = GetHomeDir($row->homedir);
    if (!is_dir($dir)) {
        error_log("Cloud cleaner. Trouble in cleaner.php\n$dir not directory\nunic_id = $row->unic_id", 1,
            CONFIG_ADM_MAIL);
    } else {
        if (!removeDirRec($dir)) {
            error_log("Cloud cleaner. Trouble in cleaner.php\nCan't delete $dir\nunic_id = $row->unic_id", 1,
                CONFIG_ADM_MAIL);
        } else {
            $query = "DELETE FROM `users_table` WHERE `users_table`.`unic_id` = $row->unic_id;";
            DB::exec($query);
            LOG::write("[KILL-DIR] $dir");
        }
    }
  }
}

$query = "SELECT `share_id`,`path`,`path`,`kill_date` FROM `shares` WHERE `kill_date` is not null;";
$result = DB::query($query) or die ("Query failed: " . DB::error());
while ($row = $result->fetchObject()) {
    if (time() > strtotime($row->kill_date)) {
        $path = $row->path;
        if(!file_exists($path)) {
            error_log("Cloud cleaner. Trouble in cleaner.php\nFile not exist $path\nshare_id = $row->share_id", 1,
                CONFIG_ADM_MAIL);
        } else {
            if(!unlink($path)) {
                error_log("Cloud cleaner. Trouble in cleaner.php\nCan't delete $path\nshare_id = $row->share_id", 1,
                    CONFIG_ADM_MAIL);
            } else {
                $query = "DELETE FROM `shares` WHERE `shares`.`share_id` = $row->share_id;";
                DB::exec($query);
                LOG::write("[KILL-FILE] $path");
            }
        }
    }
}
