<?php
session_start();

// Set session to Jasmine Bautista
$_SESSION['user_id'] = 16;
$_SESSION['role_id'] = 4;

echo "<h1>Testing API Response for Jasmine Bautista</h1>\n";

// Capture the API output
ob_start();
include 'api/student_dashboard.php';
$apiResponse = ob_get_clean();

echo "<h2>API Response:</h2>\n";
$data = json_decode($apiResponse, true);

if ($data) {
    echo "<h3>Profile:</h3>\n";
    echo "<p><strong>Name:</strong> {$data['profile']['first_name']} {$data['profile']['last_name']}</p>\n";
    echo "<p><strong>Grade:</strong> {$data['profile']['grade_level']}</p>\n";
    
    echo "<h3>Statistics:</h3>\n";
    echo "<p><strong>Total Points Earned:</strong> {$data['stats']['total_points_earned']}</p>\n";
    echo "<p><strong>Completed Submissions:</strong> {$data['stats']['completed_submissions']}</p>\n";
    echo "<p><strong>Total Submissions:</strong> {$data['stats']['total_submissions']}</p>\n";
    echo "<p><strong>Completion Rate:</strong> {$data['stats']['completion_rate']}%</p>\n";
    echo "<p><strong>Average Percentage:</strong> {$data['stats']['average_percentage']}%</p>\n";
    
    // Compare with expected values
    if ($data['stats']['total_points_earned'] == 40) {
        echo "<p style='color: green;'>✅ API now returns correct points (40)</p>\n";
    } else {
        echo "<p style='color: red;'>❌ API still returns incorrect points: {$data['stats']['total_points_earned']}</p>\n";
    }
    
    echo "<h3>Recent Submissions Count:</h3>\n";
    echo "<p>Recent submissions: " . count($data['recent_submissions']) . "</p>\n";
    
    if (!empty($data['recent_submissions'])) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Activity</th><th>Subject</th><th>Score</th><th>Status</th></tr>\n";
        foreach ($data['recent_submissions'] as $submission) {
            echo "<tr>\n";
            echo "<td>{$submission['activity_title']}</td>\n";
            echo "<td>{$submission['subject_name']}</td>\n";
            echo "<td>{$submission['total_score']}</td>\n";
            echo "<td>{$submission['submission_status']}</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
} else {
    echo "<p>Failed to decode JSON response</p>\n";
    echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>\n";
}
?>