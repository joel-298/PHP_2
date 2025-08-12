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



// FUNCTION TO SANITIZE INPUT
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// FETCH AND SANITIZE INPUTS
$current_password = isset($_POST["current_password"]) ? sanitize($_POST["current_password"]) : "";
$new_password = isset($_POST["new_password"]) ? sanitize($_POST["new_password"]) : "";
$confirm_password = isset($_POST["confirm_password"]) ? sanitize($_POST["confirm_password"]) : "";

// CHECK EMPTY FIELDS
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit;
}


// GET USER ID FROM SESSION
$userId = $_SESSION['id'] ?? null;
if (!$userId) {
    session_unset();
    session_destroy(); 
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// FETCH CURRENT PASSWORD HASH FROM DB
$stmt = $connection->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($stored_hashed_password);
$stmt->fetch();
$stmt->close();

// VERIFY CURRENT PASSWORD
if (!password_verify($current_password, $stored_hashed_password)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Current password is incorrect."]);
    exit;
}
// VALIDATE NEW PASSWORD FORMAT
if (strlen($new_password) < 6 || strlen($new_password) > 15 || strlen($confirm_password) < 6 || strlen($confirm_password) > 15) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Password must be 6-15 characters long."]);
    exit;
}

$hasLetter = preg_match('/[a-zA-Z]/', $new_password);
$hasNumber = preg_match('/\d/', $new_password);
$hasSpecial = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password);
if (!$hasLetter || !$hasNumber || !$hasSpecial) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Password must include a letter, number & symbol."]);
    exit;
}

// CHECK MATCH BETWEEN NEW AND CONFIRM PASSWORD
if ($new_password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "New password and confirm password does not match."]);
    exit;
}

// HASH AND UPDATE NEW PASSWORD
$new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$updateStmt = $connection->prepare("UPDATE users SET password = ? WHERE id = ?");
$updateStmt->bind_param("si", $new_hashed_password, $userId);
if ($updateStmt->execute()) {
    $updateStmt->close();
    http_response_code(200) ; 
    echo json_encode(["status" => "success", "message" => "Password updated successfully."]);
    exit;
} else {
    $updateStmt->close();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Internal Server Error."]);
    exit;
}


?>