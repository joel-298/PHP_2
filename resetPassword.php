<?php
$PAGE_TITLE = "Reset-Password";
require_once __DIR__ . '/header.php';
// redirect the person to authentication if he trys to refresh the page !
unset($_SESSION["change_credentials"]);
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
        </div>
        <div class="col-md-6">
            <form id="RestPasswordForm" class="bg-light">
                <!-- CSRF TOKEN -->
                <input type="hidden" id="csrfRestPasswordToken" name="csrfRestPasswordToken"
                    value="<?php echo htmlspecialchars($_SESSION['csrfToken']); ?>">

                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <span class="span" id="RestPasswordWarning">*</span><br>
                    <div class="input-group">
                        <input type="password" class="form-control" id="RestPassword" name="RestPassword" value="" required>
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback" id="RestPasswordFeedback">TEXT</div>
                </div>
                <div class="mb-3">
                    <label for="RestPasswordconfirm_password" class="form-label">Confirm Password</label>
                    <span class="span" id="RestPasswordconfirm_passwordWarning">*</span><br>
                    <div class="input-group">
                        <input type="password" class="form-control" id="RestPasswordconfirm_password" name="RestPasswordconfirm_password" value="" required>
                        <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback" id="RestPasswordconfirm_passwordFeedback">TEXT</div>
                </div>

                <div class="mb-3" style="display:flex ; align-items:center ;">
                    <button type="submit" class="btn btn-primary">Submit</button>&nbsp;&nbsp;&nbsp;
                    <a href="signup.php" style="text-decoration:none;" class="load_ajax"><span>Back to Login</span></a>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    $(function () {
        // TOGGLE PASSWORD VISIBILITY
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#RestPassword');
        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
        const ConfirmPassword = document.querySelector('#RestPasswordconfirm_password');

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


        // CHANGE PASSWORD
        $('#RestPasswordForm').submit(function (e) {
            e.preventDefault();


            let password = $('#RestPassword').val().trim();
            let confirm_password = $('#RestPasswordconfirm_password').val().trim();

            let valid = true;
            if (!password) {
                $('#RestPasswordFeedback').css("display", "block");
                $('#RestPasswordFeedback').text("Password Field Cannot be Empty !");
                valid = false;
            } else if (password.length < 6 || password.length > 15 ) {
                $('#RestPasswordFeedback').css("display", "block");
                $('#RestPasswordFeedback').text("Password must be between 6 and 15 characters");
                $('#RestPassword').val('') ; 
                valid = false;
            } else {
                $('#RestPasswordFeedback').css("display", "none");
            }

            if (!confirm_password) {
                $('#RestPasswordconfirm_passwordFeedback').css("display", "block");
                $('#RestPasswordconfirm_passwordFeedback').text("Confirm Password Field Cannot be Empty !");
                valid = false;
            } else if (confirm_password.length < 6 || confirm_password.length > 15 ) {
                $('#RestPasswordconfirm_passwordFeedback').css("display", "block");
                $('#RestPasswordconfirm_passwordFeedback').text("Password must be between 6 and 15 characters");
                $('#RestPasswordconfirm_password').val('');
                valid = false;
            } else {
                $('#RestPasswordconfirm_passwordFeedback').css("display", "none");
            }


            if (!valid) {
                // alert("Please Enter Valid Data !") ; 
                return;
                valid = true ; 
            }

            const hasLetter = /[a-zA-Z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            if (!hasLetter || !hasNumber || !hasSpecial) {
                $('#RestPasswordFeedback').css("display", "block");
                $('#RestPasswordFeedback').text("Password must include a letter, a number, and a special character!");
                $('#RestPassword').val('') ; 
                valid = false;
            } else {
                $('#RestPasswordFeedback').css("display", "none");
            }

            if (confirm_password.length != password.length || confirm_password != password) {
                $('#RestPasswordconfirm_passwordFeedback').css("display", "block");
                $('#RestPasswordconfirm_passwordFeedback').text("Password did not match !");
                $('#RestPassword').val('') ; 
                $('#RestPasswordconfirm_password').val('');
                valid = false;
            } else {
                $('#RestPasswordconfirm_passwordFeedback').css("display", "none");
            }
            if (!valid) {
                // alert("Please Enter Valid Data !") ; 
                return;
            }


            // Remove Stars from the input fiels 
            $('#RestPasswordFeedback, #RestPasswordconfirm_passwordFeedback').css("display",'none');


            // FORM DATA 
            let formData = new FormData();
            formData.append("password", password);
            formData.append("confirmPassword", confirm_password) ; 
            formData.append("csrfToken", $('#csrfRestPasswordToken').val());
            $.ajax({
                url: "<?php echo CONTROLLERS_URL ;?>7_resetPassword.php",
                type: "POST",
                data: formData,
                contentType: false, // do not set header to url-encoded-form 
                processData: false, // Do not change the javascript object to query String (like we see in urls)
                success: function (response) {
                    console.log(response);
                    alert("Password Changed Successfully ! Please Login !");
                    window.location.href = "login.php";
                    return;
                },
                error: function (xhr) {
                    console.log("Status Code : " + xhr.status);
                    console.log("Response : " + xhr.responseText);
                    const errorResponse = JSON.parse(xhr.responseText);
                    $('#RestPasswordFeedback, #RestPasswordconfirm_passwordFeedback').css("display",'none');
                    if(xhr.status == 403) {
                        alert(errorResponse.message) ;
                        window.location.reload() ; 
                        return ; 
                    } else if(xhr.status == 400 ) {
                        if (errorResponse.message.includes("fields")) {
                            alert("Marked fields required.") ; 
                            $('#RestPasswordFeedback, #RestPasswordconfirm_passwordFeedback').text("Marked fields required.").css("display",'inline');
                            return ; 
                        } else if (errorResponse.message.includes("characters long")) {
                            alert(errorResponse.message) ;
                            $('#RestPasswordFeedback, #RestPasswordconfirm_passwordFeedback').text(errorResponse.message).css("display",'inline');
                            $('#RestPassword').val('') ; 
                            $('#RestPasswordconfirm_password').val('');
                            return ; 
                        } else if (errorResponse.message.includes("a letter, number & symbol")) {
                            alert(errorResponse.message) ;
                            $('#RestPasswordFeedback, #RestPasswordconfirm_passwordFeedback').text(errorResponse.message).css("display",'inline');
                            $('#RestPassword').val('') ; 
                            $('#RestPasswordconfirm_password').val('');
                            return ;    
                        } else if (errorResponse.message.includes("do not match")) {
                            alert(errorResponse.message) ;
                            $('#RestPasswordconfirm_passwordFeedback').text(errorResponse.message).css("display",'inline');
                            $('#RestPassword').val('') ; 
                            $('#RestPasswordconfirm_password').val('');
                            return ;   
                        } else {
                            alert("Internal Server Error") ;
                            window.location.reload() ;           
                            return ;
                        }
                    } else if(xhr.status == 404) {
                        alert(errorResponse.message) ;        
                        window.location.href = "signup.php";
                        return ; 
                    } else { // 500
                        if(errorResponse.message.includes("Please Register again")) {
                            alert(errorResponse.message);
                            window.location.href = "signup.php";
                            return;
                        }
                        alert(errorResponse.message || 'Internal Server Error !');
                        window.location.reload() ;
                        return ; 
                    }
                }
            })

        });
    }); 
</script>
<?php require_once __DIR__ . '/footer.php'; ?>