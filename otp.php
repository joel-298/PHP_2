<?php
$PAGE_TITLE = "Otp-verification";
require_once __DIR__ . '/header.php';
// CREATE A CSRF TOKEN 
if (empty($_SESSION['csrfToken'])) {
    $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
}
// redirect user back to login page in he tries to refresh or re-visit
unset($_SESSION['showOtpPage']);
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 mb-4">
            <video src="<?php echo RESOURCES; ?>animation.mp4" class="img-fluid mb-3" autoplay loop muted playsinline
                style="height:fit-content;"></video>
            <h5 class="sample_email">OTP&nbsp;:&nbsp;<a href="tel:123456">123456</a></h5>
        </div>

        <div class="col-md-6">
            <form id="otpForm" class="bg-light">
                <!-- CSRF TOKEN -->
                <input type="hidden" id="csrfOTPToken" name="csrfToken"
                    value="<?php echo htmlspecialchars($_SESSION['csrfToken']); ?>">

                <div class="mb-3">
                    <label for="otp" class="form-label">Enter 6-Digit OTP</label>
                    <span class="span" id="otpWarning">*</span><br>

                    <!-- Hidden actual field used by AJAX (keep IDs as is) -->
                    <input type="hidden" maxlength="6" class="form-control" id="otp" name="otp" value="">

                    <!-- UI inputs -->
                    <div class="d-flex gap-1 justify-content-center mt-2" id="otpBoxes">
                        <input type="text" maxlength="1" class="form-control text-center otp-box">
                        <input type="text" maxlength="1" class="form-control text-center otp-box">
                        <input type="text" maxlength="1" class="form-control text-center otp-box">
                        <input type="text" maxlength="1" class="form-control text-center otp-box">
                        <input type="text" maxlength="1" class="form-control text-center otp-box">
                        <input type="text" maxlength="1" class="form-control text-center otp-box">
                    </div>

                    <div class="invalid-feedback" id="otpFeedback">Please enter your OTP</div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" id="regenerateBtn">Regenerate OTP</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>


                <p id="timerText" class="mt-3 text-muted">Didn't receive? Click regenerate or check spam!</p>
            </form>
        </div>

    </div>
</div>


<script>
    $(function () {
        // On each box input, update hidden field
        $('.otp-box').on('input', function () {
            let otp = '';
            $('.otp-box').each(function () {
                otp += $(this).val();
            });
            $('#otp').val(otp); // update hidden field
            
            // Real-time validation
            validateOTP(otp);
        });

        // Enhanced input validation for OTP boxes
        $('.otp-box').on('keypress', function (e) {
            // Only allow numbers
            if (e.which < 48 || e.which > 57) {
                e.preventDefault();
                return false;
            }
        });

        // Optional: auto-focus to next box
        $('.otp-box').on('keyup', function (e) {
            if (e.key >= '0' && e.key <= '9') {
                $(this).next('.otp-box').focus();
            } else if (e.key === 'Backspace') {
                $(this).prev('.otp-box').focus();
            }
        });

        // OTP validation function
        function validateOTP(otp) {
            const otpFeedback = $('#otpFeedback');
            const otpWarning = $('#otpWarning');
            
            // Reset styling
            $('.otp-box').removeClass('otp-invalid otp-valid');
            
            if (!otp) {
                otpFeedback.addClass("display");
                otpFeedback.text("OTP Field Cannot be Empty!");
                otpWarning.text("*");
                return false;
            }
            
            if (otp.length !== 6) {
                otpFeedback.addClass("display");
                otpFeedback.text("Please enter exactly 6 digits!");
                otpWarning.text("*");
                return false;
            }
            
            if (!/^\d{6}$/.test(otp)) {
                otpFeedback.addClass("display");
                otpFeedback.text("OTP must contain only numbers!");
                otpWarning.text("*");
                return false;
            }
            
            // Valid OTP
            otpFeedback.removeClass("display");
            otpWarning.text("");
            $('.otp-box').addClass('otp-valid');
            return true;
        }

        // O T P   A J A X 
        $('#otpForm').submit(function (e) {
            e.preventDefault();

            // FORM VALIDATION 
            function sanitizeInput(input) {
                return input.replace(/[<>\/\\'"`;%(){}[\]=+&^$|]/g, "").trim();
            }

            // let id = sanitizeInput(sessionStorage.getItem('id')) ; 
            let otp = sanitizeInput($('#otp').val());

            // Enhanced validation
            if (!validateOTP(otp)) {
                return;
            }

            // Additional security checks
            if (otp.length !== 6) {
                $('#otpFeedback').addClass("display");
                $('#otpFeedback').text("Please enter exactly 6 digits!");
                return;
            }

            if (!/^\d{6}$/.test(otp)) {
                $('#otpFeedback').addClass("display");
                $('#otpFeedback').text("OTP must contain only numbers!");
                return;
            }

            // Remove Stars from the input fields 
            $('#otpFeedback').removeClass("display");

            let currentTime = new Date().toISOString(); // e.g. 2025-07-01T04:30:00.000Z
            // FORM DATA 
            let formData = new FormData();
            // formData.append("id",id) ; 
            formData.append("otp", otp);
            formData.append("time", currentTime); // Send timestamp
            formData.append("csrfToken", $('#csrfOTPToken').val());
            
            // Disable submit button to prevent double submission
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).text('Verifying...');
            
            $.ajax({
                url: "<?php echo CONTROLLERS_URL ;?>1_Otp.php",
                type: "POST",
                data: formData,
                contentType: false, // do not set header to url-encoded-form 
                processData: false, // Do not change the javascript object to query String (like we see in urls)
                success: function (response) {
                    console.log(response);
                    if (response.status === "success") {
                        // Check if what is the page name ? 
                        console.log(response);
                        if (response.page == "forgotPassword") {
                            alert("OTP verified you can change the credentials now!");
                            window.location.href = "resetPassword.php";
                            return;
                        } else {
                            alert("Account created successfully! Please Login!");
                            window.location.href = "login.php";
                            return;
                        }
                    }
                },
                error: function (xhr) {
                    console.log("Status Code : " + xhr.status);
                    console.log("Response : " + xhr.responseText);
                    const errorResponse = JSON.parse(xhr.responseText);
                    $('#otpFeedback').removeClass("display");
                    if(xhr.status == 403) {
                        alert(errorResponse.message) ;
                        window.location.reload() ;
                        return ;  
                    } else if (xhr.status == 400) {
                        if(errorResponse.message.includes("expiry not set")) {
                            alert(errorResponse.message) ;    
                            return ; 
                        } else if(errorResponse.message.includes("Valid Fields")) {
                            alert("Marked fileds cannot be empty") ;
                            $('#otpFeedback').text("Marked fileds cannot be empty").addClass("display");    
                            return ; 
                        } else if (errorResponse.message.includes("OTP expired")) {
                            alert(errorResponse.message) ;
                            $('#otpFeedback').text(errorResponse.message).addClass("display");    
                            return ; 
                        } else if (errorResponse.message.includes("Invalid OTP")) {
                            alert(errorResponse.message) ;
                            $('#otpFeedback').text(errorResponse.message).addClass("display");    
                            return ; 
                        } else if (errorResponse.message.includes("User Not Found")) {
                            alert(errorResponse.message) ;
                            window.location.href = "signup.php";
                            return;
                        } else {
                            alert("Internal Server Error") ; 
                            return ; 
                        }
                    } else {
                        alert(errorResponse.message || 'Internal Server Error !');
                        return ; 
                    }
                },
                complete: function() {
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).text('Submit');
                }
            })

        });

        // OTP REGENERATION !
        let countdownInterval;
        $('#regenerateBtn').on('click', function () {
            const button = $(this);
            const csrfToken = $('#csrfOTPToken').val();
            
            // Validate CSRF token
            if (!csrfToken) {
                alert("Security token missing. Please refresh the page.");
                return;
            }
            
            // Disable button and start countdown
            button.prop('disabled', true);
            let timer = 60;
            $('#timerText').text(`Please wait ${timer} seconds to resend OTP...`);
            countdownInterval = setInterval(() => {
                timer--;
                $('#timerText').text(`Please wait ${timer} seconds to resend OTP...`);
                if (timer <= 0) {
                    clearInterval(countdownInterval);
                    button.prop('disabled', false);
                    $('#timerText').text("Didn't receive? Click regenerate or check spam!");
                }
            }, 1000);

            // AJAX request
            $.ajax({
                url: "<?php echo CONTROLLERS_URL ;?>1_RegenerateOTP.php",
                type: "POST",
                data: {
                    csrfToken: csrfToken
                    // id: id
                },
                success: function (response) {
                    if (response.status === "success") {
                        alert("OTP resent successfully!");
                        // Clear OTP fields
                        $('.otp-box').val('').removeClass('otp-valid otp-invalid');
                        $('#otp').val('');
                    } else {
                        if (response.message == "User Not Found Please Signup!") {
                            alert(response.message);
                            window.location.reload();
                            return;
                        }
                        alert("Error: " + response.message);
                    }
                },
                error: function (xhr) {
                    console.log("Status Code : " + xhr.status);
                    console.log("Response : " + xhr.responseText);
                    alert("Failed to resend OTP.");
                }
            });
        });
    });
</script>
<?php require_once __DIR__ . '/footer.php'; ?>