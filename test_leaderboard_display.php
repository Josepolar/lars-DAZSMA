<?php
session_start();

// Test with Jasmine Bautista (Grade 7)
$_SESSION['user_id'] = 16;
$_SESSION['role_id'] = 4;

echo "<h1>Testing Updated Leaderboard Display</h1>\n";

// Capture the leaderboard section from student-home.php
ob_start();
include 'students/student-home.php';
$content = ob_get_clean();

// Extract just the leaderboard section
preg_match('/<div class="leaderboard">(.*?)<\/div>\s*<\/div>/s', $content, $leaderboardMatch);

if (isset($leaderboardMatch[1])) {
    echo "<h2>Leaderboard HTML Generated:</h2>\n";
    echo "<div style='border: 1px solid #ddd; padding: 15px; background: #f9f9f9;'>\n";
    echo "<div class='leaderboard'>" . $leaderboardMatch[1] . "</div>\n";
    echo "</div>\n";
    
    // Check if the new structure is present
    if (strpos($leaderboardMatch[1], 'rank-number') !== false) {
        echo "<p style='color: green;'>✅ New leaderboard structure is working</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Old leaderboard structure detected</p>\n";
    }
    
    if (strpos($leaderboardMatch[1], 'student-avatar') !== false) {
        echo "<p style='color: green;'>✅ Avatar structure is present</p>\n";
    }
    
    if (strpos($leaderboardMatch[1], 'student-points') !== false) {
        echo "<p style='color: green;'>✅ Points structure is present</p>\n";
    }
    
} else {
    echo "<h2>Could not extract leaderboard section</h2>\n";
    
    // Check for errors in the page
    if (strpos($content, 'Fatal error') !== false) {
        echo "<p style='color: red;'>PHP Fatal Error detected in page</p>\n";
    }
    
    if (strpos($content, 'Warning') !== false) {
        echo "<p style='color: orange;'>PHP Warning detected in page</p>\n";
    }
}

echo "<h2>Page Generation Test:</h2>\n";
echo "<p>Page size: " . strlen($content) . " characters</p>\n";

if (strpos($content, 'LEADERBOARDS - GRADE') !== false) {
    echo "<p style='color: green;'>✅ Leaderboard title is present</p>\n";
} else {
    echo "<p style='color: red;'>❌ Leaderboard title missing</p>\n";
}
?>