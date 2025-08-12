<?php
require_once dirname(__DIR__) . '/config.php';
$page = basename($_SERVER['PHP_SELF']);
$loggedIn = false;



// PROTECTED ROUTES !
if ($page == 'otp.php') {
    if (!isset($_SESSION['showOtpPage']) || $_SESSION['showOtpPage'] == false) {
        header("Location: ./");
        exit;
    }
}
if ($page == "resetPassword.php") {
    if (!isset($_SESSION["change_credentials"]) || $_SESSION["change_credentials"] == false) {
        header("Location: signup.php");
        exit;
    }
}
if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn']) {
    $loggedIn = true;
    if ($page == 'signup.php' || $page == 'login.php' || $page == 'forgotPassword.php' || $page == 'resetPassword.php') {
        header("Location: ./profile.php");
        exit;
    }
} else { // if not logged in
    if ($page == 'profile.php') {
        header("Location: ./login.php");
        exit;
    } 
    if($page == "changePassword.php") {
        header("Location: ./login.php");
        exit;
    }
 }
?>