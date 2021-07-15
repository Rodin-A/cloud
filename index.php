<?php
require_once "db.class.php";
require_once "access.class.php";
require_once "templates.class.php";
session_name(PHP_SID);
session_start();

$US = md5($_SERVER['REMOTE_ADDR'] . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ""));
if (!isset($_SESSION['unic_id']) || strcmp($_SESSION['user_stamp'], $US) !==0) {
    session_destroy();
    $QS = urldecode( $_SERVER['QUERY_STRING'] );
    header("Location: /login.php".(strlen($QS) > 0 ? "?$QS" : ""));
    exit;
}

$query = "SELECT * FROM users_table WHERE unic_id=? LIMIT 1;";
$res = DB::prepare($query);
$res->execute(array($_SESSION['unic_id']))  or die('Query failed!');
if ($res->rowCount() != 1) {
    unset($_SESSION['unic_id']);
    session_destroy();
    die('Срок действия Вашего аккаунта истек! <a href="./">'.$_SERVER['HTTP_HOST'].'</a>');
} else {
    $row = $res->fetchObject();
    $_SESSION['homedir'] = $row->homedir;
    $_SESSION['is_writable'] = ($row->can_write == 1) ? is_writable($row->homedir) : false;
    $_SESSION['can_share'] = ($row->can_share == 1);
    $_SESSION['kill_date'] = is_null($row->kill_date) ? 'Бессрочный' : date('d.m.Y', strtotime($row->kill_date));
} 

function GetCurDir() {
    $LC = "";
    if (!isset($_GET['dir']))
        return $LC;
    $LC = $_GET['dir'];
    $LC = mb_convert_encoding($LC,FS_CP,BROWSER_CP);
    if (strpos($LC, '/') === 0) // Убираем слэш в начале
            $LC = substr($LC, 1);
    
    if (strlen($LC) > 0) {
        if (strpos(strrev($LC), '/') !== 0) // Добавляем слэш в конце
            $LC .= '/';
        if (!access::PathIsCorrect($LC))
            return "";
        return $LC;            
    }
    
    return "";
}

$_LOADED = true;
setcookie(session_name(),session_id(),time()+60*60*24*30);
require_once "breadcrumb.class.php";
require_once "catalog.class.php";

$CD = GetCurDir();
$_SESSION['curdir'] = $CD;
$crumb = new breadcrumb($CD);
$cat = new catalog(access::GetAbsolutePath($CD,false),$CD);

$JS_Share = ($_SESSION['can_share'] ? '<script type="text/javascript" src="/js/share.js"></script>' : '');
if ((access::isAdmin())&&($_SESSION['can_share'])) $JS_Share = '<script type="text/javascript" src="/js/share-adm.js"></script>';

echo templates::HtmlByTemplate("index",array(
    'host' => $_SERVER['HTTP_HOST'],
    'js_share' => $JS_Share,
    'date' => $_SESSION['kill_date'],
    'crumb' => $crumb,
    'cat' => $cat
), array(
    'write' => $_SESSION['is_writable']
));