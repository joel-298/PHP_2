<?php
// SESSION START
// DB CONNECTION 
require_once dirname(dirname(__DIR__)) . '/config.php';
header('Content-Type: application/json'); // Makes sure client's browser will receive response in json format

// REQUEST METHOD 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403); // Forbidden
    echo json_encode(["status" => "error", "message" => "Access Denied"]);
    exit;
}
// CSRF TOKEN VALIDATION
if (!isset($_POST['csrfToken']) || !isset($_SESSION['csrfToken']) ||  $_POST['csrfToken'] !== $_SESSION['csrfToken']) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => "error", "message" => "Your session has expired. Please refresh the page and try again"]);
    exit;
}

function sanitize($data){
    return htmlspecialchars(strip_tags(trim($data)));
}



// LOGIN
if ($_POST['function'] == "login") {
    // VALIDATIONS
    $email = isset($_POST['email']) ? sanitize($_POST["email"]) : '';
    $password = isset($_POST['password']) ? sanitize($_POST["password"]) : '';
    // Email Validation
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "All fields required"]);
        exit;
    }
    if (strlen($email) > 100) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email must be under 100 characters"]);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid Email Format !"]);
        exit;
    }


    $query = $connection->prepare("SELECT * FROM admin WHERE email = ? ");
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

            if (password_verify($password, $userPassword)) {
                $query->close(); // free up space
                $_SESSION['admin_id'] = $id;
                $_SESSION['isAdminLoggedIn'] = true;
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Admin Login Successfull !"]);
                exit;
            } else {
                $query->close(); // free up space
                http_response_code(401); // Unauthorized
                echo json_encode(["status" => "error", "message" => "Unauthorized"]);
                exit;
            }


        } else { // USER DOES NOT EXISTS 
            $query->close(); // free up space
            http_response_code(404); // not found
            echo json_encode(["status" => "error", "message" => "Admin Not Found !"]);
            exit;
        }
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Internal Server Error"]);
        exit;
    }


}




// EDIT PROFILE 
else if($_POST['function'] == "editProfile") {
    $userId = isset($_SESSION["admin_id"]) ? $_SESSION["admin_id"] : "" ; 
    if($userId == "") {
        http_response_code(403) ; 
        echo json_encode(["status"=> "error", "message"=> "Access Denied"]);
        exit ; 
    }
    // VALIDATIONS
    $first_name = isset($_POST['first_name']) ? sanitize($_POST["first_name"]) : '';
    $last_name = isset($_POST['last_name']) ? sanitize($_POST["last_name"]) : '';
    $filePath = null;
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize($_POST["phone"]) : '';
    $address = isset($_POST['address']) ? sanitize($_POST['address']) : '' ; 

    if (empty($first_name) || empty($last_name) || empty($email)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Marked fields required.']);
        exit;
    }
    if(strlen($first_name) > 100 || strlen($last_name) > 100) {
        http_response_code(400) ; 
        echo json_encode(["status"=> "error", "message"=> "First and Last name must be less than 100 characters"]);
        exit ; 
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
    if (strlen($phone) != 0) { // if phone is not null then only run this condition
        if (strlen($phone) < 10 || strlen($phone) > 15 || preg_match('/[a-zA-Z]/', $phone) || preg_match('/[^0-9+]/', $phone)) {
            http_response_code(400); // Bad Request
            echo json_encode(["status" => "error", "message" => "Invalid Phone Number !"]);
            exit;
        }
    }
    // Get image file if uploaded
    $image = $_FILES['profile_image'] ?? null;
    $allowedTypes = json_decode(ALLOWED_IMAGE_TYPES, true);
    $maxSize = MAX_IMAGE_SIZE;

    // VALIDATE IMAGE IF EXISTS
    if ($image && $image['error'] === UPLOAD_ERR_OK) {
        $imageTmp = $image['tmp_name'];
        $imageName = basename($image['name']);
        $imageType = mime_content_type($imageTmp);
        $imageSize = $image['size'];

        if (!in_array($imageType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid image type.']);
            exit;
        }

        if ($imageSize > $maxSize) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Image exceeds size limit.']);
            exit;
        }

        // Everything valid — move image
        $ext = pathinfo($imageName, PATHINFO_EXTENSION);
        $newImageName = uniqid('profile_') . '.' . $ext;
        $uploadDir = dirname(__DIR__) . '/uploads/';
        $targetPath = $uploadDir . $newImageName;

        if (!move_uploaded_file($imageTmp, $targetPath)) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to save image.']);
            exit;
        }

        $filePath = '../uploads/' . $newImageName;
    }

    // GET EXISTING USER INFO
    $stmt = $connection->prepare("SELECT profile_image FROM admin WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        http_response_code(404);
        session_unset();
        session_destroy();
        echo json_encode(['status' => 'error', 'message' => 'Admin not found.']);
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();
    // REMOVE OLD IMAGE IF NEW ONE IS UPLOADED
    if ($filePath && !empty($user['profile_image'])) {
        $oldImagePath = dirname(__DIR__) . '/uploads/' . basename($user['profile_image']);
        if (file_exists($oldImagePath)) {
            unlink($oldImagePath); // Delete old image
        }
    }

    // ADMIN EMAIL CHECK
    $checkStmt = $connection->prepare("SELECT id FROM admin WHERE email = ? AND id != ?");
    $checkStmt->bind_param("si", $email, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Email already exists."]);
        exit;
    }
    $checkStmt->close();

    // UPDATE ADMIN USER
    $updateQuery = "UPDATE admin SET first_name = ?, last_name = ? , phone = ? , address = ? ";
    $params = [$first_name, $last_name, $phone , $address];
    $types = "ssss";

    if ($email) {
        $updateQuery .= ", email = ?";
        $params[] = $email;
        $types .= "s";
    }

    if ($filePath) {
        $updateQuery .= ", profile_image = ?";
        $params[] = $filePath;
        $types .= "s";
    }

    $updateQuery .= " WHERE id = ?";
    $params[] = $userId;
    $types .= "i";


    $stmt = $connection->prepare($updateQuery);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        http_response_code(200) ; 
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Internal Server Error']);
    }
    $stmt->close(); 
}




// CHANGE PASSWORD 
else if($_POST['function'] == "changePassword") {

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
    $userId = $_SESSION['admin_id'] ?? null;
    if (!$userId) {
        session_unset();
        session_destroy(); 
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized"]);
        exit;
    }

    // FETCH CURRENT PASSWORD HASH FROM DB
    $stmt = $connection->prepare("SELECT password FROM admin WHERE id = ?");
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

    $updateStmt = $connection->prepare("UPDATE admin SET password = ? WHERE id = ?");
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

}


else if($_POST['function'] == "DeleteUser") {

    if (isset($_POST['id'])) {
        // === Single delete ===
        $id = (int) $_POST['id'];

        $stmt = $connection->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $stmt->close();
                http_response_code(200) ; 
                echo json_encode(['status' => "success", 'message' => 'Customer deleted successfully']);
                exit ;
            } else {
                $stmt->close();
                http_response_code(404) ; 
                echo json_encode(['status' => "error", 'message' => 'Customer not found or already deleted']);
                exit ; 
            }
        } else {
            $stmt->close();
            http_response_code(500) ; 
            echo json_encode(['status' => "error", 'message' => 'Internal Server Error']);
            exit;
        }

    } elseif (isset($_POST['ids']) && is_array($_POST['ids'])) {
        // === Bulk delete ===
        $ids = array_map('intval', $_POST['ids']);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));

            $stmt = $connection->prepare("DELETE FROM users WHERE id IN ($placeholders)");
            if ($stmt) {
                $stmt->bind_param($types, ...$ids);
                $stmt->execute();
                http_response_code(200) ; 
                echo json_encode(['success' => true, 'message' => count($ids) . ' customer(s) deleted']);
                $stmt->close();
                exit ; 
            } else {
                http_response_code(500) ; 
                echo json_encode(['success' => false, 'message' => 'Internal Server Error']);
                $stmt->close();
                exit ; 
            }
        }
    }

}


?>