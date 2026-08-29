<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();

    if (isset($_POST['kt_submit_button'])) {

        $user_name = $_POST['user_name'];
        $login_password = $_POST['password'];

        if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {

            header("Location: ../logout.php");
            exit(); // Ensure script stops execution after redirection
        }

        // instantiate LoginContr class   
        include "autoloader.inc.php";
        $login = new LoginContr($user_name, $login_password);

        // Running error handler 
        print $login->LoginAdmin();
    }
}


