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








// OTP AND ITS EXPIRY
if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_expiry'])) {
    http_response_code(400) ; 
    echo json_encode(['status' => 'error', 'message' => 'OTP or expiry not set']);
    exit;
}

// VALIDATIONS
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}


$id = isset($_SESSION["id"]) ? $_SESSION["id"] : "";
$otp = isset($_POST["otp"]) ? sanitize($_POST["otp"]) : "";

if (empty($id) || empty($otp)) {
    http_response_code(400); // Bad Request — client sent invalid data
    echo json_encode(["status" => "error", "message" => "Please Enter Valid Fields"]);
    exit;
}

// DB LOGIC 
$currentTime = new DateTime();
$expiryTime = new DateTime($_SESSION['otp_expiry']);
if ($currentTime > $expiryTime) { // NOT VALID OTP 
    http_response_code(400) ; 
    echo json_encode(['status' => 'error', 'message' => 'OTP expired']);
    exit;
}
if ($_POST['otp'] != $_SESSION['otp']) { // NOT VALID OTP
    http_response_code(400) ; 
    echo json_encode(['status' => 'error', 'message' => 'Invalid OTP']);
    exit;
}

// VALID OTP 
$stmt = $connection->prepare("UPDATE users SET is_email_verified = '1' WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => "Internal Server Error"]);
    exit;
}
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    $MailContent = "✅ Account Created Successfully !";
    $name = " "; // no need for sending name in mail! 
    // Fetch email for this id
    $emailStmt = $connection->prepare("SELECT email FROM users WHERE id = ?");
    if ($emailStmt) {
        $emailStmt->bind_param("i", $id);
        $emailStmt->execute();
        $result = $emailStmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $pageName = isset($_SESSION["page"]) ? $_SESSION["page"] : "";                                                          // PAGE NAME : bascially this variable is being used that for which page the otp request is coming from !
            if (!empty($pageName) && $pageName == "forgotPassword") {
                // redirect to reset credentials page 
                $_SESSION["change_credentials"] = true;
                unset($_SESSION['showOtpPage']);
                unset($_SESSION['page']);

                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Now you can change the Password !', 'page' => $pageName]);
            } else {
                $email = $row['email'];
                sendEmail($name, $email, $MailContent);
                unset($_SESSION['showOtpPage']);
                unset($_SESSION['page']);

                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Account Created Successfully !', 'page' => $pageName]);
            }
        } else {
            $email = null;
            // http_response_code(500);
            unset($_SESSION['showOtpPage']);
            unset($_SESSION['page']);
            http_response_code(400) ; 
            echo json_encode(['status' => 'error', 'message' => 'User Not Found Please Signup !']);
            exit;
        }
        $emailStmt->close();
    } else {
        $email = null;
        unset($_SESSION['showOtpPage']);
        unset($_SESSION['page']);

        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Internal Server Error"]);
        exit;
    }
}

$stmt->close();

?>