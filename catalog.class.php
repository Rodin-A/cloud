<?php
require_once "db.class.php";
require_once "templates.class.php";
require_once "utils.class.php";

if (!isset($_LOADED)) {
    header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
    header("Status: 404 Not Found");
    die(); 
}

class catalog{
        
    private $files = array();
    private $dirs = array();
    private $files_cnt = 0;
    private $dirs_cnt = 0;
    private $total_size = 0;
    
    /* The constructor */
    public function __construct($path,$curdir) {
        $cdir = scandir($path);
        $df = "d.m.Y H:i";
        if($_SESSION['can_share']) {
            $res = DB::prepare("SELECT shares.token, users_table.can_write FROM shares LEFT JOIN users_table ON shares.account_id = users_table.unic_id WHERE shares.path=? AND shares.owner_id=? LIMIT 1;");
        }
        foreach ($cdir as $value) {
            if (!in_array($value,array(".",".."))) {
                $cpath = $path . $value;
                $vpath =  mb_convert_encoding($curdir.$value, BROWSER_CP, FS_CP);
                $name = mb_convert_encoding($value, BROWSER_CP, FS_CP);
                $title = " ";
                if (mb_strlen($name, BROWSER_CP) >= 50) {
                    $title = " title=\"".htmlspecialchars($name)."\" ";
                    $name = mb_substr($name,0,47,BROWSER_CP)."...";
                }
                $size = 0;
                $token = '';
                $share_can_write = false;
                if ($_SESSION['can_share']) {
                    $res->execute(array($cpath, $_SESSION['unic_id'])) or die('Query failed!');
                    if ($res->rowCount() > 0) {
                        $row = $res->fetchObject();
                        $token = $row->token;
                        if ($row->can_write == '1') $share_can_write = true;                        
                    }
                }
                if (is_dir($cpath)) {
                    $size = self::dir_size($cpath);
                    $this->dirs[] = array(
                        "Name" => "[".$name."]",
                        "Path" => $vpath,
                        "Size" => $size,
                        "HRSize" => utils::BytesToHumanReadStr($size),
                        "Date" => date($df, filemtime($cpath)),
                        "Title" => $title,
                        "Token" => $token,
                        "write" => $share_can_write
                    );
                    $this->dirs_cnt++;
                } else {
                    $ext = pathinfo($cpath, PATHINFO_EXTENSION);
                    if (strlen($ext) < 1)
                        $ext = "file";
                    $ext = strtolower($ext);
                    $size = filesize($cpath);
                    $this->files[] = array(
                        "Name" => $name,
                        "Path" => $vpath,
                        "Ext"  => $ext,
                        "Size" => $size,
                        "HRSize" => utils::BytesToHumanReadStr($size),
                        "Date" => date($df, filemtime($cpath)),
                        "Title" => $title,
                        "Token" => $token
                    );
                    $this->files_cnt++;
                }
                $this->total_size += $size;
            }
        }
        sort($this->dirs);
        sort($this->files);    
    }
            
    public function __toString() {    
        // The string we return is outputted by the echo statement

        $html = '';
        foreach ($this->dirs as $value) {
            $html .= self::GetTRdir($value);
        }
        
        foreach ($this->files as $value) {
            $html .= self::GetTRfile($value);
        }

        return templates::HtmlByTemplate("catalog", array(
            'TR' => $html,
            'total_size' => utils::BytesToHumanReadStr($this->total_size)
        ), array(
            'write' => $_SESSION['is_writable']
        ));
    }
    
    private static function GetTRfile($file) {
        $share = '<div class="share" title="Получить ссылку для скачивания"></div>';
        if($file['Token']) $share = '<div id="'.$file['Token'].'" class="share live" title="Получить ссылку для скачивания"></div>';
        if (!$_SESSION['can_share']) $share = '';

        return templates::HtmlByTemplate("tr_file", array(
            'share' => $share,
            'title' => $file['Title'],
            'name' => $file['Name'],
            'path' => htmlspecialchars( $file['Path'] ),
            'file_ext' => $file['Ext'],
            'size' => $file['Size'],
            'hr_size' => $file['HRSize'],
            'date' => $file['Date']
        ));
    }

    private static function GetTRdir($dir) {
        $share = '<div class="share" title="Получить ссылку для скачивания"></div>';
        if($dir['Token']) {
            $class = $dir['write'] ? 'share live write' : 'share live';
            $share = '<div id="'.$dir['Token'].'" class="'.$class.'" title="Получить ссылку для скачивания"></div>';
        }
        if (!$_SESSION['can_share']) $share = '';

        return templates::HtmlByTemplate("tr_dir", array(
            'share' => $share,
            'title' => $dir['Title'],
            'name' => $dir['Name'],
            'path' => htmlspecialchars( $dir['Path'] ),
            'size' => $dir['Size'],
            'hr_size' => $dir['HRSize'],
            'date' => $dir['Date']
        ));
    }
    
    private static function dir_size($dir) {
      
       $result = 0;

       $cdir = scandir($dir);
       foreach ($cdir as $value) {
           $cpath = $dir . DIRECTORY_SEPARATOR . $value;
           if (!in_array($value,array(".",".."))) {
               if (is_dir($cpath)) {
                   $result += self::dir_size($cpath);
               } else {
                   $result += filesize($cpath);
               }
           }
       }
       return $result;
    } 

} // closing the class definition  

