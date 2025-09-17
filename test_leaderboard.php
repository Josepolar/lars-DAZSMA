<?php
// Test the leaderboard query for Grade 7 students
$pdo = new PDO("mysql:host=localhost;dbname=lars_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$grade_level = '7';

echo "Testing leaderboard for Grade $grade_level:\n\n";

// First, let's see what students are in Grade 7
echo "Grade 7 students:\n";
$stmt = $pdo->prepare("SELECT user_id, first_name, last_name FROM users WHERE role_id = 4 AND grade_level = ?");
$stmt->execute([$grade_level]);
while ($row = $stmt->fetch()) {
    echo "- {$row['first_name']} {$row['last_name']} (ID: {$row['user_id']})\n";
}

echo "\nGrade 7 activities and submissions:\n";
$stmt = $pdo->query("
    SELECT 
        a.activity_id, 
        a.title, 
        s.subject_name, 
        ss.student_id, 
        ss.submission_status, 
        ss.total_score,
        CONCAT(u.first_name, ' ', u.last_name) as student_name
    FROM activities a 
    JOIN subjects s ON a.subject_id = s.subject_id 
    LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id
    LEFT JOIN users u ON ss.student_id = u.user_id
    WHERE s.grade_level = '7' 
    ORDER BY a.activity_id, ss.student_id
");
while ($row = $stmt->fetch()) {
    echo "- Activity: {$row['title']} | Student: " . ($row['student_name'] ?: 'No submissions') . " | Status: " . ($row['submission_status'] ?: 'N/A') . " | Score: " . ($row['total_score'] ?: 'N/A') . "\n";
}

echo "\nTesting updated leaderboard query:\n";
$leaderboardStmt = $pdo->prepare("
    SELECT 
        u.user_id,
        CONCAT(u.first_name, ' ', u.last_name) as full_name,
        u.first_name,
        u.last_name,
        COALESCE(SUM(CASE WHEN ss.submission_status = 'graded' AND s.grade_level = u.grade_level THEN ss.total_score ELSE 0 END), 0) as total_points,
        COUNT(CASE WHEN ss.submission_status = 'graded' AND s.grade_level = u.grade_level THEN 1 END) as completed_activities,
        COALESCE(AVG(CASE WHEN ss.submission_status = 'graded' AND s.grade_level = u.grade_level AND ss.percentage IS NOT NULL THEN ss.percentage END), 0) as avg_percentage
    FROM users u
    LEFT JOIN student_submissions ss ON u.user_id = ss.student_id
    LEFT JOIN activities a ON ss.activity_id = a.activity_id
    LEFT JOIN subjects s ON a.subject_id = s.subject_id
    WHERE u.role_id = 4 AND u.grade_level = ?
    GROUP BY u.user_id, u.first_name, u.last_name
    ORDER BY total_points DESC, avg_percentage DESC
    LIMIT 10
");
$leaderboardStmt->execute([$grade_level]);
$leaderboard = $leaderboardStmt->fetchAll(PDO::FETCH_ASSOC);

echo "Leaderboard results:\n";
foreach ($leaderboard as $index => $student) {
    $rank = $index + 1;
    echo "$rank. {$student['full_name']} - {$student['total_points']} points ({$student['completed_activities']} activities, {$student['avg_percentage']}% avg)\n";
}

if (count($leaderboard) == 0) {
    echo "No students found in leaderboard\n";
}
?>