<?php
// SESSION START
require_once dirname(__DIR__) . '/config.php';
header('Content-Type: application/json');
require_once __DIR__ . '/3_sendMail.php';


// Request method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Access Denied"]);
    exit;
}

// CSRF token validation
if (!isset($_POST['csrfToken']) || $_POST['csrfToken'] !== $_SESSION['csrfToken']) {
    http_response_code(403);
    echo json_encode(['status' => "error", "message" => "Your session has expired. Please refresh the page and try again"]);
    exit;
}

// Function to sanitize input
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Constants from config
$ALLOWED_FILE_TYPES = json_decode(ALLOWED_FILE_TYPES_APPOINTMENT, true);
$MAX_FILE_SIZE = MAX_FILE_SIZE_APPOINTMENT;
$MAX_FILE_UPLOAD_LIMIT = MAX_FILE_UPLOAD_LIMIT;

// Office timings from config
$OFFICE_OPEN_TIME = OFFICE_OPEN_TIME; // "09:00"
$OFFICE_CLOSE_TIME = OFFICE_CLOSE_TIME; // "18:30"
$LUNCH_START_TIME = LUNCH_START_TIME; // "14:00"
$LUNCH_END_TIME = LUNCH_END_TIME; // "14:30"

// Receive & sanitize data
$name = sanitize(isset($_POST['name']) ? $_POST['name'] : '');
$email = sanitize(isset($_POST['email']) ? $_POST['email'] : '');
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$startTime = isset($_POST['start_time']) ? $_POST['start_time'] : '';
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : '';
$endTime = isset($_POST['end_time']) ? $_POST['end_time'] : '';



// Name validation
if (!$name || !$email || !$startDate || !$endDate || !$startTime || !$endTime) {
    http_response_code(400) ; 
    echo json_encode(['status'=>'error','message'=>"Marked fields required"]) ; 
    exit ; 
} 
if (strlen($name) > 100) {
    http_response_code(400) ; 
    echo json_encode(['status'=>'error','message'=>"Name must be less than 100 characters."]) ; 
    exit ; 
}
// Email validation
if (strlen($email) > 100) {
    http_response_code(400) ; 
    echo json_encode(['status'=>'error','message'=>"Email must be less than 100 characters."]) ; 
    exit ; 
} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400) ; 
    echo json_encode(['status'=>'error','message'=>"Invalid email format."]) ; 
    exit ; 
}
// Date/Time conversion
try {
    $startDateTime = new DateTime("$startDate $startTime");
    $endDateTime = new DateTime("$endDate $endTime");
    $now = new DateTime();
    $today = new DateTime(date('Y-m-d'));
    $oneYearFromNow = (clone $now)->modify('+1 year');
} catch (Exception $e) {
    http_response_code(400) ; 
    echo json_encode(['status'=>'error','message'=>"Invalid date or time."]) ; 
    exit ; 
}

//------------------------------------------------------------- Date & time validations -----------------------------------------------------------------------------------------

// Cannot book for today or past
if ($startDateTime <= $today) {
    http_response_code(400) ; 
    echo json_encode(['status'=>'error','message'=>"You can only select dates from tomorrow onwards."]) ; 
    exit ; 
}
// Cannot book more than 1 year ahead
if ($startDateTime > $oneYearFromNow) {
    http_response_code(400) ; 
    echo json_encode(['status'=>'error','message'=>"Start date cannot be more than 1 year from today."]) ; 
    exit ; 
}

// ---------------------------------------------------------- End must be after start ---------------------------------------------------
if ($endDateTime <= $startDateTime) {
    http_response_code(400) ; 
    echo json_encode(['status'=>'error','message'=> "End date and time must be after start date and time."]) ; 
    exit ; 
}


// ------------------------------------------------------------------- Office hours check -------------------------------------------------------------------
$startTimeStr = $startDateTime->format('H:i');
$endTimeStr = $endDateTime->format('H:i');

$isStartTimeValid = ($startTimeStr >= $OFFICE_OPEN_TIME && $startTimeStr < $LUNCH_START_TIME) ||
                    ($startTimeStr >= $LUNCH_END_TIME && $startTimeStr <= $OFFICE_CLOSE_TIME);

$isEndTimeValid = ($endTimeStr > $OFFICE_OPEN_TIME && $endTimeStr <= $LUNCH_START_TIME) ||
                    ($endTimeStr >= $LUNCH_END_TIME && $endTimeStr <= $OFFICE_CLOSE_TIME);

if (!$isStartTimeValid) {
    http_response_code(400) ; 
    echo json_encode(['status'=>'error','message'=> "Start time must be within office hours (excluding lunch break)."]) ; 
    exit ; 
}
if (!$isEndTimeValid) {
    http_response_code(400) ; 
    echo json_encode(['status'=>'error','message'=> "End time must be within office hours (excluding lunch break)."]) ; 
    exit ; 
}

// ------------------------------------------------------------------- Duration check -------------------------------------------------------------------
$diffMinutes = ($endDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 60;
if ($diffMinutes < 30) {
    http_response_code(400) ; 
    echo json_encode(['status'=>'error','message'=> "Booking must be at least 30 minutes long."]) ; 
    exit ; 
} elseif ($diffMinutes > 60) {
    http_response_code(400) ; 
    echo json_encode(['status'=>'error','message'=> "Booking cannot be more than 1 hour long."]) ; 
    exit ; 
}







//------------------------------------------------------------- Holiday check --------------------------------------------------------------
if (empty($errors)) {
    $holidayCheckSQL = "SELECT COUNT(*) FROM holiday_info WHERE holiday_date = ?";
    $stmt = $connection->prepare($holidayCheckSQL);
    $stmt->bind_param("s", $startDate);
    $stmt->execute();
    $stmt->bind_result($holidayCount);
    $stmt->fetch();
    $stmt->close();

    if ($holidayCount > 0) {
        http_response_code(400) ; 
        echo json_encode(['status'=>'error','message'=> "Cannot book on holidays."]) ; 
        exit ; 
    }
}



// ---------------------------------------------------------- Time slot clash check -----------------------------------------------------
if (empty($errors)) {
    $clashSQL = "SELECT COUNT(*) FROM booking_info 
                 WHERE NOT (booking_end_datetime <= ? OR booking_start_datetime >= ?)";
    $stmt = $connection->prepare($clashSQL);
    $startStr = $startDateTime->format('Y-m-d H:i:s');
    $endStr = $endDateTime->format('Y-m-d H:i:s');
    $stmt->bind_param("ss", $startStr, $endStr);
    $stmt->execute();
    $stmt->bind_result($clashCount);
    $stmt->fetch();
    $stmt->close();

    if ($clashCount > 0) {
        http_response_code(400) ; 
        echo json_encode(['status'=>'error','message'=> "This time slot is already booked. Please choose a different one."]) ; 
        exit ; 
    }
}


// ------------------------------------------------------------------- File validations ----------------------------------------------------------------------
$uploadedFiles = [];
if (!empty($_FILES['documents']['name'][0])) {
    if (count($_FILES['documents']['name']) > $MAX_FILE_UPLOAD_LIMIT) {
        $errors[] = "You can upload a maximum of {$MAX_FILE_UPLOAD_LIMIT} files.";
    } else {
        foreach ($_FILES['documents']['name'] as $key => $fileName) {
            $fileType = $_FILES['documents']['type'][$key];
            $fileSize = $_FILES['documents']['size'][$key];
            $tmpName  = $_FILES['documents']['tmp_name'][$key];

            if (!in_array($fileType, $ALLOWED_FILE_TYPES)) {
                $errors[] = "Only PDF files are allowed.";
                break;
            }
            if ($fileSize > $MAX_FILE_SIZE) {
                $errors[] = "Each file must be less than " . ($MAX_FILE_SIZE / (1024 * 1024)) . " MB.";
                break;
            }

            // Save file
            // Remove spaces from file name
            $fileNameNoSpaces = str_replace(' ', '_', $fileName);
            // Generate safe unique file name
            $safeName = uniqid() . "_" . basename($fileNameNoSpaces);
            $uploadPath = '../uploads/' . $safeName;
            if (move_uploaded_file($tmpName, $uploadPath)) {
                $uploadedFiles[] = $uploadPath;
            } else {
                $errors[] = "Failed to upload file: {$fileName}";
                break;
            }
        }
    }
}



// ------------------------------------------------------------------- SAVE TO DB WITH TRANSACTION -------------------------------------------------------------------
$connection->begin_transaction();
try {
    // booking_info
    $insertBooking = "INSERT INTO booking_info 
        (person_name, email_address, booking_start_datetime, booking_end_datetime, created_on)
        VALUES (?, ?, ?, ?, NOW())";
    $stmt = $connection->prepare($insertBooking);
    $stmt->bind_param("ssss", $name, $email, $startStr, $endStr);
    $stmt->execute();
    $bookingId = $stmt->insert_id;
    $stmt->close();

    // booking_documents_info
    if (!empty($uploadedFiles)) {
        $insertDoc = "INSERT INTO booking_documents_info (booking_id, document_path) VALUES (?, ?)";
        $stmt = $connection->prepare($insertDoc);
        foreach ($uploadedFiles as $path) {
            $stmt->bind_param("is", $bookingId, $path);
            $stmt->execute();
        }
        $stmt->close();
    }

    $connection->commit();
    // --- SEND EMAIL ---
    sendEmail('ADMIN',ADMIN_GMAIL ,"New Appointment Booking
            <h3>New Appointment Booking Details :</h3>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Start:</strong> {$startStr}</p>
            <p><strong>End:</strong> {$endStr}</p>
        ",
        $uploadedFiles // attachments
    );

    // $mail = new PHPMailer(true);
    // try {
    //     $mail->isSMTP();
    //     $mail->Host = SMTP_HOST ;
    //     $mail->SMTPAuth = true;
    //     $mail->Username = SMTP_USERNAME ;
    //     $mail->Password = SMTP_PASSWORD ;
    //     $mail->SMTPSecure = 'SSL';
    //     $mail->Port = SMTP_PORT;

    //     $mail->setFrom('joel@orientaloutsourcing.com', 'JOEL MATTHEW');
    //     $mail->addAddress(ADMIN_GMAIL , 'Admin');

    //     $mail->isHTML(true);
    //     $mail->Subject = "New Appointment Booking";
    //     $mail->Body = "
    //         <h3>New Appointment Booking Details :</h3>
    //         <p><strong>Name:</strong> {$name}</p>
    //         <p><strong>Email:</strong> {$email}</p>
    //         <p><strong>Start:</strong> {$startStr}</p>
    //         <p><strong>End:</strong> {$endStr}</p>
    //     ";

    //     foreach ($uploadedFiles as $path) {
    //         $mail->addAttachment($path);
    //     }
    //     $mail->send();
        
    // } catch (Exception $e) {
    //     // Log email error, don't fail booking
    //     error_log("Mailer Error: {$mail->ErrorInfo}");
    // }

    http_response_code(200) ; 
    echo json_encode(["status" => "success", "message" => "Appointment booked successfully!"]);
    exit; 

} catch (Exception $e) {
    $connection->rollback();
    http_response_code(500) ; 
    echo json_encode(["status" => "error", "message" => "Internal Server Error"]); 
    exit ; 
}


?>