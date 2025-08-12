<?php
// SESSION START
// DB CONNECTION 
require_once dirname(__DIR__) . '/config.php';
header('Content-Type: application/json'); // Makes sure client's browser will receive response in json format
require_once __DIR__ . '/3_sendMail.php';
$id = isset($_SESSION['id']) ? $_SESSION['id'] : "";
// REQUEST METHOD 
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id == "") {
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


$raw_password = isset($_POST['password']) ? sanitize($_POST["password"]) : "";
$confirm_password = isset($_POST["confirmPassword"]) ? sanitize($_POST["confirmPassword"]) : "";
$password = password_hash($raw_password, PASSWORD_DEFAULT);

if (empty($password) || empty($confirm_password)) {
    http_response_code(400); // Bad Request — client sent invalid data
    echo json_encode(["status" => "error", "message" => "All fields required !"]);
    exit;
}
// Password validations
if (strlen($raw_password) < 6 || strlen($raw_password) > 15 || strlen($confirm_password) < 6 || strlen($confirm_password) > 15) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Password must be 6-15 characters long."]);
    exit;
}
// Check if password includes a letter, number, and special character
$hasLetter = preg_match('/[a-zA-Z]/', $raw_password);
$hasNumber = preg_match('/\d/', $raw_password);
$hasSpecial = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $raw_password);
if (!$hasLetter || !$hasNumber || !$hasSpecial) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Password must include a letter, number & symbol."]);
    exit;
}
// Check if password and confirm password match
if ($raw_password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Passwords do not match."]);
    exit;
}


// CHECK : If entry exists or not
$stmt = $connection->prepare("SELECT * FROM users where id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => "Internal Server Error"]);
    exit;
}
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows === 1) { // EXISTS
        $row = $result->fetch_assoc();
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $name = $first_name . " " . $last_name;
        $email = $row['email'];

        if ($row['is_email_verified'] === '1') { // Email is verified : Allowed to change password
            $stmt->close();
            $update = $connection->prepare("UPDATE users SET password = ? WHERE id = ?");
            if (!$update) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => "Internal Server Error"]);
                exit;
            }
            $update->bind_param("si", $password, $id);
            if ($update->execute()) {
                $MailContent = "✅ Password Changed Successfully !";
                sendEmail($name, $email, $MailContent);
            }
            $update->close();

            http_response_code(200) ; 
            echo json_encode(["status" => "success", "message" => "Password Changed Successfully ! Please Login !"]);
            exit;
        } else { // Email is not verified 
            $stmt->close();
            http_response_code(500) ; 
            echo json_encode(["status" => "error", "message" => "Internal Server Error ! Please Register again !"]); // Extra security measure 
            exit;
        }
    } else { // DOES NOT EXISTS : Please signup
        $stmt->close();
        http_response_code(404) ; 
        echo json_encode(["status" => "error", "message" => "Account Does not exists Please Register !"]);
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode(['status' => "error", 'message' => "Internal Server Error"]);
    exit;
}
?>