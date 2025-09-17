<?php
session_start();

// Set session to Jasmine Bautista
$_SESSION['user_id'] = 16;
$_SESSION['role_id'] = 4;

echo "<h1>Testing student-home.php with Jasmine's Session</h1>\n";

// Capture the output of student-home.php
ob_start();
include 'students/student-home.php';
$content = ob_get_clean();

// Extract the total points value from the page
preg_match('/<span class="points" id="totalPoints">([^<]+)<\/span>/', $content, $pointsMatch);
if (isset($pointsMatch[1])) {
    echo "<h2>Points displayed in profile: {$pointsMatch[1]}</h2>\n";
} else {
    echo "<h2>Could not extract points from profile section</h2>\n";
}

// Extract student name
preg_match('/<h3 class="student-name" id="studentName">([^<]+)<\/h3>/', $content, $nameMatch);
if (isset($nameMatch[1])) {
    echo "<h2>Student name displayed: {$nameMatch[1]}</h2>\n";
} else {
    echo "<h2>Could not extract student name</h2>\n";
}

// Extract completion rate
preg_match('/<span class="rate" id="completionRate">([^<]+)<\/span>/', $content, $rateMatch);
if (isset($rateMatch[1])) {
    echo "<h2>Completion rate displayed: {$rateMatch[1]}</h2>\n";
} else {
    echo "<h2>Could not extract completion rate</h2>\n";
}

// Show leaderboard position for verification
preg_match('/<div class="leaderboard">.*?<ul class="leaderboard-list"[^>]*>(.*?)<\/ul>/s', $content, $leaderboardMatch);
if (isset($leaderboardMatch[1])) {
    echo "<h3>Leaderboard extracted successfully</h3>\n";
    // Check if Jasmine is in the leaderboard
    if (strpos($leaderboardMatch[1], 'Jasmine Bautista') !== false) {
        echo "<p>✅ Jasmine found in leaderboard</p>\n";
        preg_match('/Jasmine Bautista.*?<div class="total-points">\s*([^<]+)\s*<\/div>/s', $leaderboardMatch[1], $leaderboardPoints);
        if (isset($leaderboardPoints[1])) {
            echo "<p>Leaderboard points: " . trim($leaderboardPoints[1]) . "</p>\n";
        }
    } else {
        echo "<p>❌ Jasmine NOT found in leaderboard</p>\n";
    }
} else {
    echo "<h3>Could not extract leaderboard</h3>\n";
}

echo "<h3>Raw Profile Section:</h3>\n";
preg_match('/<div class="profile-stats">(.*?)<\/div>/s', $content, $profileMatch);
if (isset($profileMatch[1])) {
    echo "<pre>" . htmlspecialchars($profileMatch[1]) . "</pre>\n";
}
?>