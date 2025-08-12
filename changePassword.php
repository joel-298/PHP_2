<?php
$PAGE_TITLE = "Profile-Change-Password";
require_once __DIR__ . '/header.php';
// CREATE A CSRF TOKEN 
if (empty($_SESSION['csrfToken'])) {
    $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
}
?>


<!-- Overall Container -->
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-11 col-md-12">
            <div class="row g-4">
                <!-- Profile Sidebar -->
                <div class="col-md-4 text-center">
                    <img src="<?php echo isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : 'https://png.pngtree.com/png-vector/20240529/ourlarge/pngtree-web-programmer-avatar-png-image_12529202.png'; ?>"
                        alt="Profile Image" class="rounded-circle img-fluid mb-3"
                        style="width: 150px; height: 150px; object-fit: cover;">
                    <h5 class="mb-0">
                        <?php echo htmlspecialchars(isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '', ENT_QUOTES); ?>
                        <?php echo htmlspecialchars(isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '', ENT_QUOTES); ?>
                    </h5>
                    <p class="text-muted small">
                        <?php echo htmlspecialchars(isset($_SESSION['email']) ? $_SESSION['email'] : '', ENT_QUOTES); ?>
                    </p>
                </div>

                <!-- Profile Form -->
                <div class="col-md-8 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Change Password</h4>
                        <a href="profile.php" class="btn btn-primary btn-sm px-2 load_ajax">Profile</a>
                    </div>

                    <form method="POST">
                        <!-- CSRF TOKEN -->
                        <input type="hidden" id="csrfToken" name="csrfToken"
                            value="<?php echo htmlspecialchars($_SESSION['csrfToken']); ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="current" class="form-label">Current Password<span
                                        class="text-danger"> *</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current" name="current" value="" required >
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="current_Feedback">TEXT</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="new" class="form-label">New Password<span
                                        class="text-danger"> *</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new" name="new" value="" required >
                                    <button type="button" class="btn btn-outline-secondary" id="toggleNewPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="new_Feedback">TEXT</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm" class="form-label">Confirm Password<span
                                        class="text-danger"> *</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm" name="confirm" value="" required >
                                    <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="confirm_Feedback">TEXT</div>
                            </div>
                        </div>




                        <div class="text-start mt-4">
                            <button type="submit" class="btn btn-primary px-3">Save</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
<script>

    $(function () {
        // TOGGLE PASSWORD VISIBILITY
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#current');
        const toggleNewPassword = document.querySelector('#toggleNewPassword');
        const NewPassword = document.querySelector('#new');
        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
        const ConfirmPassword = document.querySelector('#confirm');

        togglePassword.addEventListener('click', function (e) {
            // toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // toggle the eye / eye-slash icon
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });   
        toggleNewPassword.addEventListener('click', function (e) {
            // toggle the type attribute
            const type = NewPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            NewPassword.setAttribute('type', type);
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

        // FORM SUBMIT
        $('form').submit(function (e) {
            e.preventDefault();

            // Sanitize function
            function sanitize(input) {
                return $('<div>').text(input).html().trim();
            }
            let valid = true;
            $('#current_Feedback, #new_Feedback, #confirm_Feedback').text("").css("display", "none");


            const current = $('#current').val().trim();
            const newPassword = $('#new').val().trim();
            const confirm = $('#confirm').val().trim();

            const hasLetter = (str) => /[a-zA-Z]/.test(str);
            const hasNumber = (str) => /\d/.test(str);
            const hasSpecial = (str) => /[!@#$%^&*(),.?":{}|<>]/.test(str);

            // === Current password ===
            if (!current) {
                $('#current_Feedback').text('Current password is required.').css("display", "inline");
                valid = false;
            } else {
                $('#current_Feedback').text("").css("display", "none");
            }


            // === New password ===
            if (!newPassword) {
                $('#new_Feedback').text('New password is required.').css("display", "inline");
                $('#new').val('') ;
                valid = false;
            } else if (newPassword.length < 6 || newPassword.length > 15) {
                $('#new_Feedback').text('Password must be between 6 and 15 characters.').css("display", "inline");
                $('#new').val('') ;
                valid = false;
            } else if (!hasLetter(newPassword) || !hasNumber(newPassword) || !hasSpecial(newPassword)) {
                $('#new_Feedback').text('Password must include a letter, number, and special character.').css("display", "inline");
                $('#new').val('') ;
                valid = false;
            } else {
                $('#new_Feedback').text("").css("display", "none");
            }

            // === Confirm password ===
            if (!confirm) {
                $('#confirm_Feedback').text('Please confirm your new password.').css("display", "inline");
                $('#confirm').val('') ; 
                valid = false;
            } else if (confirm.length < 6 || confirm.length > 15) {
                $('#confirm_Feedback').text('Password must be between 6 and 15 characters.').css("display", "inline");
                $('#confirm').val('') ; 
                valid = false;
            } else if (!hasLetter(confirm) || !hasNumber(confirm) || !hasSpecial(confirm)) {
                $('#confirm_Feedback').text('Password must include a letter, number, and special character.').css("display", "inline");
                $('#confirm').val('') ; 
                valid = false;
            } else {
                $('#confirm_Feedback').text("").css("display", "none");
            }

            if (!valid) {
                console.log("Please enter valid data");
                valid = true ; 
                return;
            }

            
            if (newPassword.length != confirm.length || newPassword != confirm) {
                $('#confirm_Feedback').text('Password does not match').css("display", "inline");
                $('#confirm').val('') ; 
                valid = false ; 
            }
    
            if (!valid) {
                console.log("Please enter valid data");
                return;
            }



            // Prepare form data
            let formData = new FormData();
            formData.append("csrfToken", $('#csrfToken').val());
            formData.append('current_password', current);
            formData.append('new_password', newPassword);
            formData.append('confirm_password', confirm);
            // AJAX CALL
            $.ajax({
                url: '<?php echo CONTROLLERS_URL; ?>9_ChangePassword.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    // Handle success response
                    console.log(response);
                    alert("Password changed successfully.");
                    window.location.href = "profile.php" ; 
                },
                error: function (xhr) {
                    // Handle error
                    console.log("Status Code : " + xhr.status);
                    console.log("Response : " + xhr.responseText);
                    $('#current_Feedback, #new_Feedback, #confirm_Feedback').text("").css("display", "none");
                    const errorResponse = JSON.parse(xhr.responseText);
                    if(xhr.status == 403) {
                        alert(errorResponse.message) ;
                        window.location.reload() ;
                        return ; 
                    } else if(xhr.status == 400) {
                        if(errorResponse.message.includes("fields")) {
                            alert("Marked fileds cannot be empty") ; 
                            $('#current_Feedback, #new_Feedback, #confirm_Feedback').text("Marked fileds cannot be empty").css("display", "inline");
                            return ; 
                        } else if(errorResponse.message.includes("characters")) {
                            alert(errorResponse.message) ; 
                            $('#new_Feedback, #confirm_Feedback').text(errorResponse.message).css("display", "inline");
                            $('#new').val('') ;
                            $('#confirm').val('') ; 
                            return ; 
                        } else if(errorResponse.message.includes("letter")) {
                            alert(errorResponse.message) ; 
                            $('#new_Feedback, #confirm_Feedback').text(errorResponse.message).css("display", "inline");
                            $('#new').val('') ;
                            $('#confirm').val('') ; 
                            return ; 
                        } else if(errorResponse.message.includes("does not match")) {
                            alert(errorResponse.message) ; 
                            $('#new_Feedback, #confirm_Feedback').text(errorResponse.message).css("display", "inline");
                            $('#confirm').val('') ; 
                            return ; 
                        }
                    } else if(xhr.status == 401) {
                        if(errorResponse.message.includes("Unauthorized")) {
                            alert(errorResponse.message) ; 
                            window.location.reload() ; 
                            return ; 
                        } else {
                            alert(errorResponse.message) ; 
                            $('#current').val('') ; 
                            $('#current_Feedback').text(errorResponse.message).css("display", "inline");
                            return ; 
                        }
                    } else {
                        alert(errorResponse.message || 'Internal Server Error !');
                        return ; 
                    }
                }
            });
        });
    });

</script>
<?php require_once __DIR__ . '/footer.php'; ?>