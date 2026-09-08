<?php
// Function to initialize and manage session settings
function initialize_session() {
    ini_set('session_use_only_cookies', 1);
    ini_set('session_use_strict_mode', 1);

    session_set_cookie_params([
        'lifetime' => 1800,
        //'domain' => 'products.mycreativecode.com',
        'domain' => 'localhost',
        'path' => '/',
        'secure' => true,
        'httponly' => true
    ]);

    session_start();

date_default_timezone_set('Asia/Dhaka');
$current_time = date("H:i:s");

 if ($current_time < $_SESSION['logintime'] && $current_time > $_SESSION['logouttime']) {
    header("Location: logout.php");
    exit();
}



 if (!isset($_SESSION['admin_access_token'])) {
        header("Location: logout.php?mess=1");
        exit();
    }


$_SESSION['menu_link'] = basename($_SERVER['PHP_SELF']);


}

// Call the function to initialize the session
initialize_session();
if(isset($_GET['mess'])){ $mess = $_GET['mess'] ; }else{ $mess = '';}
if (!isset($_SESSION['csrf_token'])) {  $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }



