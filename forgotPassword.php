<?php
$PAGE_TITLE = "Forgot-Password";
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

            <h2 id="heading_forgot_password">Forgot Password</h2>
            <form id="forgotForm" class="bg-light">
                <!-- CSRF TOKEN -->
                <input type="hidden" id="csrfForgotToken" name="csrfToken"
                    value="<?php echo htmlspecialchars($_SESSION['csrfToken']); ?>">

                <div class="mb-3">
                    <label for="forgot_email" class="form-label">Email</label>
                    <span class="span" id="forgot_emailWarning">*</span><br>
                    <input type="email" class="form-control" id="forgot_email" name="forgot_email" value="" required>
                    <div class="invalid-feedback" id="forgot_emailFeedback">TEXT</div>
                </div>


                <div class="mb-3" style="display:flex ; align-items:center ;">
                    <button type="submit" class="btn btn-primary">Submit</button>&nbsp;&nbsp;&nbsp;
                    <a href="login.php" style="text-decoration:none;" class="load_ajax"><span>Back to Login</span></a>
                </div>
            </form>



        </div>
    </div>
</div>

<script>
    $(function () {
        $('#forgotForm').submit(function (e) {
            e.preventDefault();

            // FORM VALIDATION 
            function sanitizeInput(input) {
                return input.replace(/[<>\/\\'"`;%(){}[\]=+&^$|]/g, "").trim();
            }
            let email = sanitizeInput($('#forgot_email').val());

            let valid = true;
            if (!email) {
                $('#forgot_emailFeedback').addClass("display");
                $('#forgot_emailFeedback').text("Email Field Cannot be Empty !");
                valid = false;
            }
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $('#forgot_emailFeedback').addClass("display");
                $('#forgot_emailFeedback').text("Please enter a valid email address!");
                valid = false;
            } 
            else {
                $('#forgot_emailFeedback').removeClass("display");
            }

            if (!valid) {
                // alert("Please Enter Valid Data !") ; 
                return;
            }
            // Remove Stars from the input fiels 
            $('#forgot_emailFeedback').removeClass("display");
            // FORM DATA 
            let formData = new FormData();
            formData.append("email", email);
            formData.append("csrfToken", $('#csrfForgotToken').val());
            $.ajax({
                url: "<?php echo CONTROLLERS_URL ;?>6_ForgotPassword.php",
                type: "POST",
                data: formData,
                contentType: false, // do not set header to url-encoded-form 
                processData: false, // Do not change the javascript object to query String (like we see in urls)
                success: function (response) {
                    if (response.status == "success") {
                        $('#forgot_emailFeedback').removeClass("display");
                        alert("Email sent successfully ! Please verify your email and then you can change credentials !");
                        window.location.href = "otp.php";
                        return;
                    }
                    else {
                        $('#forgot_emailFeedback').removeClass("display");
                        // $('#heading_forgot_password').text("Verify your email before changing credentials") ;
                        alert("You account verification is pending please verify your account !");
                        window.location.href = "otp.php";
                        return;
                    }
                },
                error: function (xhr) {
                    console.log("Status Code : " + xhr.status);
                    console.log("Response : " + xhr.responseText);
                    $('#forgot_emailFeedback').removeClass("display");
                    const errorResponse = JSON.parse(xhr.responseText);
                    if(xhr.status == 403) {
                        alert(errorResponse.message) ;
                        window.location.reload() ;
                        return ;  
                    } else if(xhr.status == 400) {
                        if(errorResponse.message.includes("fields")) {
                            alert(errorResponse.message) ; 
                            $('#forgot_emailFeedback').text(errorResponse.message).addClass("display");
                            return ; 
                        } else if(errorResponse.message.includes("Email") || errorResponse.message.includes("email") ) {
                            alert(errorResponse.message) ; 
                            $('#forgot_emailFeedback').text(errorResponse.message).addClass("display") ; 
                            return ; 
                        }
                    } else if(xhr.status == 404) {
                        alert(errorResponse.message) ; 
                        window.location.href = "signup.php"
                        return ; 
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