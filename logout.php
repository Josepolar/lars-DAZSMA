<?php

session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;

// Log the logout action if user_id exists
if ($user_id) {
    include 'Database/database.php';
    $log_query = "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, 'Logout', ?)";
    $log_stmt = $pdo->prepare($log_query);
    $ip = $_SERVER['REMOTE_ADDR'];
    $log_stmt->execute([$user_id, $ip]);
}

// Clear and destroy session
session_unset();
session_destroy();

// Redirect based on user role
if ($role == 2) {
    header('Location: staff/staff-login.php');
} elseif ($role == 3) {
    header('Location: teachers/teacher-login.php');
} elseif ($role == 4) {
    header('Location: students/stud-login.php');
} elseif ($role == 1) {
    header('Location: admin/admin-login.php');
} else {
    header('Location: index.php');
}
exit();
?>
