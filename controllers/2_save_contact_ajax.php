<?php
// SESSION START
// DB CONNECTION 
require_once dirname(__DIR__) . '/config.php';
header('Content-Type: application/json'); // Makes sure client's browser will receive response in json format
require_once __DIR__ . '/3_sendMail.php';
// REQUEST METHOD 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403); // Forbidden
    echo json_encode(["status" => "error", "message" => "Access Denied"]);
    exit;
}
// CSRF TOKEN VALIDATION
if (!isset($_POST['csrfToken']) || $_POST['csrfToken'] !== $_SESSION['csrfToken']) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => "error", "message" => "Your session has expired. Please refresh the page and try again"]);
    exit;
}










// VALIDATIONS
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}
$name = isset($_POST['name']) ? sanitize($_POST["name"]) : '';
$email = isset($_POST['email']) ? sanitize($_POST["email"]) : '';
$phone = isset($_POST['phone']) ? sanitize($_POST["phone"]) : '';
$message = isset($_POST['message']) ? sanitize($_POST["message"]) : '';
$filePath = null;

if (empty($name) || empty($email) || empty($phone) || empty($message)) {
    http_response_code(400); // Bad Request
    echo json_encode(["status" => "error", "message" => "All Fields Required !"]);
    exit;
}
if (strlen($name) > 100) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Name must be under 100 characters."]);
    exit;
}
if (strlen($email) > 100) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Email must be under 100 characters."]);
    exit;
}
if (strlen($message) > 500) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Message must be under 500 characters."]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Bad Request
    echo json_encode(["status" => "error", "message" => "Invalid Email Format !"]);
    exit;
}
if (strlen($phone) < 10 || strlen($phone) > 15 || preg_match('/[a-zA-Z]/', $phone) || preg_match('/[^0-9+]/', $phone)) {
    http_response_code(400); // Bad Request
    echo json_encode(["status" => "error", "message" => "Invalid Phone Number !"]);
    exit;
}
// FILE HANDLING !
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {                                     // RECIVING FILE
    $allowedExt  = json_decode(ALLOWED_FILE_EXT, true);                         // EXTENSIONS ALLOWED 
    $allowedMime = json_decode(ALLOWED_FILE_TYPES, true);                       // MIME PROTECTION
    $maxFileSize = MAX_FILE_SIZE ; 
    $fileTmpPath = $_FILES['file']['tmp_name'];                                                  // FILE TEMPORARY PATH    
    $fileName = basename($_FILES['file']['name']);                                               // FILE NAME
    $fileSize = $_FILES['file']['size'];
    $fileType = mime_content_type($fileTmpPath);                                                 // FILE TYPE
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));                                  // FILE EXT.

    if (in_array($ext, $allowedExt) && in_array($fileType, $allowedMime) && $fileSize <= $maxFileSize) {       // FILE VALIDATION BASED ON ENTENSION, MIME, AND SIZE
        $uploadPath = "../uploads/" . time() . "$fileName";                                        // SAVING IT IN UPLOADS FOLDER OF SERVER
        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
            $filePath = $uploadPath;
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to upload File !"]);
            exit;
        }
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(["status" => "error", "message" => "Invalid File Upload !"]);
        exit;
    }
}

// DB LOGIC WITH PREPARED STATEMENTS
$stmt = $connection->prepare("INSERT INTO contact_us (name,email,phone,message,file_path) VALUES(?,?,?,?,?)");
if (!$stmt) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["status" => "error", "message" => "Internal Server Error"]);
    exit;
}



$stmt->bind_param("sssss", $name, $email, $phone, $message, $filePath);

if ($stmt->execute()) {
    $MailContent = "✅ Thanks for reaching out! We've received your message and will be in touch soon.";
    sendEmail($name, $email, $MailContent, $filePath);
    http_response_code(200); // Internal Server Error
    echo json_encode(["status" => "success", "message" => "Your Message is saved Successfully !"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Internal Server Error"]);
}

// HELPS IN FREEING UP MEMORY but not necessay : ALSO when script it completed php automatically closes these
$stmt->close();

?>