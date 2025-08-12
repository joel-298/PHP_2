<?php
$PAGE_TITLE = "Signup";
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
            <!-- <h5>Phone: <a href="tel:1234567890">1234567890</a></h5> -->
        </div>
        <div class="col-md-6">

            <form id="signUpForm" class="bg-light">
                <!-- CSRF TOKEN -->
                <input type="hidden" id="csrfSignupToken" name="csrfSignupToken"
                    value="<?php echo htmlspecialchars($_SESSION['csrfToken']); ?>">

                <div class="mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <span class="span" id="first_nameWarning">*</span><br>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="" required>
                    <div class="invalid-feedback" id="first_nameFeedback">TEXT</div>
                </div>

                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <span class="span" id="last_nameWarning">*</span><br>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="" required>
                    <div class="invalid-feedback" id="last_nameFeedback">TEXT</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <span class="span" id="emailWarning">*</span><br>
                    <input type="email" class="form-control" id="email" name="email" value="" required>
                    <div class="invalid-feedback" id="emailFeedback">TEXT</div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <span class="span" id="passwordWarning">*</span><br>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" value="" required minlength="6" maxlength="15">
                        <button type="button" class="btn btn-outline-secondary" id="toggleSignupPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback" id="passwordFeedback">TEXT</div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <span class="span" id="confirm_passwordWarning">*</span><br>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" value="" required minlength="6" maxlength="15">
                        <button type="button" class="btn btn-outline-secondary" id="toggleSignupConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback" id="confirm_passwordFeedback">TEXT</div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:15px;">Signup</button>
                <p id="url">Already have an account ? <span onclick="showLogin()">Login</span> now !</p>
            </form>


        </div>
    </div>
</div>


<script>
    function showLogin() {
        window.location.href = "login.php";
    }
    $(function () {

        // TOGGLE PASSWORD VISIBILITY
        const togglePassword = document.querySelector('#toggleSignupPassword');
        const password = document.querySelector('#password');
        const toggleConfirmPassword = document.querySelector('#toggleSignupConfirmPassword');
        const ConfirmPassword = document.querySelector('#confirm_password');

        togglePassword.addEventListener('click', function (e) {
            // toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // toggle the eye / eye-slash icon
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });       
        toggleConfirmPassword.addEventListener('click', function (e) {
            // toggle the type attribute
            const type = ConfirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            ConfirmPassword.setAttribute('type', type);
            // toggle the eye / eye-slash icon
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });


        // S I G N U P   A J A X 
        $('#signUpForm').submit(function (e) {
            e.preventDefault();

            // FORM VALIDATION 
            function sanitizeInput(input) {
                return input.replace(/[<>\/\\'"`;%(){}[\]=+&^$|]/g, "").trim();
            }


            let first_name = sanitizeInput($('#first_name').val());
            let last_name = sanitizeInput($('#last_name').val());
            let email = sanitizeInput($('#email').val());
            let password = $('#password').val().trim();
            let confirm_password = $('#confirm_password').val();

            let valid = true;
            if (!first_name) {
                $('#first_nameFeedback').addClass("display");
                $('#first_nameFeedback').text("First Name Field Cannot be Empty !");
                valid = false;
            } else if(first_name.length > 100) {
                $('#first_nameFeedback').addClass("display");
                $('#first_nameFeedback').text("First Name must be less than 100 characters !");
                valid = false;
            } else {
                $('#first_nameFeedback').removeClass("display");
            }

            if (!last_name) {
                $('#last_nameFeedback').addClass("display");
                $('#last_nameFeedback').text("Last Name Field Cannot be Empty !");
                valid = false;
            } else if (last_name.length > 100) {
                $('#last_nameFeedback').addClass("display");
                $('#last_nameFeedback').text("Last Name must be less than 100 characters !");
                valid = false;
            } else {
                $('#last_nameFeedback').removeClass("display");
            }

            if (!email) {
                $('#emailFeedback').addClass("display");
                $('#emailFeedback').text("Email Field Cannot be Empty !");
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $('#emailFeedback').addClass("display");
                $('#emailFeedback').text("Please enter a valid email address!");
                valid = false;
            } else if(email.length > 100) {
                $('#emailFeedback').addClass("display");
                $('#emailFeedback').text("Email must be less than 100 characters");
                valid = false;
            } else {
                $('#emailFeedback').removeClass("display");
            }

            if (!password) {
                $('#passwordFeedback').css("display", "inline"); 
                $('#passwordFeedback').text("Password Field Cannot be Empty !");
                valid = false;
            } else if (password.length < 6 || password.length > 15) {
                console.log("hello") ; 
                $('#passwordFeedback').css("display", "inline");
                $('#passwordFeedback').text("Password must be between 6 to 15 characters."); 
                $('#password').val('');
                valid = false;
            } else {
                $('#passwordFeedback').css("display", "none");
            }

            if (!confirm_password) {
                $('#confirm_passwordFeedback').css("display", "inline"); 
                $('#confirm_passwordFeedback').text("Confirm Password Field Cannot be Empty !");
                valid = false;
            }  else if (confirm_password.length < 6 || confirm_password.length > 15) {
                $('#confirm_passwordFeedback').css("display", "inline");
                $('#confirm_passwordFeedback').text("Confirm Password must be between 6 to 15 characters.");
                $('#confirm_password').val('');
                valid = false;
            } else {
                $('#confirm_passwordFeedback').css("display", "none");
            }

            const hasLetter = /[a-zA-Z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            if (!hasLetter || !hasNumber || !hasSpecial) {
                $('#passwordFeedback').css("display", "inline"); 
                $('#passwordFeedback').text("Password must include a letter, a number, and a special character!");
                $('#password').val('');
                $('#confirm_password').val('');
                valid = false;
            } else {
                $('#confirm_passwordFeedback').css("display", "none");
            }

            if (confirm_password.length != password.length || confirm_password != password) {
                $('#confirm_passwordFeedback').css("display", "inline"); 
                $('#confirm_passwordFeedback').text("Password did not match !");
                // Clear both password fields
                $('#password').val('');
                $('#confirm_password').val('');
                valid = false;
            } else {
                $('#confirm_passwordFeedback').css("display", "none");
            }

            if (!valid) {
                console.log("Please enter valid data");
                return;
            }
            // Remove Stars from the input fiels 
            $('#first_nameFeedback, #last_nameFeedback, #emailFeedback, #passwordFeedback, #confirm_passwordFeedback').removeClass("display");
            $('#first_nameFeedback, #last_nameFeedback, #emailFeedback, #passwordFeedback, #confirm_passwordFeedback').css('display','none') ;


            // FORM DATA 
            let formData = new FormData();
            formData.append("first_name", first_name);
            formData.append("last_name", last_name);
            formData.append("email", email);
            formData.append("password", password);
            formData.append("confirmPassword", confirm_password);
            formData.append("csrfToken", $('#csrfSignupToken').val());
            $.ajax({
                url: "<?php echo CONTROLLERS_URL ;?>1_Signup.php",
                type: "POST",
                data: formData,
                contentType: false, // do not set header to url-encoded-form 
                processData: false, // Do not change the javascript object to query String (like we see in urls)
                success: function (response) {
                    console.log(response);
                    if (response.status === "success") {
                        // sessionStorage.setItem("id",response.id) ; 
                        if (response.message == "Account already present please verifiy your email !") { // Please Verify Your Email !
                            alert("Your account Is already present, Please verify !");
                            window.location.href = "otp.php";
                            return;
                        } else { // Create a new account
                            alert("Your account is created successfully ! Please verify it !");
                            window.location.href = "otp.php";
                            return;
                        }
                    }
                    else {
                        alert("Acount already exists please Login !");
                        window.location.href = "login.php";
                        return;
                    }
                },
                error: function (xhr) {
                    console.log("Status Code : " + xhr.status);
                    console.log("Response : " + xhr.responseText);
                    const errorResponse = JSON.parse(xhr.responseText);
                    // Remove Stars from the input fiels 
                    $('#first_nameFeedback, #last_nameFeedback, #emailFeedback, #passwordFeedback, #confirm_passwordFeedback').removeClass("display");
                    $('#first_nameFeedback, #last_nameFeedback, #emailFeedback, #passwordFeedback, #confirm_passwordFeedback').css('display','none') ;
                    if(xhr.status == 403) {
                        alert(errorResponse.message) ;
                        window.location.reload() ;
                        return ; 
                    } else if (xhr.status == 400) {
                        if(errorResponse.message.includes("fields")) {
                            alert("Marked fields required") ; 
                            $('#first_nameFeedback, #last_nameFeedback, #emailFeedback, #passwordFeedback, #confirm_passwordFeedback').text("Marked fields required").css("display","inline") ; 
                            return ; 
                        } else if (errorResponse.message.includes("name")) {
                            alert(errorResponse.message) ; 
                            $('#first_nameFeedback, #last_nameFeedback').text(errorResponse.message).css("display","inline") ; 
                            return ; 
                        } else if (errorResponse.message.includes("email")) {
                            alert(errorResponse.message) ; 
                            $('#emailFeedback').text(errorResponse.message).css("display","inline") ; 
                            return ; 
                        } else if (errorResponse.message.includes("Password")) {
                            alert(errorResponse.message) ; 
                            $('#passwordFeedback , #confirm_passwordFeedback').text(errorResponse.message).css("display","inline") ; 
                            // Clear both password fields
                            $('#password').val('');
                            $('#confirm_password').val('');
                            return ; 
                        } else {
                            alert("Internal Server Error.") ; 
                            window.location.reload() ; 
                            return ; 
                        }
                    } else {
                        alert(errorResponse.message || 'Internal Server Error !');
                        return ; 
                    }
                }
            })

        });
    });
</script>
<?php require_once __DIR__ . '/footer.php'; ?>