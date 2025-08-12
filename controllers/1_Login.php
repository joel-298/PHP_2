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
$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
$password = isset($_POST['password']) ? sanitize($_POST['password']) : '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "All fields required !"]);
    exit;
}

if(strlen($email) > 100 ) {
    http_response_code(400) ; 
    echo json_encode(["status"=> "error", "message"=> "Email must be less than 100 characters."]);
    exit ; 
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid Email Format !"]);
    exit;
}






$query = $connection->prepare("SELECT * FROM users WHERE email = ? ");
if (!$query) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Internal Server Error"]);
    exit;
}

$query->bind_param("s", $email);
if ($query->execute()) {
    $result = $query->get_result();
    if ($result->num_rows === 1) { // EXISTS
        $row = $result->fetch_assoc();
        $id = $row['id'];
        $userPassword = $row['password'];

        if ($row['is_email_verified'] === '1') { // Email verified now check for password 
            if (password_verify($password, $userPassword)) {
                $query->close(); // free up space
                $_SESSION['id'] = $id;
                $_SESSION['isLoggedIn'] = true;
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "User Login Successfull !"]);
                exit;
            } else {
                $query->close(); // free up space
                http_response_code(401) ; // Unauthorized
                echo json_encode(["status" => "error", "message" => "Unauthorized"]);
                exit;
            }
        } else { // not verified
            $query->close();
            // OTP LOGIC : 
            $otp = random_int(100000, 999999);             // Generate random 6-digit OTP
            $_SESSION['otp'] = $otp;                       // Store OTP in session
            $_SESSION['showOtpPage'] = true;
            $_SESSION['page'] = 'login';

            // Set OTP expiry to current time + 1 minute (60 seconds)
            $expiry = new DateTime(); // current time
            $expiry->modify('+5 minute');
            $_SESSION['otp_expiry'] = $expiry->format('Y-m-d H:i:s');

            $MailContent = "✅ Please verify your account : " . $otp;
            $name = $row['first_name'] . " " . $row['last_name'];
            sendEmail($name, $email, $MailContent);

            $_SESSION['id'] = $id;
            http_response_code(401) ; 
            echo json_encode(["status" => "error", "message" => "Please verify your email"]);
            exit;
        }

    } else { // USER DOES NOT EXISTS 
        $query->close(); // free up space
        http_response_code(404) ; // not found
        echo json_encode(["status" => "error", "message" => "User Not Present Please Signup !"]);
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Internal Server Error"]);
    exit;
}

?>