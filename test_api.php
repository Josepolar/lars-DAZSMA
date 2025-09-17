<?php
session_start();

// Simulate a student login for testing
$_SESSION['user_id'] = 14; // John Doe
$_SESSION['role_id'] = 4;

echo "Testing API calls...\n\n";

echo "=== TESTING student_dashboard.php ===\n";
ob_start();
include 'api/student_dashboard.php';
$output = ob_get_clean();
echo "Output: " . $output . "\n\n";

echo "=== TESTING student_activities.php ===\n";
$_GET['action'] = 'list';
ob_start();
include 'api/student_activities.php';
$output = ob_get_clean();
echo "Output: " . $output . "\n\n";

echo "Done testing.";
?>
