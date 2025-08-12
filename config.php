<?php
    session_start() ; 
    $servername='localhost'; 
    $username='root'; 
    $password='' ; 
    $database='task'; 

    $connection = mysqli_connect($servername,$username,$password,$database) ; 
    if (!$connection) {
        die("Connection failed: " . $connection->connect_error);
    }

    $currentPage = basename($_SERVER['PHP_SELF']);
    // echo $currentPage ; 
    // Define base paths (no "../" since everything is now in the same directory)
    define('SUB_FOLDER', '/task_13/');
    define('INDEX_URL', SUB_FOLDER . 'index.php');
    define('CONTROLLERS_URL', SUB_FOLDER  . 'controllers/');
    define('CSS_URL', SUB_FOLDER  . 'css/');
    define('JS_URL', SUB_FOLDER  . 'js/');
    define('RESOURCES',SUB_FOLDER . 'public/') ; 
    define('BOOKING_UPLOADS', SUB_FOLDER . 'uploads/') ; 

    define('ADMIN_SUB_FOLDER', SUB_FOLDER . 'Admin/') ; 
    define('ADMIN_RESOURCES',ADMIN_SUB_FOLDER . 'assets/') ;

    define('SMTP_HOST', 'em2.pwh-r1.com'); 
    define('SMTP_USERNAME', 'joel@orientaloutsourcing.com') ; 
    define('SMTP_PASSWORD','Jerry@29879') ; 
    define('SMTP_PORT',587) ; 

    define('ALLOWED_IMAGE_TYPES', json_encode(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg']));
    define('MAX_IMAGE_SIZE', 2 * 1024 * 1024); // 2MB
    // File type and size contact-us
    define('ALLOWED_FILE_TYPES', json_encode(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']));
    define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
    define('ALLOWED_FILE_EXT', json_encode(['pdf', 'docx', 'xlsx']));
    // File type and size book-appointment
    define('ALLOWED_FILE_TYPES_APPOINTMENT', json_encode(['application/pdf']));
    define('MAX_FILE_SIZE_APPOINTMENT', 10 * 1024 * 1024); // 10 MB
    define('MAX_FILE_UPLOAD_LIMIT', 5);

    // Office working hours
    define('OFFICE_OPEN_TIME', '09:00');    // 9:00 AM
    define('OFFICE_CLOSE_TIME', '18:30');   // 6:30 PM
    // Lunch break hours
    define('LUNCH_START_TIME', '14:00');    // 2:00 PM
    define('LUNCH_END_TIME', '14:30');      // 2:30 PM


    // ADMIN GMAIL
    define('ADMIN_GMAIL','joel298@yopmail.com') ; 
?>