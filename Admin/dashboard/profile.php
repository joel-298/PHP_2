<?php
$PAGE_TITLE = "Admin-Profile";
require_once __DIR__ . "/header.php";
// CREATE A CSRF TOKEN 
if (empty($_SESSION['csrfToken'])) {
    $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
}


$userId = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : '' ;
if(empty($userId)) {
    header("Location: ./login.php");
    exit;
}



// FETCH USER DETALS HERE AND THEN DISPLAY THEM DOWN BELOW IN THE FORM
$user = [
    'first_name' => null,
    'last_name' => null,
    'email' => null,
    'phone' => null,
    'profile_image' => null,
    'address' => null
];
if ($userId) {
    // Fetch user details
    $stmt = $connection->prepare("SELECT first_name, last_name, email, phone, profile_image, address FROM admin WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
} else {
    header("Location: ./login.php");
    exit;
}



// FOR IMAGE VALIDATION 
$allowedTypes = json_decode(ALLOWED_IMAGE_TYPES, true);
// Handle decode error
if (!is_array($allowedTypes)) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg']; // fallback
}
$allowedExtensions = array_map(function($mime) {
    return '.' . explode('/', $mime)[1];
}, $allowedTypes);
$acceptAttr = implode(',', $allowedExtensions);

?>



<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="container mt-5 ">
        <div class="row parent">
            <!-- Profile Form -->
            <div class="col-md-6 px-5" id="div1">
                <form id="adminProfileForm" enctype="multipart/form-data">
                    <!-- CSRF TOKEN -->
                    <input type="hidden" id="csrfToken" name="csrfToken"
                        value="<?php echo htmlspecialchars($_SESSION['csrfToken']); ?>">
                    <div class="text-center mb-4 d-flex justify-content-between align-items-center" id="display">
                        <img src="<?php echo $user['profile_image'] ?? 'https://png.pngtree.com/png-vector/20240529/ourlarge/pngtree-web-programmer-avatar-png-image_12529202.png'; ?>" alt="Profile Image" class="rounded-circle img-fluid" alt="Avatar" style="width: 100px; height: 100px; object-fit: cover;">
                        <h3 class="mt-2">Admin Profile</h3>
                    </div>
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES); ?>">
                        <div class="invalid-feedback" id="first_name_Feedback">TEXT</div>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES); ?>">
                        <div class="invalid-feedback" id="last_name_Feedback">TEXT</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES); ?>">
                        <div class="invalid-feedback" id="email_Feedback">TEXT</div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES); ?>">
                        <div class="invalid-feedback" id="phone_Feedback">TEXT</div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($user['address'] ?? '', ENT_QUOTES); ?></textarea>
                        <div class="invalid-feedback" id="address_Feedback">TEXT</div>
                    </div>
                    <div class="mb-3">
                        <label for="profile_photo" class="form-label">Upload Profile Photo</label>
                        <input type="file"
                            class="form-control"
                            id="profile_image"
                            name="profile_image"
                            accept="<?php echo htmlspecialchars($acceptAttr); ?>"
                            data-max-size="<?php echo MAX_IMAGE_SIZE; ?>">
                            <div class="text-muted mt-2 small">
                                Max file size: 2MB. Allowed formats: 
                                <?php
                                $allowedTypes = json_decode(ALLOWED_IMAGE_TYPES, true);
                                $extensions = array_map(function($mime) {
                                    return '.' . explode('/', $mime)[1];
                                }, $allowedTypes);
                                echo implode(', ', $extensions);
                                ?>.
                            </div>
                        <div class="invalid-feedback" id="profile_photo">TEXT</div>
                        <button type="button" id="previewButton" class="btn btn-secondary bg-success btn-sm mt-2" disabled>Preview Image</button>
                    </div>
                    <div class="mb-3 text-start mt-4">
                        <button type="submit" class="btn btn-primary">Save Profile</button>
                    </div>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="col-md-6 px-5" id="div2">
                <h3 class="mt-2 text-end">Change Password</h3>
                <form id="changePasswordForm">
                    <!-- CSRF TOKEN -->
                    <input type="hidden" id="csrfTokenPassword" name="csrfToken"
                        value="<?php echo htmlspecialchars($_SESSION['csrfToken']); ?>">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="current_Feedback">TEXT</div>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <button type="button" class="btn btn-outline-secondary" id="toggleNewPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="newPassword_Feedback">TEXT</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password">
                            <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="confirmPassword_Feedback">TEXT</div>
                    </div>
                    <div class="mb-3 text-start mt-4">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

</div>
</div>


<script>
    // TOGGLE PASSWORD VISIBILITY
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#current_password');
    const toggleNewPassword = document.querySelector('#toggleNewPassword');
    const NewPassword = document.querySelector('#new_password');
    const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
    const ConfirmPassword = document.querySelector('#confirm_new_password');

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

    // DYNAMIC PHOTO CHANGE
    const allowedTypes = <?php echo ALLOWED_IMAGE_TYPES; ?>;
    const maxSize = <?php echo MAX_IMAGE_SIZE; ?>;
    const profileImageInput = $('#profile_image');
    const previewButton = $('#previewButton');
    const previewImageElement = $('#previewImage');
    const profilePhotoFeedback = $('#profile_photo');

    $(function () {
        $('#profile_image').on('change', function () {
            const file = this.files[0];
            // Clear previous preview and disable the button if no file is selected
            if (!file) {
                previewButton.prop('disabled', true);
                previewImageElement.hide().attr('src', '#');
                profilePhotoFeedback.css('display', 'none');
                return;
            }
            let validationPassed = true;

            if (file) {
                // Validate file type
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload a valid image (JPEG, JPG, PNG, GIF, or WEBP).');
                    $('#profile_photo').text('Invalid image type.').css('display', 'inline');
                    validationPassed = false;
                    return;
                } else {
                    $('#profile_photo').css('display', 'none');
                }

                // Validate file size
                if (file.size > maxSize) {
                    alert('Image size should not exceed 2MB.');
                    $('#profile_photo').text('Image size should not exceed 2MB.').css('display', 'inline');
                    validationPassed = false;
                    return;
                } else {
                    $('#profile_photo').css('display', 'none');
                }
                if (validationPassed) {
                    profilePhotoFeedback.css('display', 'none');
                    previewButton.prop('disabled', false);
                } else {
                    previewButton.prop('disabled', true);
                }
            }
        });
        // Event listener for the "Preview" button click
        previewButton.on('click', function () {
            const file = profileImageInput[0].files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    // Update the dedicated preview image
                   $('img[alt="Profile Image"]').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);

                // Disable the button after previewing
                previewButton.prop('disabled', true);
            }
        });

        // ADMIN PROFILE FORM SUBMIT 
        $('#adminProfileForm').submit(function (e) {
            e.preventDefault();

            // Sanitize function
            function sanitize(input) {
                return $('<div>').text(input).html().trim();
            }

            // Fetch and sanitize inputs
            let first_name = sanitize($('#first_name').val());
            let last_name = sanitize($('#last_name').val());
            let email = sanitize($('#email').val()) ;  
            let phone = sanitize($('#phone').val());
            let address = sanitize($('#address').val()) ; 
            let csrfToken = $('#csrfToken').val();
            let imageFile = $('#profile_image')[0].files[0];


            // Restore current password's form data
            $('#current_Feedback, #newPassword_Feedback, #confirmPassword_Feedback').text("").css("display", "none");
            const current = $('#current_password').val("");
            const newPassword = $('#new_password').val("");
            const confirm = $('#confirm_new_password').val("");
            // === Validation ===
            let hasError = false;


            if (!first_name) {
                // console.log("FIRST NAME ERROR") ;
                $('#first_name_Feedback').text('First name is required.').css('display', 'inline');
                hasError = true;
            } else if(first_name.length > 100) {
                $('#first_name_Feedback').text('First name too large').css('display', 'inline');
                hasError = true;
            } else {
                $('#first_name_Feedback').css('display', 'none');
            }

            if (!last_name) {
                // console.log("LAST NAME ERROR"); 
                $('#last_name_Feedback').text('Last name is required.').css('display', 'inline');
                hasError = true;
            } else if(last_name.length > 100) {
                $('#last_name_Feedback').text('Last name too large').css('display', 'inline');
                hasError = true;
            } else {
                $('#last_name_Feedback').css('display', 'none');
            }

            if (!email) {
                $('#email_Feedback').text("Email Field Cannot be Empty !").css('display', 'inline');
                hasError = true;
            } else if(email.length > 100) {
                $('#email_Feedback').text("Email too large, must be less than 100 characters !").css('display', 'inline');
                hasError = true;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $('#email_Feedback').text("Please enter a valid email address !").css('display', 'inline');
                hasError = true;
            } else {
                $('#email_Feedback').css('display', 'none');
            }

            // Validate phone number: 10 digits only
            let phonePattern = /^[0-9]{10}$/;
            if (phone !== "" && !phonePattern.test(phone)) {
                $('#phone_Feedback').text("Please enter a valid phone number !").css('display', 'inline');
                hasError = true; 
            } else {
                $('#phone_Feedback').css('display', 'none');
            }

            if (address !== "") {
                let addressPattern = /^[a-zA-Z0-9\s,.'#\-\/()&]+$/;

                if (address.length < 20 || address.length > 500  || !addressPattern.test(address)){
                    $('#address_Feedback').text("Please enter a valid address (20–255 characters, avoid special characters like @, $, %, etc).").css('display', 'inline');
                    hasError = true;
                } else {
                    $('#address_Feedback').css('display', 'none');
                }
            } else {
                // Address is optional
                $('#address_Feedback').css('display', 'none');
            }



            if (imageFile) {
                if (!allowedTypes.includes(imageFile.type)) {
                    // console.log("IMAGE FILE TYPE ERROR") ; 
                    $('#profile_photo').text('Invalid image type.').css('display', 'inline');
                    hasError = true;
                } else if (imageFile.size > maxSize) {
                    // console.log("SIZE ERROR") ; 
                    $('#profile_photo').text('Image exceeds ' + (maxSize / 1024 / 1024) + 'MB.').css('display', 'inline');
                    hasError = true;
                } else {
                    $('#profile_photo').css('display', 'none');
                }
            }

            if (hasError) {
                console.log("has errors") ;  
                return;
            }
            $('#first_name_Feedback, #last_name_Feedback, #phone_Feedback , #email_Feedback , #profile_photo , #address_Feedback').css('display','none') ;





            // Prepare form data
            let formData = new FormData();
            formData.append('csrfToken', csrfToken);
            formData.append('first_name', first_name);
            formData.append('last_name', last_name);
            formData.append('email', email);
            if (phone) formData.append('phone', phone);
            if (address) formData.append('address', address);
            if (imageFile) formData.append('profile_image', imageFile);
            formData.append("function","editProfile") ; 

            // AJAX CALL
            $.ajax({
                url: './front_ajax.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    // Handle success response
                    console.log(response);
                    alert("Profile updated successfully.");
                    location.reload();
                },
                error: function (xhr) {
                    // Handle error
                    console.log("Status Code : " + xhr.status);
                    console.log("Response : " + xhr.responseText);
                    const errorResponse = JSON.parse(xhr.responseText);
                    $('#first_name_Feedback, #last_name_Feedback, #phone_Feedback , #email_Feedback , #profile_photo , #address_Feedback').css('display','none') ;
                    if(xhr.status == 403) {
                        alert(errorResponse.message) ;
                        window.location.reload() ;
                        return ;  
                    } else if(xhr.status == 401) {
                        alert(errorResponse.message) ; 
                        window.location.reload() ; 
                        return ; 
                    } else if(xhr.status == 400) {
                        if(errorResponse.message.includes("fields")) {
                            alert(errorResponse.message) ; 
                            $('#first_name_Feedback, #last_name_Feedback , #email_Feedback').text("Marked fields cannot be empty").css('display','inline') ; 
                            return ;
                        } else if (errorResponse.message.includes("name")) {
                            alert(errorResponse.message) ; 
                            $('#first_name_Feedback, #last_name_Feedback').text(errorResponse.message).css('display','inline') ; 
                            return ;
                        } else if (errorResponse.message.includes("Email")) {
                            alert(errorResponse.message) ; 
                            $('#email_Feedback').text(errorResponse.message).css('display','inline') ; 
                            return ; 
                        } else if (errorResponse.message.includes("Phone")) {
                            alert(errorResponse.message) ; 
                            $('#phone_Feedback').text(errorResponse.message).css('display','inline') ; 
                            return ; 
                        } else if (errorResponse.message.includes("image") || errorResponse.message.includes("Image")) {
                            alert(errorResponse.message) ; 
                            $('#profile_photo').text(errorResponse.message).css('display','inline') ; 
                            return ;
                        } else {
                            alert(errorResponse.message) ; 
                            window.location.reload() ; 
                            return ; 
                        }
                    } else if(xhr.status == 409) {
                        alert(errorResponse.message); 
                        window.location.reload() ; 
                        return ; 
                    } else if(xhr.status == 404) {
                        alert(errorResponse.message) ; 
                        window.location.reload() ; 
                        return ; 
                    } else {
                        alert(errorResponse.message || 'Internal Server Error !');
                        return ; 
                    }
                }
            });
        });


        // CHANGE PASSWORD FORM SUBMIT 
        $('#changePasswordForm').submit(function (e) {
            e.preventDefault();

            // Sanitize function
            function sanitize(input) {
                return $('<div>').text(input).html().trim();
            }

            $('#first_name_Feedback, #last_name_Feedback, #phone_Feedback , #email_Feedback , #profile_photo , #address_Feedback').css('display','none') ;
            $('#first_name').val("<?php echo $user['first_name'] ?>");
            $('#last_name').val("<?php echo $user['last_name'] ?>");
            $('#email').val("<?php echo $user['email'] ?>") ;  
            $('#phone').val("<?php echo isset($user['phone']) ? $user['phone'] : "" ?>");
            $('#address').val("<?php echo isset($user['address']) ? $user['address'] : "" ?>") ; 
        

            // == Validations == 
            let valid = true;
            $('#current_Feedback, #newPassword_Feedback, #confirmPassword_Feedback').text("").css("display", "none");
            const current = $('#current_password').val().trim();
            const newPassword = $('#new_password').val().trim();
            const confirm = $('#confirm_new_password').val().trim();

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
                $('#newPassword_Feedback').text('New password is required.').css("display", "inline");
                valid = false;
            } else if (newPassword.length < 6 || newPassword.length > 15) {
                $('#newPassword_Feedback').text('Password must be between 6 and 15 characters.').css("display", "inline");
                $('#new_password').val('');
                valid = false;
            } else if (!hasLetter(newPassword) || !hasNumber(newPassword) || !hasSpecial(newPassword)) {
                $('#newPassword_Feedback').text('Password must include a letter, number, and special character.').css("display", "inline");
                $('#new_password').val('');
                valid = false;
            } else {
                $('#newPassword_Feedback').text("").css("display", "none");
            }

            // === Confirm password ===
            if (!confirm) {
                $('#confirmPassword_Feedback').text('Please confirm your new password.').css("display", "inline");
                valid = false;
            } else if (confirm.length < 6 || confirm.length > 15) {
                $('#confirmPassword_Feedback').text('Password must be between 6 and 15 characters.').css("display", "inline");
                $('#confirm_new_password').val('');
                valid = false;
            }  else if (!hasLetter(newPassword) || !hasNumber(newPassword) || !hasSpecial(newPassword)) {
                $('#confirmPassword_Feedback').text('Password must include a letter, number, and special character.').css("display", "inline");
                $('#confirm_new_password').val('');
                valid = false;
            }  else {
                $('#confirmPassword_Feedback').text("").css("display", "none");
            }



            if (!valid) {
                console.log("Please enter valid data");
                valid = true ; 
                return;
            }
            if (newPassword.length != confirm.length || newPassword != confirm) {
                $('#confirmPassword_Feedback').text('Password does not match').css("display", "inline");
                $('#confirm_new_password').val('');
                valid = false ; 
            }

            if (!valid) {
                console.log("Please enter valid data");
                return;
            }

            if(newPassword.length == confirm.length && newPassword == confirm) {
                $('#confirmPassword_Feedback, #newPassword_Feedback, #confirm_Feedback').text("").css("display", "none");
            }



            // Prepare form data
            let formData = new FormData();
            formData.append("csrfToken", $('#csrfTokenPassword').val());
            formData.append('current_password', current);
            formData.append('new_password', newPassword);
            formData.append('confirm_password', confirm);
            formData.append('function','changePassword') ; 
            // AJAX CALL
            $.ajax({
                url: 'front_ajax',
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
                    $('#current_Feedback, #newPassword_Feedback, #confirmPassword_Feedback').text("").css("display", "none");
                    const errorResponse = JSON.parse(xhr.responseText);
                    if(xhr.status == 403) {
                        alert(errorResponse.message) ;
                        window.location.reload() ;
                        return ; 
                    } else if(xhr.status == 400) {
                        if(errorResponse.message.includes("fields")) {
                            alert("Marked fields cannot be empty.") ; 
                            $('#current_Feedback, #newPassword_Feedback, #confirmPassword_Feedback').text("This field cannot be empty").css("display", "inline");
                            return ; 
                        } else if(errorResponse.message.includes("characters")) {
                            alert(errorResponse.message) ; 
                            $('#newPassword_Feedbac').text(errorResponse.message).css("display", "inline");
                            $('#new_password').val('');
                            $('#confirm_new_password').val('');
                            return ; 
                        } else if(errorResponse.message.includes("letter")) {
                            alert(errorResponse.message) ; 
                            $('#newPassword_Feedback').text(errorResponse.message).css("display", "inline");
                            $('#new_password').val('');
                            $('#confirm_new_password').val('');
                            return ; 
                        } else if(errorResponse.message.includes("does not match")) {
                            alert(errorResponse.message) ; 
                            $('#confirmPassword_Feedback').text(errorResponse.message).css("display", "inline");
                            $('#new_password').val('');
                            $('#confirm_new_password').val('');
                            return ; 
                        }
                    } else if(xhr.status == 401) {
                        if(errorResponse.message.includes("Unauthorized")) {
                            alert(errorResponse.message) ; 
                            window.location.reload() ; 
                            return ; 
                        } else {
                            alert(errorResponse.message) ; 
                            $('#current_Feedback').text(errorResponse.message).css("display", "inline");
                            $('#current_password').val('');
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
<?php require_once __DIR__ . "/footer.php"; ?>