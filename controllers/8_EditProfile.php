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
$first_name = isset($_POST['first_name']) ? sanitize($_POST["first_name"]) : '';
$last_name = isset($_POST['last_name']) ? sanitize($_POST["last_name"]) : '';
$filePath = null;
$dob = isset($_POST['dob']) ? sanitize($_POST['dob']) : '';
$userId = $_SESSION['id'] ?? null;
$skills = isset($_POST['skills']) ? json_decode($_POST['skills'], true) : [];
if (!is_array($skills)) {
    $skills = [];
}
// Sanitize each skill using a loop
foreach ($skills as $key => $skill) {
    $skills[$key] = sanitize($skill);
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}
if (empty($first_name) || empty($last_name)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'First and Last name required.']);
    exit;
}
if ( strtotime($dob) >  strtotime(date('d-m-Y')) ) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Date of birth cannot be in the future.']);
    exit ; 
}
if (strtotime($dob) < strtotime('01-01-1900')) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Date of birth cannot be before the year 1900.']);
    exit;
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

    $filePath = 'uploads/' . $newImageName;
}

// GET EXISTING USER INFO
$stmt = $connection->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    http_response_code(404);
    session_unset();
    session_destroy();
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();
// REMOVE OLD IMAGE IF NEW ONE IS UPLOADED
if ($filePath && !empty($user['profile_image'])) {
    $oldImagePath = dirname(__DIR__) . '/' . $user['profile_image'];
    if (file_exists($oldImagePath)) {
        unlink($oldImagePath); // Delete old image
    }
}



$connection->begin_transaction();
try {

    // UPDATE USER
    $updateQuery = "UPDATE users SET first_name = ?, last_name = ?";
    $params = [$first_name, $last_name];
    $types = "ss";

    if ($dob) {
        $updateQuery .= ", dob = ?";
        $params[] = $dob;
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

    $updateStmt = $connection->prepare($updateQuery);
    $updateStmt->bind_param($types, ...$params);

    if (empty($skills)) {
        // Delete all user skills if input is empty
        $delStmt = $connection->prepare("DELETE FROM user_skills WHERE user_id = ?");
        $delStmt->bind_param("i", $userId);
        $delStmt->execute();
        $delStmt->close(); 
    } else {
        // Step 1: Get all skills from DB
        $skillRes = $connection->query("SELECT id, name FROM skills");
        $skillMap = [];
        while ($row = $skillRes->fetch_assoc()) {
            $skillMap[strtolower($row['name'])] = (int) $row['id'];
        }

        // Step 2: Convert user input skill names to skill IDs
        $userSkillIds = [];
        foreach ($skills as $name) {
            $key = strtolower(trim($name));
            if (isset($skillMap[$key])) {
                $userSkillIds[] = $skillMap[$key];
            }
        }

        // Step 3: Get existing skill_ids from DB for this user
        $stmt = $connection->prepare("SELECT skill_id FROM user_skills WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $existingSkillIds = [];
        while ($row = $result->fetch_assoc()) {
            $existingSkillIds[] = (int) $row['skill_id'];
        }
        $stmt->close();  
        // Step 4: Find which to insert / delete
        $toInsert = array_diff($userSkillIds, $existingSkillIds);
        $toDelete = array_diff($existingSkillIds, $userSkillIds);

        // Step 5: Insert new ones
        if (!empty($toInsert)) {
            $insertStmt = $connection->prepare("INSERT INTO user_skills (user_id, skill_id) VALUES (?, ?)");
            foreach ($toInsert as $skillId) {
                $insertStmt->bind_param("ii", $userId, $skillId);
                $insertStmt->execute();
            }
            $insertStmt->close();
        }

        // Step 6: Delete removed ones
        if (!empty($toDelete)) {
            $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
            $types = str_repeat('i', count($toDelete)) . 'i';
            $values = array_merge($toDelete, [$userId]);

            $query = "DELETE FROM user_skills WHERE skill_id IN ($placeholders) AND user_id = ?";
            $deleteStmt = $connection->prepare($query);
            $deleteStmt->bind_param($types, ...$values);
            $deleteStmt->execute();
        }
    }

    if ($updateStmt->execute()) {
        $connection->commit(); // COMMIT THE TRANSACTION
        http_response_code(200) ; 
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
    } else {
        throw new Exception("Failed to update user");
    }
    $updateStmt->close(); 
} catch (Exception $e) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error.']);
}

?>