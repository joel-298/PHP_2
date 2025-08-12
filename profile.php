<?php
$PAGE_TITLE = "Profile";
require_once __DIR__ . '/header.php';
// CREATE A CSRF TOKEN 
if (empty($_SESSION['csrfToken'])) {
    $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
}


$userId = isset($_SESSION['id']) ? $_SESSION['id'] : '' ;
if(empty($userId)) {
    header("Location: ./login.php");
    exit;
}


// FETCH ALL AVAILABLE THE SKILLS FROM THE DB
$allSkills = [];
$skillsQuery = $connection->query("SELECT id, name FROM skills");
while ($row = $skillsQuery->fetch_assoc()) {
    $allSkills[] = $row;
}





// FETCH USER DETALS HERE AND THEN DISPLAY THEM DOWN BELOW IN THE FORM
$user = [
    'first_name' => null,
    'last_name' => null,
    'email' => null,
    'dob' => null,
    'profile_image' => null,
    'skills' => []
];
if ($userId) {
    // Fetch user details
    $stmt = $connection->prepare("SELECT first_name, last_name, email, dob, profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();

    // For displaying in change password page (just for ui purpose)
    $_SESSION['first_name'] = $user['first_name'] ; 
    $_SESSION['last_name'] = $user['last_name'] ;
    $_SESSION['profile_image'] = $user['profile_image'] ; 
    $_SESSION['email'] = $user['email'] ;
 
    // Fetch user's skills
    $skillsStmt = $connection->prepare("
        SELECT s.name 
        FROM user_skills us 
        JOIN skills s ON s.id = us.skill_id 
        WHERE us.user_id = ?
    ");
    $skillsStmt->bind_param("i", $userId);
    $skillsStmt->execute();
    $skillsResult = $skillsStmt->get_result();
    $user['skills'] = [];
    while ($row = $skillsResult->fetch_assoc()) {
        $user['skills'][] = $row['name'];  // 'name' column from skills table
    }
    $skillsStmt->close();
} else {
    header("Location: ./signup.php");
    exit;
}



// FOR IMAGE VALIDATION 
$allowedTypes = json_decode(ALLOWED_IMAGE_TYPES, true);
// Handle decode error
if (!is_array($allowedTypes)) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp' , 'image/jpg']; // fallback
}
$allowedExtensions = array_map(function($mime) {
    return '.' . explode('/', $mime)[1];
}, $allowedTypes);
$acceptAttr = implode(',', $allowedExtensions);
?>

<!-- Overall Container -->
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-11 col-md-12">
            <div class="row g-4">
                <!-- Profile Sidebar -->
                <div class="col-md-4 text-center" id="first_div">
                    <img src="<?php echo $user['profile_image'] ?? 'https://png.pngtree.com/png-vector/20240529/ourlarge/pngtree-web-programmer-avatar-png-image_12529202.png'; ?>" alt="Profile Image" class="rounded-circle img-fluid mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <h5 class="mb-0" id="name_display"><?php echo htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES); ?> <?php echo htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES); ?></h5>
                    <p class="text-muted small" id="email_display"><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES); ?></p>
                </div>

                <!-- Profile Form -->
                <div class="col-md-8 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Profile</h4>
                        <a href="changePassword.php" class="btn btn-primary btn-sm px-2 load_ajax">Change Password</a>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <!-- CSRF TOKEN -->
                        <input type="hidden" id="csrfToken" name="csrfToken" value="<?php echo htmlspecialchars($_SESSION['csrfToken']); ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES); ?>" required>
                                <div class="invalid-feedback" id="first_name_Feedback">TEXT</div>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES); ?>" required>
                                <div class="invalid-feedback" id="last_name_Feedback">TEXT</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="dob" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? '', ENT_QUOTES); ?>" min="1900-01-01" max="<?php echo date('Y-m-d'); ?>" >
                                <div class="invalid-feedback" id="dob_Feedback">TEXT</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Profile Photo</label>
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

                        <div class="mb-3">
                            <label class="form-label d-block">Skills</label>
                            <?php foreach ($allSkills as $skill): 
                                $skillName = htmlspecialchars($skill['name']);
                                $isChecked = in_array($skill['name'], $user['skills']);
                            ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input checkbox" 
                                        type="checkbox" 
                                        id="skill_<?php echo $skill['id']; ?>" 
                                        name="skills[]" 
                                        value="<?php echo $skillName; ?>"
                                        <?php echo $isChecked ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="skill_<?php echo $skill['id']; ?>">
                                        <?php echo $skillName; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
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
    // DYNAMIC PHOTO CHANGE
    const allowedTypes = <?php echo ALLOWED_IMAGE_TYPES; ?>;
    const maxSize = <?php echo MAX_IMAGE_SIZE; ?>;
    // DYNAMIC SKILLS ARRAY
    let selectedSkills = <?php echo json_encode($user['skills']); ?>;
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




        // DYNAMIC SKILLS ARRAY
        $(document).on('change', '.checkbox', function () {
            const skill = $(this).val();
            if ($(this).is(':checked')) {
                if (!selectedSkills.includes(skill)) selectedSkills.push(skill);
            } else {
                selectedSkills = selectedSkills.filter(s => s !== skill);
            }
            console.log('Updated Skills:', selectedSkills);
        });



        // FORM SUBMIT 
        $('form').submit(function (e) {
            e.preventDefault();

            // Sanitize function
            function sanitize(input) {
                return $('<div>').text(input).html().trim();
            }

            // Fetch and sanitize inputs
            let first_name = sanitize($('#first_name').val());
            let last_name = sanitize($('#last_name').val());
            let dob = $('#dob').val();
            let csrfToken = $('#csrfToken').val();
            let imageFile = $('#profile_image')[0].files[0];

            // // === Validation ===
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

            if (dob && new Date(dob) > new Date()) {
                // console.log("DOB ERROR"); 
                $('#dob_Feedback').text('Date of birth cannot be in the future.').css('display', 'inline');
                hasError = true;
            } else if (dob && new Date(dob).getFullYear() < 1900) {
                $('#dob_Feedback').text('Date of birth cannot be before the year 1900.').css('display', 'inline');
                hasError = true;
            } else {
                $('#dob_Feedback').css('display', 'none');
            }

            if (imageFile) {
                if (!allowedTypes.includes(imageFile.type)) {
                    // console.log("IMAGE FILE TYPE ERROR") ; 
                    $('#profile_photo').text('Invalid image type.').css('display', 'inline');
                    hasError = true;
                } else if (imageFile.size > maxSize) {
                    console.log("SIZE ERROR") ; 
                    $('#profile_photo').text('Image exceeds ' + (maxSize / 1024 / 1024) + 'MB.').css('display', 'inline');
                    hasError = true;
                } else {
                    $('#profile_photo').css('display', 'none');
                }
            }

            if (hasError) return;
            $('#first_name_Feedback, #last_name_Feedback, #dob_Feedback , #profile_photo').css('display','none') ;





            // Prepare form data
            let formData = new FormData();
            formData.append('csrfToken', csrfToken);
            formData.append('first_name', first_name);
            formData.append('last_name', last_name);
            if (dob) formData.append('dob', dob);
            if (imageFile) formData.append('profile_image', imageFile);
            formData.append('skills', JSON.stringify(selectedSkills)); // Send array as JSON

            // AJAX CALL
            $.ajax({
                url: '<?php echo CONTROLLERS_URL ;?>8_EditProfile.php',
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
                    $('#first_name_Feedback, #last_name_Feedback, #dob_Feedback , #profile_photo').css('display','none') ;
                    if(xhr.status == 403) {
                        alert(errorResponse.message) ;
                        window.location.reload() ;
                        return ;  
                    } else if(xhr.status == 401) {
                        alert(errorResponse.message) ; 
                        window.location.reload() ; 
                        return ; 
                    } else if(xhr.status == 400) {
                        if(errorResponse.message.includes("name")) {
                            alert(errorResponse.message) ; 
                            $('#first_name_Feedback, #last_name_Feedback').text("Marked fields cannot be empty").css('display','inline') ; 
                            return ;
                        } else if (errorResponse.message.includes("image") || errorResponse.message.includes("Image")) {
                            alert(errorResponse.message) ; 
                            $('#profile_photo').text(errorResponse.message).css('display','inline') ; 
                            return ;
                        } else if (errorResponse.message.includes("Date")) {
                            alert(errorResponse.message) ;    
                            $('#dob_Feedback').text(errorResponse.message).css("display",'inline') ;     
                            return ; 
                        } else {
                            alert(errorResponse.message) ; 
                            window.location.reload() ; 
                            return ; 
                        }
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
    });



</script>
<?php require_once __DIR__ . '/footer.php'; ?>