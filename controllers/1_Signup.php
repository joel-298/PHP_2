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
$first_name = isset($_POST["first_name"]) ? sanitize($_POST["first_name"]) : "";
$last_name = isset($_POST["last_name"]) ? sanitize($_POST["last_name"]) : "";
$email = isset($_POST["email"]) ? sanitize($_POST["email"]) : "";
$raw_password = isset($_POST["password"]) ? sanitize($_POST["password"]) : "";
$confirm_password = isset($_POST["confirmPassword"]) ? sanitize($_POST["confirmPassword"]) : "";
$password = password_hash($raw_password, PASSWORD_DEFAULT);


if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
    http_response_code(400); // Bad Request — client sent invalid data
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit;
}
if (strlen($first_name) > 100 || strlen($last_name) > 100) {
    http_response_code(400) ; 
    echo json_encode(["status"=>"error","message"=>"First and Last name must be less than 100 characters."]);
    exit ;
}
if (strlen($email) > 100) {
    http_response_code(400) ; 
    echo json_encode(["status"=>"error","message"=>"email must be less than 100 characters."]);
    exit ; 
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please enter a valid email address."]);
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
    echo json_encode(["status" => "error", "message" => "Password do not match."]);
    exit;
}


// DB LOGIC 
// CHECK : If email exists or not
$stmt = $connection->prepare("SELECT * FROM users where email = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => "Internal Server Error"]);
    exit;
}

$stmt->bind_param("s", $email);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows === 1) { // EXISTS
        $row = $result->fetch_assoc();

        if ($row['is_email_verified'] === '1') { // Email is verified : Please Login
            $stmt->close();
            http_response_code(200);
            echo json_encode(["status" => "error", "message" => "This email is already registered. Please log in."]);
            exit;
        } else { // Email is not verified 
            $stmt->close();
            $update = $connection->prepare("UPDATE users SET first_name = ?, last_name = ?, password = ? WHERE email = ?");
            if (!$update) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => "Internal Server Error"]);
                exit;
            }
            $update->bind_param("ssss", $first_name, $last_name, $password, $email);
            if ($update->execute()) {
                // OTP LOGIC : 
                $otp = random_int(100000, 999999);             // Generate random 6-digit OTP
                $_SESSION['otp'] = $otp;                       // Store OTP in session
                $_SESSION['showOtpPage'] = true;
                $_SESSION['page'] = 'signup';

                // Set OTP expiry to current time + 1 minute (60 seconds)
                $expiry = new DateTime(); // current time
                $expiry->modify('+5 minute');
                $_SESSION['otp_expiry'] = $expiry->format('Y-m-d H:i:s');

                $MailContent = "✅ Please verify your account : " . $otp;
                $name = $first_name . " " . $last_name;
                sendEmail($name, $email, $MailContent);


                // Fetch the id now
                $getIdStmt = $connection->prepare("SELECT id FROM users WHERE email = ?");
                if ($getIdStmt) {
                    $getIdStmt->bind_param("s", $email);
                    $getIdStmt->execute();
                    $idResult = $getIdStmt->get_result();
                    if ($idRow = $idResult->fetch_assoc()) {
                        $userId = $idRow['id'];
                    } else {
                        $userId = null;
                    }
                    $getIdStmt->close();
                } else {
                    $userId = null;
                }

                $_SESSION['id'] = $userId;
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Account already present please verifiy your email !']);
            }

            $update->close();
            exit;
        }
    } else { // Insert in db 
        $stmt->close();
        $insert = $connection->prepare("INSERT INTO users (first_name, last_name, email, password, is_email_verified) VALUES (?, ?, ?, ?, '0')");
        if (!$insert) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => "Internal Server Error"]);
            exit;
        }
        $insert->bind_param("ssss", $first_name, $last_name, $email, $password);

        if ($insert->execute()) {
            // OTP LOGIC : 
            $otp = random_int(100000, 999999);             // Generate random 6-digit OTP
            $_SESSION['otp'] = $otp;                       // Store OTP in session
            $_SESSION['showOtpPage'] = true;
            $_SESSION['page'] = 'signup';

            // Set OTP expiry to current time + 1 minute (60 seconds)
            $expiry = new DateTime(); // current time
            $expiry->modify('+5 minute');
            $_SESSION['otp_expiry'] = $expiry->format('Y-m-d H:i:s');

            $MailContent = "✅ Please verify your account : " . $otp;
            $name = $first_name . " " . $last_name;
            sendEmail($name, $email, $MailContent);

            // Fetch the inserted id
            $newUserId = $insert->insert_id;

            $_SESSION['id'] = $newUserId;
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Account created. Please verify.', 'id' => $newUserId]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal Server Error']);
        }

        $insert->close();

    }
} else {
    http_response_code(500);
    echo json_encode(['status' => "error", 'message' => "Internal Server Error"]);
    exit;
}

?>