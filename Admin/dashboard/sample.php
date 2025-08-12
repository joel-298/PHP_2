<?php
// SESSION START
// DB CONNECTION 
require_once dirname(dirname(__DIR__)) . '/config.php';
header('Content-Type: application/json'); // Makes sure client's browser will receive response in json format

// REQUEST METHOD 
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['admin_id'])) {
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



?>