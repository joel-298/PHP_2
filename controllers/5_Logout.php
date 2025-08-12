<?php
session_start();
header('Content-Type: application/json'); // Makes sure client's browser will receive response in json format

session_unset();
session_destroy();


echo json_encode([
    "status" => "success",
    "message" => "You have been logged out successfully!"
]);
exit;
?>