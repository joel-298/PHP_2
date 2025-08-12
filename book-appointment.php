<?php
$PAGE_TITLE = "Book Appointment";
require_once __DIR__ . '/header.php';
// CREATE A CSRF TOKEN
if (empty($_SESSION['csrfToken'])) {
    $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4 mb-4 d-flex ">
            <img src="https://orientaloutsourcing.com/images/contact.png" class="img-fluid mx-auto mb-3" style="height:290px !important;" alt="Contact">
        </div>

        <div class="col-md-8 p-4 book_appointment">
            <h3 class="mb-4">Book Appointment</h3>
            <form id="appointmentForm" enctype="multipart/form-data">
                <!-- CSRF TOKEN -->
                <input type="hidden" id="csrfToken" name="csrfToken" value="<?php echo htmlspecialchars($_SESSION['csrfToken']); ?>">

                <div class="mb-3">
                    <label for="name" class="form-label required">Name</label>
                    <input type="text" class="form-control" id="name" name="name" >
                    <div class="invalid-feedback" id="nameFeedback">TEXT</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label required">Email</label>
                    <input type="email" class="form-control" id="email" name="email" >
                    <div class="invalid-feedback" id="emailFeedback">TEXT</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="startDate" class="form-label required">Start Date</label>
                        <input type="date" class="form-control" id="startDate" name="start_date" >
                        <div class="invalid-feedback" id="startDateFeedback">TEXT</div>
                    </div>
                    <div class="col-md-6">
                        <label for="startTime" class="form-label required">Start Time</label>
                        <input type="time" class="form-control" id="startTime" name="start_time" >
                        <div class="text-muted small">    
                            You can book appointments only between 
                            <?php echo date('g:i A', strtotime(OFFICE_OPEN_TIME)); ?> and 
                            <?php echo date('g:i A', strtotime(OFFICE_CLOSE_TIME)); ?>, 
                            except during lunch 
                            <?php echo date('g:i A', strtotime(LUNCH_START_TIME)); ?> - 
                            <?php echo date('g:i A', strtotime(LUNCH_END_TIME)); ?>.
                        </div>
                        <br>
                        <div class="invalid-feedback" id="startTimeFeedback">TEXT</div>
                    </div>
                    <div class="col-md-6">
                        <label for="endDate" class="form-label required">End Date</label>
                        <input type="date" class="form-control" id="endDate" name="end_date" >
                        <div class="invalid-feedback" id="endDateFeedback">TEXT</div>
                    </div>
                    <div class="col-md-6">
                        <label for="endTime" class="form-label required">End Time</label>
                        <input type="time" class="form-control" id="endTime" name="end_time" >
                        <br>
                        <div class="invalid-feedback" id="endTimeFeedback">TEXT</div>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label for="documents" class="form-label">Upload documents (if any)</label>
                    <input type="file" class="form-control" id="documents" name="documents[]" multiple accept=".pdf">
                    <small class="text-muted small">INSTRUCTIONS : Only PDF files are allowed, max 5 files, max file size 10 MB each.</small>
                    <br><div class="invalid-feedback" id="fileFeedback">TEXT</div>
                </div>
                <div id="selectedFilesList" class="mb-3 d-flex flex-wrap gap-2"></div>


                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>

    </div>
</div>
<script>
    var ALLOWED_FILE_TYPES = <?php echo ALLOWED_FILE_TYPES_APPOINTMENT; ?>;
    var MAX_FILE_SIZE = <?php echo MAX_FILE_SIZE_APPOINTMENT; ?> ; // 10 MB
    var MAX_FILE_UPLOAD_LIMIT = <?php echo MAX_FILE_UPLOAD_LIMIT; ?>;
    var OFFICE_OPEN_TIME = "<?php echo OFFICE_OPEN_TIME; ?>";
    var OFFICE_CLOSE_TIME = "<?php echo OFFICE_CLOSE_TIME; ?>";
    var LUNCH_START_TIME = "<?php echo LUNCH_START_TIME; ?>";
    var LUNCH_END_TIME = "<?php echo LUNCH_END_TIME; ?>";

    $(function () {

        // Set the minimum date for start and end date pickers to tomorrow
        let tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        let tomorrowString = tomorrow.toISOString().slice(0, 10);
        // Get the date one year from tomorrow
        let oneYearFromTomorrow = new Date();
        oneYearFromTomorrow.setDate(oneYearFromTomorrow.getDate() + 1); // Start from tomorrow
        oneYearFromTomorrow.setFullYear(oneYearFromTomorrow.getFullYear() + 1); // Add one year
        let oneYearFromTomorrowString = oneYearFromTomorrow.toISOString().slice(0, 10);

        $('#startDate').attr('min', tomorrowString);
        $('#endDate').attr('min', tomorrowString);
        // Set the maximum date for start and end date pickers to one year from tomorrow
        $('#startDate').attr('max', oneYearFromTomorrowString);
        $('#endDate').attr('max', oneYearFromTomorrowString);
        // Dynamically update endDate's minimum date when startDate changes
        $('#startDate').on('change', function () {
            let selectedStartDate = $(this).val();
            $('#endDate').attr('min', selectedStartDate);

            // Ensure endDate's max is not less than selectedStartDate (though less critical with overall max)
            // You might also want to re-evaluate end date/time if start date pushes it past max
            if (new Date(selectedStartDate) > oneYearFromTomorrow) {
                $('#endDate').attr('max', selectedStartDate); // Cap it at the start date if beyond overall max
            } else {
                $('#endDate').attr('max', oneYearFromTomorrowString); // Otherwise, use the overall max
            }
        });

        // File handling and display
        const fileInput = $('#documents');
        const selectedFilesList = $('#selectedFilesList');
        // Helper function to validate a single file
        function validateFile(file) {
            let isValid = true;
            if (!ALLOWED_FILE_TYPES.includes(file.type)) {
                isValid = false;
            } else if (file.size > MAX_FILE_SIZE) {
                isValid = false;
            }
            return isValid;
        }
        function updateFilesList(files) {
            selectedFilesList.empty(); // Clear existing list
            if (files.length > 0) {
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const fileName = file.name;
                    const isValid = validateFile(file);
                    // Add classes for styling based on validation status
                    const cardClass = isValid ? 'bg-light' : 'bg-danger text-white'; 
                    const cardStyle = isValid ? '' : 'background:rgba(255, 0, 0, 0.92) !important;'
                    const closeBtnClass = isValid ? '' : 'btn-close-white';
                    const fileItem = $(`
                        <div class="d-flex align-items-center border rounded p-2 ${cardClass} w-100" 
                            style="flex-grow: 1; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.2s ease-in-out; min-width: 0;${cardStyle}">
                            <span class="me-2 text-truncate ${isValid ? 'text-muted' : 'text-white'}" style="flex-shrink: 1;">${fileName}</span>
                            <button type="button" class="btn-close ms-auto ${closeBtnClass}" style="height:8px !important;width:8px !important;" aria-label="Remove" data-filename="${fileName}"></button>
                        </div>
                    `);
                    selectedFilesList.append(fileItem);
                }
            }
        }  
        fileInput.on('change', function () {
            const files = this.files;
            updateFilesList(files);
        });
        // Handle file removal
        selectedFilesList.on('click', '.btn-close', function () {
            const filenameToRemove = $(this).data('filename');
            const files = fileInput[0].files;
            const dataTransfer = new DataTransfer();
            for (let i = 0; i < files.length; i++) {
                if (files[i].name !== filenameToRemove) {
                    dataTransfer.items.add(files[i]);
                }
            }
            fileInput[0].files = dataTransfer.files;
            updateFilesList(fileInput[0].files);
        });



        // AJAX CALL FOR FORM SUBMIT 
        $('#appointmentForm').submit(function (e) {
            e.preventDefault();

            function sanitizeInput(input) {
                return input.replace(/[<>\/\\'"`;%(){}[\]=+&^$|]/g, "").trim();
            }

            let name = sanitizeInput($('#name').val());
            let email = sanitizeInput($('#email').val());
            let startDate = $('#startDate').val();
            let startTime = $('#startTime').val();
            let endDate = $('#endDate').val();
            let endTime = $('#endTime').val();
            let files = $('#documents')[0].files;

            let valid = true;

            // Reset all feedback messages
            $('#nameFeedback').removeClass("display");
            $('#emailFeedback').removeClass("display");
            $('#startDateFeedback').removeClass("display");
            $('#startTimeFeedback').removeClass("display");
            $('#endDateFeedback').removeClass("display");
            $('#endTimeFeedback').removeClass("display");
            $('#fileFeedback').removeClass("display");

            //  Name Validation
            if (!name) {
                $('#nameFeedback').text("Name is required !").addClass("display");
                valid = false;
            } else if(name.length > 100 ) {
                $('#nameFeedback').text("Name must be less than 100 characters !").addClass("display");
                valid = false;
            }

            // Email Validation
            if (!email) {
                $('#emailFeedback').text("Email is required !").addClass("display");
                valid = false;
            } else if(email.length > 100) {
                $('#emailFeedback').text("Email must be less than 100 characters !").addClass("display");
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $('#emailFeedback').text("Please enter a valid email address !").addClass("display");
                valid = false;
            }

            // Date & Time Validation
            if (validateDateTime(startDate, startTime, endDate, endTime) === false) {
                valid = false;
            }

            // File Validations
            if (files.length > 0) {
                if (files.length > MAX_FILE_UPLOAD_LIMIT) {
                    $('#fileFeedback').text(`You can upload a maximum of ${MAX_FILE_UPLOAD_LIMIT} files.`).addClass("display");
                    valid = false;
                } else {
                    for (let i = 0; i < files.length; i++) {
                        if (!ALLOWED_FILE_TYPES.includes(files[i].type)) {
                            $('#fileFeedback').text("Only PDF files are allowed!").addClass("display");
                            valid = false;
                            break;
                        }
                        if (files[i].size > MAX_FILE_SIZE) {
                            $('#fileFeedback').text(`Each file must be less than ${MAX_FILE_SIZE / (1024 * 1024)} MB.`).addClass("display");
                            valid = false;
                            break;
                        }
                    }
                }
            }
            // Stop if invalid
            if (!valid) {
                return;
            }

            // Send data
            let formData = new FormData(this);
            formData.append("csrfToken", "<?php echo $_SESSION['csrfToken']; ?>");

            $.ajax({
                url: "<?php echo CONTROLLERS_URL; ?>10_BookAppointment.php",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    console.log(response) ; 
                    alert(response.message || "Appointment booked successfully!");
                    $('#appointmentForm')[0].reset();
                    window.location.reload() ; 
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
                    } else if(xhr.status == 400 ) {
                        if(errorResponse.message.includes("fields required")) {
                            alert(errorResponse.message) ; 
                            $('#nameFeedback , #emailFeedback , #startDateFeedback , #startTimeFeedback , #endDateFeedback , #endTimeFeedback').addClass("display").text("Marked fields required.");
                            return ; 
                        } else if (errorResponse.message.includes("Name must be less than 100 characters.")) {
                            alert(errorResponse.message) ; 
                            $('#nameFeedback').addClass("display").text(errorResponse.message);
                            return ; 
                        } else if(errorResponse.message.includes("Email") || errorResponse.message.includes("email")) {
                            alert(errorResponse.message) ; 
                            $('#emailFeedback').addClass("display").text(errorResponse.message);
                            return ; 
                        } else if (errorResponse.message.includes("Invalid date or time.")) {
                            alert(errorResponse.message) ; 
                            $('#startDateFeedback , #startTimeFeedback').addClass("display").text(errorResponse.message) ; 
                            return ;
                        } else if (errorResponse.message.includes("You can only select dates from tomorrow onwards.") || errorResponse.message.includes("Start date cannot be more than 1 year from today.") ) {
                            alert(errorResponse.message) ; 
                            $('#startDateFeedback').addClass("display").text(errorResponse.message) ; 
                            return ;
                        } else if (errorResponse.message.includes("Start time must be within office hours (excluding lunch break).") || errorResponse.message.includes("This time slot is already booked")) {
                            alert(errorResponse.message) ; 
                            $('#startTimeFeedback').addClass("display").text(errorResponse.message) ; 
                            return ;
                        } else if (errorResponse.message.includes("End time must be within office hours (excluding lunch break).")) {
                            alert(errorResponse.message) ; 
                            $('#endTimeFeedback').addClass("display").text(errorResponse.message) ; 
                            return ;
                        } else if (errorResponse.message.includes("Booking")) {
                            alert(errorResponse.message) ; 
                            $('#endTimeFeedback').addClass("display").text(errorResponse.message) ; 
                            return ;
                        } else if (errorResponse.message.includes("End date")) {
                            alert(errorResponse.message) ; 
                            $('#endDateFeedback').addClass("display").text(errorResponse.message) ; 
                            return ;
                        } else if (errorResponse.message.includes("Cannot book on holidays.")) {
                            alert(errorResponse.message) ; 
                            $('#startDateFeedback').addClass("display").text(errorResponse.message) ; 
                            return ;
                        } else {
                            alert("Internal Server Error") ; 
                            window.location.reload() ; 
                            return ; 
                        }
                    } else {
                        alert(errorResponse.message || 'Internal Server Error !');
                        window.location.reload() ; 
                        return ; 
                    }
                }
            });
        });
        


        // Function to handle all date/time validations
        function validateDateTime(startDate, startTime, endDate, endTime) {
            let now = new Date();
            let startDateObj = new Date(startDate + "T" + startTime);
            let endDateObj = new Date(endDate + "T" + endTime);
            let oneYearFromNow = new Date();
            oneYearFromNow.setFullYear(now.getFullYear() + 1);

            // Normalize 'now' to just the date, ignoring the time
            let today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            let startDay = new Date(startDateObj.getFullYear(), startDateObj.getMonth(), startDateObj.getDate());
            
            // Reset date/time feedback
            $('#startDateFeedback').removeClass("display");
            $('#startTimeFeedback').removeClass("display");
            $('#endDateFeedback').removeClass("display");
            $('#endTimeFeedback').removeClass("display");
            
            let validDateTime = true;

            // Start Date & Time Validation
            if (!startDate || !startTime) {
                if (!startDate) $('#startDateFeedback').text("Start date is required!").addClass("display");
                if (!startTime) $('#startTimeFeedback').text("Start time is required!").addClass("display");
                if (!endDate) $('#endDateFeedback').text("End date is required!").addClass("display");
                if (!endTime) $('#endTimeFeedback').text("End time is required!").addClass("display");
                validDateTime = false;
            } else if (startDay <= today) {
                $('#startDateFeedback').text("You can only select dates from tomorrow onwards.").addClass("display");
                validDateTime = false;
            } else if (startDateObj > oneYearFromNow) {
                $('#startDateFeedback').text("Start date cannot be more than 1 year from today.").addClass("display");
                validDateTime = false;
            }
            if(!endDate || !endTime) {
                if (!endDate) $('#endDateFeedback').text("End date is required!").addClass("display");
                if (!endTime) $('#endTimeFeedback').text("End time is required!").addClass("display");
                validDateTime = false;
            }
            // End Date & Time Validation - ONLY proceed if start date/time is valid
            if (validDateTime) {
                if (!endDate || !endTime) {
                    if (!endDate) $('#endDateFeedback').text("End date is required!").addClass("display");
                    if (!endTime) $('#endTimeFeedback').text("End time is required!").addClass("display");
                    validDateTime = false;
                }
            }

            // New logic: Validate time slots against office hours and lunch break first.
            if (validDateTime) {
                // Office hour and lunch break validation
                let isStartTimeValid = (startTime >= OFFICE_OPEN_TIME && startTime <= LUNCH_START_TIME) ||
                                    (startTime >= LUNCH_END_TIME && startTime <= OFFICE_CLOSE_TIME);
                let isEndTimeValid = (endTime >= OFFICE_OPEN_TIME && endTime <= LUNCH_START_TIME) ||
                                    (endTime >= LUNCH_END_TIME && endTime <= OFFICE_CLOSE_TIME);

                if (!isStartTimeValid) {
                    $('#startTimeFeedback').text("Start time must be within office hours (excluding lunch break).").addClass("display");
                    validDateTime = false;
                }

                if (!isEndTimeValid) {
                    $('#endTimeFeedback').text("End time must be within office hours (excluding lunch break).").addClass("display");
                    validDateTime = false;
                }
            }

            // Now, with valid office hours, validate duration and order
            if (validDateTime) {
                if (endDateObj < startDateObj) {
                    $('#endDateFeedback').text("End date and time must be after start date and time.").addClass("display");
                    validDateTime = false;
                } else if (endDateObj.getTime() === startDateObj.getTime()) {
                    $('#endTimeFeedback').text("End time must be after start time.").addClass("display");
                    validDateTime = false;
                } else {
                    let diffMinutes = (endDateObj - startDateObj) / (1000 * 60);
                    if (diffMinutes < 30) {
                        $('#endTimeFeedback').text("Booking must be at least 30 minutes long.").addClass("display");
                        validDateTime = false;
                    } else if (diffMinutes > 60) {
                        $('#endTimeFeedback').text("Booking cannot be more than 1 hour long.").addClass("display");
                        validDateTime = false;
                    }
                }
            }
            
            return validDateTime;
        }

    });
</script>
<?php require_once __DIR__ . '/footer.php'; ?>