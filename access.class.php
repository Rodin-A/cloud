<?php
require_once "config.php";
require_once "log.class.php";

class access{
    static function GetHomeDir() {
        $basepath = $_SESSION['homedir'];
        if (strpos(strrev($basepath), '/') !== 0) // Добавляем слэш в конце
            $basepath .= '/';
        $basepath = realpath($basepath);
        if ($basepath === false || strpos($basepath, HI_DIR) !== 0) {
            LOG::write("[SECURITY] Attempting to exceed the allowable directory. path: $basepath");
            die('Access Denied');
        }
        if (strpos(strrev($basepath), '/') !== 0) // Добавляем слэш в конце
            $basepath .= '/';
        if (!is_dir($basepath)) {
            LOG::write('[SECURITY] Home directory not exist!');   
            die('Home directory not exist!');    
        }
        return $basepath;
    }
    
    static function PathIsCorrect($path) {
        $realBase = self::GetHomeDir();
        $realUserPath = realpath($realBase . $path);
        
        if ($realUserPath === false || strpos($realUserPath, $realBase) !== 0) {
            return false;
        } else {
            return true;
        }
    }
    
    static function GetAbsolutePath($relative_path, $is_file) {
        $HD = access::GetHomeDir();
        $basepath = $HD . $relative_path;
        $basepath = realpath($basepath);
        if(!$is_file && strpos(strrev($basepath), '/') !== 0) $basepath .= '/'; 
        if ($basepath === false || strpos($basepath, $HD) !== 0) {
            LOG::write("[SECURITY] Hacking attempt! path: $relative_path");
            die('Access Denied');
        }

        if ($is_file) {
            if (!is_file($basepath)) {
                LOG::write("[SECURITY] File not exist! path: $basepath");
                die('File not exist!');  
            } 
        } else {
            if (!is_dir($basepath)) {
                LOG::write("[SECURITY] Directory not exist! path: $basepath");
                die('Directory not exist!');
            }
        }
        return $basepath; 
    }
    
    static function isAdmin() {
        if($_SESSION['login'] == 'cloud_admin') return true;
        return false;
    }    
}  

