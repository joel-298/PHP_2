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






// Required fields
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required']);
    exit;
}

$id = isset($_SESSION["id"]) ? $_SESSION['id'] : "";


// Check if user exists
$stmt = $connection->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Generate new OTP
        $otp = random_int(100000, 999999);
        $_SESSION['otp'] = $otp;

        $expiry = new DateTime();
        $expiry->modify('+5 minutes');
        $_SESSION['otp_expiry'] = $expiry->format('Y-m-d H:i:s');

        // Send email (requires valid $MailContent, $name and $email)
        $email = $row['email'];
        $name = $row['first_name'] . ' ' . $row['last_name'];
        $MailContent = "✅ Your regenerated OTP is: $otp";

        sendEmail($name, $email, $MailContent);

        echo json_encode(['status' => 'success', 'message' => 'OTP regenerated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User Not Found Please Signup !']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>