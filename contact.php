<?php
$PAGE_TITLE = "Contact-us";
require_once __DIR__ . '/header.php';
// CREATE A CSRF TOKEN 
if (empty($_SESSION['csrfToken'])) {
    $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
}
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 mb-4">
            <img src="https://orientaloutsourcing.com/images/contact.png" class="img-fluid mb-3" alt="Contact">
            <h5 class="sample_email">Email&nbsp;:&nbsp;<a href="mailto:someone@example.com">someone@example.com</a></h5>
            <h5 class="sample_email">Phone&nbsp;:&nbsp;<a href="tel:1234567890">1234567890</a></h5>
        </div>
        <div class="col-md-6">
            <form id="contactUsForm">
                <!-- CSRF TOKEN -->
                <input type="hidden" id="csrfToken" name="csrfToken" value="<?php echo htmlspecialchars($_SESSION['csrfToken']); ?>">

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <span class="span" id="nameWarning">*</span><br>
                    <input type="text" class="form-control validate[required,maxSize[100]]" id="name" name="name" value="">
                    <div class="invalid-feedback" id="nameFeedback">TEXT</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <span class="span" id="emailWarning">*</span><br>
                    <input type="email" class="form-control validate[required,custom[email],maxSize[100]]" id="email" name="email" value="" />
                    <div class="invalid-feedback" id="emailFeedback">TEXT</div>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <span class="span" id="phoneWarning">*</span><br>
                    <input type="text" class="form-control validate[required,custom[phone],maxSize[20]]" id="phone" name="phone" value="" />
                    <div class="invalid-feedback" id="phoneFeedback">TEXT</div>
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <span class="span" id="messageWarning">*</span><br>
                    <textarea class="form-control validate[required,maxSize[500]]" id="message" name="message" rows="2"></textarea>
                    <div class="invalid-feedback" id="messageFeedback">TEXT</div>
                </div>

                <div class="mb-3">
                    <label for="file" class="form-label">Upload File</label>
                    <span class="span" id="fileWarning">*</span><br>
                    <input type="file" id="file" name="file" accept=".pdf,.docs,.xlxs" class="validate[required]" /><br>
                    <small class="text-muted small">INSTRUCTIONS : Only .pdf, .docx and .xlsx files are allowed, max file size is 5 MB.</small>
                    <br><div class="invalid-feedback" id="fileFeedback">TEXT</div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:15px;">Submit</button>
            </form>
        </div>
    </div>
</div>

<script>
    $(function () {
        $('#contactUsForm').submit(function (e) {
            e.preventDefault();

            // FORM VALIDATION ! 
            function sanitizeInput(input) {
                return input.replace(/[<>\/\\'"`;%(){}[\]=+&^$|]/g, "").trim();
            }

            let name = sanitizeInput($('#name').val());
            let email = sanitizeInput($('#email').val());
            let phone = sanitizeInput($('#phone').val());
            let message = sanitizeInput($('#message').val());
            let file = $('#file')[0].files[0];

            let valid = true;
            if (!name) {
                $('#nameFeedback').addClass("display");
                $('#nameFeedback').text("Name Field Cannot be Empty !");
                valid = false;
            } else {
                $('#nameFeedback').removeClass("display");
            }

            if (!email) {
                $('#emailFeedback').addClass("display");
                $('#emailFeedback').text("Email Field Cannot be Empty !");
                valid = false;
            } else {
                $('#emailFeedback').removeClass("display");
            }

            if (!phone) {
                $('#phoneFeedback').addClass("display");
                $('#phoneFeedback').text("Phone Field Cannot be Empty !");
                valid = false;
            } else {
                $('#phoneFeedback').removeClass("display");
            }

            const hasAlphabets = /[a-zA-Z]/.test(phone);
            const hasSpecialChars = /[^0-9+]/.test(phone); // allows only digits and '+'
            if (phone.length < 10 || phone.length > 15 || hasAlphabets || hasSpecialChars) {
                $('#phoneFeedback').addClass("display");
                $('#phoneFeedback').text("Please enter a valid phone number!");
                valid = false;
            } else {
                $('#phoneFeedback').removeClass("display");
            }


            if (!message) {
                $('#messageFeedback').addClass("display");
                $('#messageFeedback').text("Message Field Cannot be Empty !");
                valid = false;
            } else {
                $('#messageFeedback').removeClass("display");
            }

            // File validations 
            if (file) {
                const allowedTypes = <?php echo ALLOWED_FILE_TYPES; ?>; 
                const maxSize = <?php echo MAX_FILE_SIZE; ?>;
                if (!allowedTypes.includes(file.type)) {
                    $('#fileFeedback').addClass('display');
                    $('#fileFeedback').text("Invalid File Type !");
                    valid = false;
                } else if (file.size > maxSize) {
                    $('#fileFeedback').addClass('display');
                    $('#fileFeedback').text("File too Large ! Max 5MB");
                    valid = false;
                } else {
                    $('#fileFeedback').removeClass('display');
                }
            } else {
                $('#fileFeedback').addClass('display');
                $('#fileFeedback').text("File field cannot be empty !");
                valid = false
            }
            if (!valid) {
                // alert("Please Enter Valid Data !") ;
                return;
            }
            // Remove Stars from the input fields ! 
            $('#nameFeedback, #emailFeedback, #phoneFeedback, #messageFeedback, #fileFeedback').removeClass("display");

            // FORM DATA 
            let formData = new FormData();
            formData.append("name", name);
            formData.append("email", email);
            formData.append("phone", phone);
            formData.append("message", message);
            formData.append("file", file);
            formData.append("csrfToken", $('#csrfToken').val()); // 🔐 this is the only way to send file!
            $.ajax({
                url: "<?php echo CONTROLLERS_URL ;?>2_save_contact_ajax.php",
                type: "POST",
                data: formData,  // 🔐 Send CSRF token
                contentType: false,
                processData: false,
                success: function (response) {
                    console.log(response) ; 
                    $('#nameFeedback, #emailFeedback, #phoneFeedback, #messageFeedback, #fileFeedback').text(response.message).removeClass("display");
                    alert(response.message);
                    $('#name').val("");
                    $('#email').val("");
                    $('#phone').val("");
                    $('#message').val("");
                    $('#file').val("");
                },
                error: function (xhr) {
                    console.log("Status Code: " + xhr.status);
                    console.log("Response: " + xhr.responseText);
                    const errorResponse = JSON.parse(xhr.responseText);
                    $('#nameFeedback, #emailFeedback, #phoneFeedback, #messageFeedback, #fileFeedback').text(errorResponse.message).removeClass("display");
                    if(xhr.status == 403) {
                        alert(errorResponse.message) ;
                        window.location.reload() ;
                        return ;  
                    } else if(xhr.status == 400) {
                        if(errorResponse.message.includes("Fields")) {
                            alert(errorResponse.message) ; 
                            $('#nameFeedback, #emailFeedback, #phoneFeedback, #messageFeedback, #fileFeedback').text(errorResponse.message).addClass("display");
                            return ; 
                        } else if(errorResponse.message.includes("Name")) {
                            alert(errorResponse.message) ; 
                            $('#nameFeedback').text(errorResponse.message).addClass("display") ; 
                            return ; 
                        } else if(errorResponse.message.includes("Email")) {
                            alert(errorResponse.message) ; 
                            $('#emailFeedback').text(errorResponse.message).addClass("display") ; 
                            return ; 
                        } else if(errorResponse.message.includes("Message")) {
                            alert(errorResponse.message) ; 
                            $('#messageFeedback').text(errorResponse.message).addClass("display") ; 
                            return ; 
                        } else if(errorResponse.message.includes("Phone")) {
                            alert(errorResponse.message) ; 
                            $('#phoneFeedback').text(errorResponse.message).addClass("display") ; 
                            return ; 
                        } else if(errorResponse.message.includes("File")) {
                            alert(errorResponse.message) ; 
                            $('#fileFeedback').text(errorResponse.message).addClass("display") ; 
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