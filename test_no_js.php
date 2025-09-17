<?php
session_start();

echo "<h1>Testing Profile Points WITHOUT JavaScript Override</h1>\n";

// Test multiple users
$testUsers = [
    ['id' => 16, 'name' => 'Jasmine Bautista', 'expected' => 40],
    ['id' => 24, 'name' => 'Trisha Alvarez', 'expected' => 40],
    ['id' => 26, 'name' => 'John Doe (Grade 8)', 'expected' => 0]
];

foreach ($testUsers as $user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role_id'] = 4;
    
    echo "<h2>Testing: {$user['name']} (Expected: {$user['expected']} points)</h2>\n";
    
    ob_start();
    include 'students/student-home.php';
    $content = ob_get_clean();
    
    // Extract points from profile
    preg_match('/<span class="points" id="totalPoints">([^<]+)<\/span>/', $content, $pointsMatch);
    $profilePoints = isset($pointsMatch[1]) ? trim($pointsMatch[1]) : 'NOT FOUND';
    
    // Extract student name
    preg_match('/<h3 class="student-name" id="studentName">([^<]+)<\/h3>/', $content, $nameMatch);
    $profileName = isset($nameMatch[1]) ? trim($nameMatch[1]) : 'NOT FOUND';
    
    // Extract completion rate
    preg_match('/<span class="rate" id="completionRate">([^<]+)<\/span>/', $content, $rateMatch);
    $completionRate = isset($rateMatch[1]) ? trim($rateMatch[1]) : 'NOT FOUND';
    
    echo "<p><strong>Profile Name:</strong> {$profileName}</p>\n";
    echo "<p><strong>Profile Points:</strong> {$profilePoints}</p>\n";
    echo "<p><strong>Completion Rate:</strong> {$completionRate}</p>\n";
    
    // Verify if points match expected
    if ($profilePoints == $user['expected']) {
        echo "<p style='color: green;'>✅ PHP values are CORRECT (no JS interference)</p>\n";
    } else {
        echo "<p style='color: red;'>❌ PHP values are INCORRECT: Expected {$user['expected']}, Got {$profilePoints}</p>\n";
    }
    
    echo "<hr>\n";
}

echo "<h2>Conclusion:</h2>\n";
echo "<p>JavaScript has been temporarily disabled. If all values show as correct above, ";
echo "then the issue is with the JavaScript/API overriding the correct PHP values.</p>\n";
?>