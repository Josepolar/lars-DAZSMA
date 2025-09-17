<?php
session_start();

// Set session to Jasmine Bautista
$_SESSION['user_id'] = 16;
$_SESSION['role_id'] = 4;

echo "<h1>Testing API Response for Jasmine Bautista</h1>\n";

// Test the API directly
$url = 'http://localhost/larss/api/student_dashboard.php';

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIESESSION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');

// Add session cookie
$sessionName = session_name();
$sessionId = session_id();
curl_setopt($ch, CURLOPT_COOKIE, "$sessionName=$sessionId");

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>API Response (HTTP $httpCode):</h2>\n";

if ($response) {
    $data = json_decode($response, true);
    
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
        
    } else {
        echo "<p>Failed to decode JSON response</p>\n";
        echo "<pre>" . htmlspecialchars($response) . "</pre>\n";
    }
} else {
    echo "<p>No response from API</p>\n";
}

// Test the profile section after API fix
echo "<h2>Testing Profile Display After API Fix:</h2>\n";
ob_start();
include 'students/student-home.php';
$content = ob_get_clean();

preg_match('/<span class="points" id="totalPoints">([^<]+)<\/span>/', $content, $pointsMatch);
if (isset($pointsMatch[1])) {
    echo "<p><strong>Initial Profile Points (PHP):</strong> {$pointsMatch[1]}</p>\n";
}

echo "<p><em>Note: The page will load JavaScript that calls the API and may update these values.</em></p>\n";
?>