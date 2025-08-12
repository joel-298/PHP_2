<?php
require_once __DIR__ . '/controllers/0_checkAuth.php';   // This file also includes config file
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $PAGE_TITLE ; ?></title>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Validation Engine CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>validationEngine.jquery.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">
    <!-- Validation Engine JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo JS_URL; ?>jquery.validationEngine.js"></script>
    <script src="<?php echo JS_URL; ?>jquery.validationEngine-en.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>
    <script src="<?php echo JS_URL; ?>loadAjax.js"></script>
    <script>
        $(function () {
            $(document).ajaxSend(function () {
                $('#loader').css('display', 'flex');
            });

            $(document).ajaxStop(function () {
                $('#loader').css('display', 'none');
            });

            // NAVBAR TOGGLE
            $('.custom-toggler').on('click', function () {
                var $nav = $('#navbarNav');
                if ($nav.hasClass('show')) {
                    $nav.stop().slideUp(300, function () {
                        $nav.removeClass('show').removeAttr('style');
                    });
                } else {
                    $nav.stop().slideDown(300, function () {
                        $nav.addClass('show').removeAttr('style');
                    });
                }
            });

            // CONTACT US FORM 
            $(document).ready(function () {
                $('#contactUsForm').validationEngine();
            });

            // LOGOUT
            $('#logoutLink').click(function (e) {
                e.preventDefault() ; 
                $.ajax({
                    url: "<?php echo CONTROLLERS_URL ;?>/5_Logout.php",
                    type: 'POST',
                    success: function (response) {
                        if (response.status === "success") {
                            alert(response.message);
                            window.location.href = 'index.php';
                        }
                    }, 
                    error: function () {
                        alert("Something went wrong during logout.");
                    }
                });
            });
        });
    </script>
</head>

<body class="d-flex flex-column min-vh-100">
    <!-- AJAX Loader -->
    <div id="loader"
        style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; backdrop-filter: blur(8px); background-color: rgba(0, 0, 0, 0.3); z-index: 9999; display: none; align-items: center; justify-content: center;">
        <div
            style=" width: 60px; height: 60px; border: 6px solid #ffffff; border-top: 6px solid transparent; border-radius: 50%; animation: spin 1s linear infinite;">
        </div>
    </div>
    <!------------------>
    <div class="mpage_container">
    <nav class="navbar navbar-expand-lg bg-light m-2 mb-0">
        <div class="container">
            <img src="https://orientaloutsourcing.com/wp-content/uploads/2020/12/logo-dark.png" alt="" id='img'>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <button class="navbar-toggler custom-toggler" type="button" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav"> <!--  use javascript for ids i.e for same page smart scroll feature ! -->
                    <li class="nav-item">
                        <a class="nav-link load_ajax <?php echo ($currentPage == 'index.php') ? "active" : ""; ?>"
                            aria-current="page" href="<?php echo INDEX_URL; ?>#Home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link load_ajax <?php echo ($currentPage == 'index.php') ? "active" : ""; ?>"
                            aria-current="page" href="<?php echo INDEX_URL; ?>#About">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link load_ajax <?php echo ($currentPage == 'index.php') ? "active" : ""; ?>"
                            aria-current="page" href="<?php echo INDEX_URL; ?>#Services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link load_ajax <?php echo ($currentPage == 'contact.php') ? "active" : ""; ?>"
                            aria-current="page" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link load_ajax <?php echo ($currentPage == 'book-appointment.php') ? "active" : ""; ?>"
                            aria-current="page" href="book-appointment.php">Book Appointment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link load_ajax <?php echo ($currentPage == 'signup.php') ? "active" : ""; ?>"
                            aria-current="page" href="signup.php"
                            style="display : <?php echo $loggedIn ? 'none' : '' ?>">Register</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link load_ajax <?php echo ($currentPage == 'login.php') ? "active" : ""; ?>"
                            aria-current="page" href="login.php"
                            style="display : <?php echo $loggedIn ? 'none' : '' ?>">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link load_ajax <?php echo ($currentPage == 'profile.php') ? "active" : ""; ?>"
                            aria-current="page" href="profile.php" style="display : <?php echo $loggedIn ? '' : 'none' ?>">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a id="logoutLink" class="nav-link" aria-current="page"
                            style="display : <?php echo $loggedIn ? '' : 'none' ?>">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>