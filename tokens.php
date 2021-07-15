<?php
require_once "shares.class.php";
require_once "guard.class.php";
require_once "templates.class.php";

session_name(PHP_SID);
session_start();

function Redirect() {
    if (guard::inBan()) die ("Превышено максимальное количесво попыток входа. Попробуйте войти через 1 час.");
    if (!isset($_GET['token'])) return;
    $token = $_GET['token'];
    if(strlen($token) != 16) return;
    if (!shares::TokenExist($token)) {
        LOG::write("TOKEN NOT FOUND - $token");
        guard::LoginFailed();
        echo templates::HtmlByTemplate("get_file_not_found",array());
        exit;
    }
    $href = '';
    LOG::write("ACCEPT TOKEN - $token");
    if(shares::is_account($token)) {
        if (isset($_SESSION['unic_id'])) {
            LOG::write("Logout!");
            unset($_SESSION['unic_id']);
            session_destroy();
        }
        $href = "/login.php?token=$token";
        //header("Location: /login.php?token=$token");
    } else {
        $href = "/get.php?token=$token";
        //header("Location: /get.php?token=$token");
    }
    echo templates::HtmlByTemplate("redirect",array("href" => $href));
    exit;   
}

Redirect();
die('Неверная ссылка или срок действия Вашего аккаунта истек! <a href="./">'.$_SERVER['HTTP_HOST'].'</a>');
