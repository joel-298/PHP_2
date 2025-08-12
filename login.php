<?php
$PAGE_TITLE = "Login";
require_once __DIR__ . '/header.php';
// CREATE A CSRF TOKEN 
if (empty($_SESSION['csrfToken'])) {
    $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
}

?>

<div class="container mt-5">

    <div class="row">

        <div class="col-md-6 mb-4">
            <video src="<?php echo RESOURCES; ?>animation.mp4" class="img-fluid mb-3" autoplay loop muted playsinline
                style="height:fit-content;"></video>
            <h5 class="sample_email">Email&nbsp;:&nbsp;<a href="mailto:someone@example.com">someone@example.com</a></h5>
        </div>

        <div class="col-md-6">
            <form id="loginForm" class="bg-light">
                <!-- CSRF TOKEN -->
                <input type="hidden" id="csrfLoginToken" name="csrfToken"
                    value="<?php echo htmlspecialchars($_SESSION['csrfToken']); ?>">

                <div class="mb-3">
                    <label for="login_email" class="form-label">Email</label>
                    <span class="span" id="login_emailWarning">*</span><br>
                    <input type="email" class="form-control" id="login_email" name="login_email" value="" required>
                    <div class="invalid-feedback" id="login_emailFeedback">TEXT</div>
                </div>

                <div class="mb-3">
                    <label for="login_password" class="form-label">Password</label>
                    <span class="span" id="login_passwordWarning">*</span><br>
                    <div class="input-group">
                        <input type="password" class="form-control" id="login_password" name="login_password" value="" required >
                        <button type="button" class="btn btn-outline-secondary" id="toggleLoginPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback" id="login_passwordFeedback">TEXT</div>
                </div>

                <div class="mb-3" style="display:flex ; align-items:center ; ">
                    <button type="submit" class="btn btn-primary">Login</button>&nbsp;&nbsp;&nbsp;
                    <a href="forgotPassword.php" style="text-decoration:none;" class="load_ajax"><span>Forgot Password</span></a>
                </div>
                <p id="url">If not registered then go to <span onclick="showSignup()">Signup</span> Page !</p>
            </form>
        </div>

    </div>

</div>

<script>
    function showSignup() {
        window.location.href = "signup.php";
    }

    $(function () {

        // TOGGLE PASSWORD VISIBILITY
        const togglePassword = document.querySelector('#toggleLoginPassword');
        const password = document.querySelector('#login_password');

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

            // FORM VALIDATION 
            function sanitizeInput(input) {
                return input.replace(/[<>\/\\'"`;%(){}[\]=+&^$|]/g, "").trim();
            }

            let email = sanitizeInput($('#login_email').val());
            let password = $('#login_password').val().trim();


            let valid = true;
            if (!email) {
                $('#login_emailFeedback').addClass("display");
                $('#login_emailFeedback').text("Email Field Cannot be Empty !");
                valid = false;
            }
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $('#login_emailFeedback').addClass("display");
                $('#login_emailFeedback').text("Please enter a valid email address!");
                valid = false;
            } 
            else if (email.length > 100) {
                $('#login_emailFeedback').addClass("display");
                $('#login_emailFeedback').text("Email must be less than 100 characters.");
                valid = false;
            } 
            else {
                $('#login_emailFeedback').removeClass("display");
            }

            if (!password) {
                $('#login_passwordFeedback').css("display",'inline');
                $('#login_passwordFeedback').text("Password Field Cannot be Empty !");
                valid = false;
            } else {
                $('#login_passwordFeedback').css("display",'none');
            }


            if (!valid) {
                // alert("Please Enter Valid Data !") ; 
                console.log("Validation failed !");
                return;
            }
            // Remove Stars from the input fiels 
            $('#login_emailFeedback, #login_passwordFeedback').removeClass("display");
            $('#login_passwordFeedback').css("display",'none');


            // FORM DATA 
            let formData = new FormData();
            formData.append("email", email);
            formData.append("password", password);
            formData.append("csrfToken", $('#csrfLoginToken').val());
            $.ajax({
                url: "<?php echo CONTROLLERS_URL ;?>1_Login.php",
                type: "POST",
                data: formData,
                contentType: false, // do not set header to url-encoded-form 
                processData: false, // Do not change the javascript object to query String (like we see in urls)
                success: function (response) {
                    console.log(response);
                    $('#login_emailFeedback').removeClass("display");
                    alert("User Login Successful !");
                    window.location.href = "profile.php";
                    return;
                },
                error: function (xhr) {
                    console.log("Status Code : " + xhr.status);
                    console.log("Response : " + xhr.responseText);
                    const errorResponse = JSON.parse(xhr.responseText);
                    $('#login_emailFeedback, #login_passwordFeedback').removeClass("display");
                    $('#login_emailFeedback, #login_passwordFeedback').css("display",'none');
                    if(xhr.status == 403) {
                        alert(errorResponse.message) ;
                        window.location.reload() ;
                        return ; 
                    } else if (xhr.status == 400) {
                        if (errorResponse.message.includes("fields")) {
                            alert("Marked fields required"); 
                            $('#login_emailFeedback,#login_passwordFeedback').text("Marked fields required").css("display",'inline');
                            return ; 
                        } else if (errorResponse.message.includes("Email")) {
                            alert(errorResponse.message) ;     
                            $('#login_emailFeedback').text(errorResponse.message).css("display","inline") ;
                            return ; 
                        } else if (errorResponse.message.includes("Password")) {
                            alert(errorResponse.message) ;   
                            $('#login_passwordFeedback').text(errorResponse.message).css("display","inline") ; 
                            return ; 
                        }  else {
                            alert("Internal Server Error") ; 
                            window.location.reload() ; 
                            return ; 
                        }
                    } else if (xhr.status == 401) {
                        if(errorResponse.message.includes("verify your email")) {
                            alert("Please verify your email before proceeding !");
                            window.location.href = "otp.php";
                            return;
                        }
                        alert("Please enter correct password !") ; 
                        $('#login_passwordFeedback').text("Please enter correct password !").css("display","inline") ; 
                        return ; 
                    } else if (xhr.status == 404) {
                        alert("User Not Found ! Please Signup !");
                        window.location.href = "signup.php";
                        return;
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
<?php require_once __DIR__ . '/footer.php'; ?>