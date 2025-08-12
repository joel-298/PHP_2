<?php
require_once dirname(dirname(__DIR__)) . '/config.php';
$page = basename($_SERVER['PHP_SELF']);
$loggedIn = false;
// PROTECTED ROUTES !
if (isset($_SESSION['isAdminLoggedIn']) && $_SESSION['isAdminLoggedIn']) {
    $loggedIn = true;
    if ($page == 'login.php' || $page == 'forgotPassword.php' || $page == 'resetPassword.php') {
        header("Location: ./");
        exit;
    }
}
// CREATE A CSRF TOKEN 
if (empty($_SESSION['csrfToken'])) {
    $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ADMIN_RESOURCES; ?>/login.css">

</head>
<body>
    <!-- AJAX Loader -->
    <div id="loader"
        style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; backdrop-filter: blur(8px); background-color: rgba(0, 0, 0, 0.3); z-index: 9999; display: none; align-items: center; justify-content: center;">
        <div
            style=" width: 60px; height: 60px; border: 6px solid #ffffff; border-top: 6px solid transparent; border-radius: 50%; animation: spin 1s linear infinite;">
        </div>
    </div>
    <!------------------>
    <div class="container-fluid login-container">
        <div class="card login-card">
            <div class="row g-0">
                <!-- Left side with image -->
                <div class="col-md-6 login-image">
                    <img src="<?php echo ADMIN_RESOURCES; ?>login_img.jpeg" alt="Login Illustration" class="img-fluid">
                </div>

                <!-- Right side with form -->
                <div class="col-md-6 login-form">
                    <div class="text-left mb-4">
                        <h1 class="fw-bold">Admin Login</h1>
                    </div>
                    <br>
                    <form id="loginForm">
                        <!-- CSRF TOKEN -->
                        <input type="hidden" id="csrfLoginToken" name="csrfToken"
                            value="<?php echo htmlspecialchars($_SESSION['csrfToken']); ?>">

                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="email" class="form-label" style="display:flex;">Email<span
                                    style="color:red;display:flex;">&nbsp;&nbsp;*&nbsp;&nbsp;<P class="hidden"
                                        id="emailFeedback">Text</P></span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="Enter your email" required>
                            </div>
                        </div>
                        <br>
                        <!-- Password Input -->
                        <div class="mb-3">
                            <label for="password" class="form-label" style="display:flex;">Password<span
                                    style="color:red;display:flex;">&nbsp;&nbsp;*&nbsp;&nbsp;<p class="hidden"
                                        id="passwordFeedback">Text</p></span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Enter your password" required maxlength="15">
                                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <br>
                        <!-- Forgot Password -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <a href="#" class="forgot-password load_ajax"><span>Forgot password?</span></a>
                        </div>

                        <!-- Login Button -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                        <br>

                    </form>
                    <div class="text-center mt-3">
                        <p class="text-muted mb-0 small">© <?php echo date('Y'); ?> Oriental Outsourcing Consultants
                            Private Limited. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>


    </div>





    <!-- Jquery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(function () {

            $(document).ajaxSend(function () {
                $('#loader').css('display', 'flex');
            });
            $(document).ajaxStop(function () {
                $('#loader').css('display', 'none');
            });
            // TOGGLE PASSWORD VISIBILITY
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');

            togglePassword.addEventListener('click', function (e) {
                // toggle the type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                // toggle the eye / eye-slash icon
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
            // L O G I N   A J A X 
            $('#loginForm').submit(function (e) {
                e.preventDefault();

                setTimeout(function () {

                }, 1000);
                // FORM VALIDATION 
                function sanitizeInput(input) {
                    return input.replace(/[<>\/\\'"`;%(){}[\]=+&^$|]/g, "").trim();
                }

                let email = sanitizeInput($('#email').val());
                let password = $('#password').val().trim();

                let valid = true;
                if (!email) {
                    $('#emailFeedback').removeClass('hidden');
                    $('#emailFeedback').text("Email Field Cannot be Empty.");
                    valid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    $('#emailFeedback').removeClass("hidden");
                    $('#emailFeedback').text("Please enter a valid email address.");
                    valid = false;
                } else {
                    $('#emailFeedback').addClass('hidden');
                }

                if (!password) {
                    $('#passwordFeedback').removeClass('hidden');
                    $('#passwordFeedback').text("Password Field Cannot be Empty.");
                    valid = false;
                } else {
                    $('#passwordFeedback').addClass('hidden');
                }


                if (!valid) {
                    // alert("Please Enter Valid Data !") ;
                    console.log("Please enter valid data !")
                    return;
                }
                // Remove feedbacks from the input fiels 
                $('#emailFeedback, #passwordFeedback').addClass("hidden");


                // FORM DATA 
                let formData = new FormData();
                formData.append("email", email);
                formData.append("password", password);                
                formData.append("function", "login");
                formData.append("csrfToken", $('#csrfLoginToken').val());
                $.ajax({
                    url: "./front_ajax.php",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        console.log(response);
                        $('#emailFeedback').addClass("hidden");
                        $('#passwordFeedback').addClass("hidden");
                        alert("Admin Login Successful.");
                        window.location.href = "./";
                        return;
                    },
                    error: function (xhr) {
                        console.log("Status Code : " + xhr.status);
                        console.log("Response : " + xhr.responseText);
                        const errorResponse = JSON.parse(xhr.responseText);
                        $('#emailFeedback, #passwordFeedback').addClass("hidden");
                        if(xhr.status == 403) { // THIS SHOULD RUN 
                            alert(errorResponse.message) ; 
                            window.location.reload() ; 
                            return ; 
                        } else if(xhr.status == 400) {
                            if(errorResponse.message.includes('fields')) {
                                alert(errorResponse.message) ; 
                                $('#emailFeedback, #passwordFeedback').text("Marked fileds cannot be empty").removeClass("hidden");
                                return ; 
                            } else if (errorResponse.message.includes("Email")) {
                                alert(errorResponse.message) ; 
                                $('#emailFeedback').text(errorResponse.message).removeClass("hidden");
                                return; 
                            } else if (errorResponse.message.includes("Password")) {
                                alert(errorResponse.message) ; 
                                $('#passwordFeedback').text(errorResponse.message).removeClass("hidden");
                                $('#password').val('');
                                return;  
                            }
                        } else if(xhr.status == 401) {
                            alert(errorResponse.message) ; 
                            $('#passwordFeedback').text("Wrong Password").removeClass("hidden") ; 
                            $('#password').val('');
                            return ; 
                        } else if(xhr.status == 404) {
                            alert(errorResponse.message) ; 
                            $('#emailFeedback').text("Admin Not Found.").removeClass("hidden") ; 
                            return ;
                        } else {
                            alert(errorResponse.message || 'Internal Server Error !');
                            window.location.reload() ; 
                            return ; 
                        }
                    }
                });

            });
        }); 
    </script>
</body>

</html>



<!-- // USE task;
// CREATE TABLE admin (
//     id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
//     name VARCHAR(255) NOT NULL,
//     email VARCHAR(150) NOT NULL UNIQUE,
//     password VARCHAR(100) NOT NULL,
//     created_on DATETIME NOT NULL,
//     verified ENUM('0', '1') NOT NULL
// );

// name = Admin 
// email = joel465.be22@chitkara.edu.in ; 
// password = Admin@123
// created on  current date and time 
// verified = 1  -->