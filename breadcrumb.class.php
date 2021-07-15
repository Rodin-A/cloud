<?php
if (!isset($_LOADED)) {
    header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
    header("Status: 404 Not Found");
    die(); 
}

class breadcrumb{
    
    private $path;
    private $crumbs = array();
    private $length = 0;
  
    /* The constructor */
    public function __construct($path) {
        $this->path = mb_convert_encoding($path, BROWSER_CP, FS_CP);
        $crumbs = explode("/", $this->path);
        $tpath = "";
        foreach ($crumbs as $value) {
            if (mb_strlen($value)>0) {
                $tpath .= $value."/";
                $title = " ";
                if (mb_strlen($value, BROWSER_CP) >= 40) {
                    $title = " title=\"".htmlspecialchars($value)."\" ";
                    $value = mb_substr($value,0,37,BROWSER_CP)."...";
                }
                $this->crumbs[] = array(
                    "Name" => $value,
                    "Path" => $tpath,
                    "Title" => $title
                );
                $this->length += mb_strlen($value, BROWSER_CP);
            }
        }
    }
            
    public function __toString() {    
        // The string we return is outputted by the echo statement
        $html = '';
        $max_len = 62;
        $path = access::GetHomeDir() . mb_convert_encoding($this->path,FS_CP,BROWSER_CP);
        if (($_SESSION['is_writable'])&&(is_writable( $path ))) $max_len = 50;
        
        $name_len = 0;
        
        for($i=count($this->crumbs)-1;$i>=0;$i--) {
            $cur = $this->crumbs[$i];
            $cur_name_len = mb_strlen($cur['Name'], BROWSER_CP);
            if($name_len + $cur_name_len > $max_len) {
                $cur['Name'] = '...';
                $html = $this->GetCrumb($cur).$html;
                break;                
            } else {
                $html = $this->GetCrumb($cur).$html;
            }
            $name_len += $cur_name_len;
        }          

        return templates::HtmlByTemplate("breadcrumbs", array(
            'crumbs' => $html
        ), array(
            'write' => $_SESSION['is_writable'] && is_writable( $path )
        ));
    }
    
    private function GetCrumb($crumb) {
       return templates::HtmlByTemplate("crumb", array(
           'title' => $crumb['Title'],
           'path' => htmlspecialchars($crumb['Path']),
           'name' => $crumb['Name']
       ), array(
           'last' => mb_strlen($this->path, 'UTF-8') === mb_strlen($crumb['Path'], 'UTF-8')
       ));
    }        
} // closing the class definition  
