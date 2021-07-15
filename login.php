<?php
require_once "guard.class.php";
require_once "db.class.php";
require_once "log.class.php";
require_once "templates.class.php";
session_name(PHP_SID);
session_start();

$wrong = 'style="display:none;"';
$QS = urldecode( $_SERVER['QUERY_STRING'] );
$QS = (strlen($QS) > 0 ? "?$QS" : "");

function CryptData($data) {
    $cipher = "BF-CBC";
    $key = ENCRIPTION_KEY;
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encrypted_data = openssl_encrypt($data, $cipher, $key, $options=0, $iv);
    return base64_encode( $iv . $encrypted_data );    
}

function DecryptData($data) {
    $data = base64_decode( $data );
    $cipher = "BF-CBC";
    $key = ENCRIPTION_KEY;
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = substr($data,0,$ivlen);
    $x = substr($data,$ivlen);
    $dec = openssl_decrypt($x, $cipher, $key, $options=0, $iv);
    return trim($dec);
}

//die (var_dump(DecryptData( CryptData('upload') )));

function slashes(&$el) {
    if (is_array($el))
        foreach($el as $k=>$v)
            slashes($el[$k]);
    else $el = stripslashes($el);
}

if (isset($_GET['logout'])) {// блок обрабатывающий завершение сессии
    if (isset($_SESSION['unic_id'])) {
        LOG::write("Logout!");
        unset($_SESSION['unic_id']);
    }
    session_destroy();
    header('Location: /login.php'); // перезагружаем файл
    exit;
}

if (isset($_SESSION['unic_id']) && !is_null($_SESSION['unic_id'])) { // если юзер уже залогинился
    header("Location: /index.php$QS");
    exit;
}

$login = '';
$token = '';
$in_ban = guard::inBan();
if(!empty($_GET['token']) && empty($_POST)) {
    $token = $_GET['token'];
    if(strlen($token) == 16) {
        $_POST['login'] = $token;
        $_POST['password'] = substr(md5($token.crc32($token)),-20);
        $QS = '';
    }
}
if (!empty($_POST) && !isset($_SESSION['unic_id']) && !$in_ban) {
    if (ini_get('magic_quotes_gpc'))
    {
        slashes($_GET);
        slashes($_POST);
        slashes($_COOKIE);
    }

    $login = (isset($_POST['login'])) ? $_POST['login'] : '';
    $password = (isset($_POST['password'])) ? $_POST['password'] : '';
    
    if(mb_strlen($login, BROWSER_CP) > 30) die('Wrong data!');
    if(mb_strlen($password, BROWSER_CP) > 30) die('Wrong data!');
    
    $login = trim($login);
    $password = trim($password);

    if (strlen($login) > 0 && strlen($password) > 0) {
        $query = "SELECT * FROM users_table WHERE login=? LIMIT 1;";
        $res = DB::prepare($query);
        $res->execute(array($login)) or die('Query failed!');
        if($res->rowCount() > 0) {
            $row = $res->fetchObject();

            if ( strcmp($password, $row->password) == 0 ) {
                $_SESSION['unic_id'] = $row->unic_id;
                $_SESSION['login'] = $row->login;
                $_SESSION['user_stamp'] = md5($_SERVER['REMOTE_ADDR'] . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ""));
                $_SESSION['homedir'] = $row->homedir;
                $kd = is_null($row->kill_date) ? strtotime('2099-01-01') : strtotime($row->kill_date);
                
                if ( strtotime("+".DIR_LIFETIME_PROLONGATION) > $kd ) {
                    $kd = strtotime("+".DIR_LIFETIME);
                    $res = DB::prepare("UPDATE users_table SET kill_date=? WHERE unic_id=?;");
                    $res->execute( array( date("Y-m-d", $kd), $row->unic_id ) );
                    LOG::write( "Extend kill date to ".date("d.m.Y", $kd) ); 
                }
                
                $_SESSION['kill_date'] = is_null($row->kill_date) ? 'Бессрочный' : date("d.m.Y", $kd);
                $_SESSION['is_writable'] = ($row->can_write == 1) ? is_writable($row->homedir) : false;
                $_SESSION['can_share'] = ($row->can_share == 1);
                
                DB::exec("UPDATE `users_table` SET last_login = NOW() WHERE `unic_id` = ".$row->unic_id );
                LOG::write("Login success!");
                if (empty($token)) setcookie("cloud_login", CryptData($login),time()+60*60*24*30);
                header("Location: /$QS");
                exit;
            }
        }
        guard::LoginFailed();
        LOG::write("Login failed!", $login);
    }
    $wrong = ''; 
    

} else {
    if (isset($_COOKIE['cloud_login'])) {
        $login = htmlspecialchars( DecryptData($_COOKIE['cloud_login']) );
    }
}

echo templates::HtmlByTemplate("login", array(
    'host' => $_SERVER['HTTP_HOST'],
    'QS' => $QS,
    'login' => $login,
    'wrong' => $wrong,
), array(
    'in_ban' => $in_ban
));