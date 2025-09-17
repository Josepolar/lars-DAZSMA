<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lars_db";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

echo "<h1>Testing Current userStatsStmt Query</h1>\n";

// Jasmine's details
$jasmine_id = 16;
$jasmine_grade = 7;

echo "<h2>Testing with Jasmine Bautista (ID: 16, Grade: 7)</h2>\n";

// Test the current query from student-home.php
echo "<h3>Current Query from student-home.php:</h3>\n";
$userStatsStmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = ? THEN ss.total_score ELSE 0 END), 0) as total_points,
        COUNT(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = ? THEN 1 END) as completed_activities,
        COUNT(CASE WHEN a.activity_id IS NOT NULL AND s.grade_level = ? THEN 1 END) as total_available_activities,
        COALESCE(AVG(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = ? AND ss.percentage IS NOT NULL THEN ss.percentage END), 0) as avg_percentage
    FROM student_submissions ss
    JOIN activities a ON ss.activity_id = a.activity_id
    JOIN subjects s ON a.subject_id = s.subject_id
    WHERE ss.student_id = ?
");
$userStatsStmt->execute([$jasmine_grade, $jasmine_grade, $jasmine_grade, $jasmine_grade, $jasmine_id]);
$userStats = $userStatsStmt->fetch(PDO::FETCH_ASSOC);

if ($userStats) {
    echo "<p><strong>Total Points:</strong> {$userStats['total_points']}</p>\n";
    echo "<p><strong>Completed Activities:</strong> {$userStats['completed_activities']}</p>\n";
    echo "<p><strong>Total Available:</strong> {$userStats['total_available_activities']}</p>\n";
    echo "<p><strong>Average Percentage:</strong> {$userStats['avg_percentage']}</p>\n";
} else {
    echo "<p>No results from current query</p>\n";
}

// Check what grade levels the activities belong to
echo "<h3>Checking Activity Grade Levels:</h3>\n";
$gradeCheckStmt = $pdo->prepare("
    SELECT 
        ss.activity_id,
        a.title as activity_title,
        s.subject_name,
        s.grade_level as subject_grade,
        ss.submission_status,
        ss.total_score
    FROM student_submissions ss
    JOIN activities a ON ss.activity_id = a.activity_id
    JOIN subjects s ON a.subject_id = s.subject_id
    WHERE ss.student_id = ?
");
$gradeCheckStmt->execute([$jasmine_id]);
$gradeCheck = $gradeCheckStmt->fetchAll(PDO::FETCH_ASSOC);

if ($gradeCheck) {
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>Activity ID</th><th>Activity Title</th><th>Subject</th><th>Subject Grade</th><th>Status</th><th>Score</th></tr>\n";
    foreach ($gradeCheck as $activity) {
        echo "<tr>\n";
        echo "<td>{$activity['activity_id']}</td>\n";
        echo "<td>{$activity['activity_title']}</td>\n";
        echo "<td>{$activity['subject_name']}</td>\n";
        echo "<td>{$activity['subject_grade']}</td>\n";
        echo "<td>{$activity['submission_status']}</td>\n";
        echo "<td>{$activity['total_score']}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
}

// Test a corrected query that doesn't rely on grade filtering
echo "<h3>Testing Simplified Query (No Grade Filtering):</h3>\n";
$simplifiedStmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') THEN ss.total_score ELSE 0 END), 0) as total_points,
        COUNT(CASE WHEN ss.submission_status IN ('submitted', 'graded') THEN 1 END) as completed_activities
    FROM student_submissions ss
    WHERE ss.student_id = ?
");
$simplifiedStmt->execute([$jasmine_id]);
$simplified = $simplifiedStmt->fetch(PDO::FETCH_ASSOC);

if ($simplified) {
    echo "<p><strong>Total Points (All Activities):</strong> {$simplified['total_points']}</p>\n";
    echo "<p><strong>Completed Activities (All):</strong> {$simplified['completed_activities']}</p>\n";
}
?>