<?php
// Test the updated leaderboard query
$pdo = new PDO("mysql:host=localhost;dbname=lars_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$grade_level = '7';

echo "Testing UPDATED leaderboard for Grade $grade_level:\n\n";

$leaderboardStmt = $pdo->prepare("
    SELECT 
        u.user_id,
        CONCAT(u.first_name, ' ', u.last_name) as full_name,
        u.first_name,
        u.last_name,
        COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level THEN ss.total_score ELSE 0 END), 0) as total_points,
        COUNT(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level THEN 1 END) as completed_activities,
        COALESCE(AVG(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level AND ss.percentage IS NOT NULL THEN ss.percentage END), 0) as avg_percentage
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

echo "Updated leaderboard results:\n";
foreach ($leaderboard as $index => $student) {
    $rank = $index + 1;
    echo "$rank. {$student['full_name']} - {$student['total_points']} points ({$student['completed_activities']} activities, " . round($student['avg_percentage'], 1) . "% avg)\n";
}

// Test for another grade level too
echo "\n\nTesting leaderboard for Grade 8:\n";
$leaderboardStmt->execute(['8']);
$leaderboard8 = $leaderboardStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($leaderboard8 as $index => $student) {
    $rank = $index + 1;
    echo "$rank. {$student['full_name']} - {$student['total_points']} points ({$student['completed_activities']} activities, " . round($student['avg_percentage'], 1) . "% avg)\n";
}
?>