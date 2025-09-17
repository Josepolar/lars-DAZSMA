<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Set session to Jasmine Bautista
$_SESSION['user_id'] = 16;
$_SESSION['role_id'] = 4;

echo "<h1>Testing API Response with Error Reporting</h1>\n";

// Test the API with error reporting
try {
    ob_start();
    include 'api/student_dashboard.php';
    $apiResponse = ob_get_clean();
    
    echo "<h2>Raw API Response:</h2>\n";
    echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>\n";
    
    $data = json_decode($apiResponse, true);
    if ($data) {
        echo "<h2>Parsed Data:</h2>\n";
        echo "<p>Total Points: {$data['stats']['total_points_earned']}</p>\n";
    } else {
        echo "<h2>JSON Decode Error:</h2>\n";
        echo "<p>" . json_last_error_msg() . "</p>\n";
    }
    
} catch (Exception $e) {
    echo "<h2>Exception:</h2>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
}
?>