<?php
include 'Database/database.php';

echo "Submission statuses in database:\n";
$stmt = $pdo->query("SELECT DISTINCT submission_status FROM student_submissions WHERE submission_status IS NOT NULL");
while($row = $stmt->fetch()) {
    echo "- {$row['submission_status']}\n";
}

echo "\nDetailed submission info for Grade 7:\n";
$stmt = $pdo->query("
    SELECT 
        ss.submission_id,
        ss.student_id,
        ss.activity_id,
        ss.submission_status,
        ss.total_score,
        ss.percentage,
        a.title,
        s.grade_level,
        CONCAT(u.first_name, ' ', u.last_name) as student_name
    FROM student_submissions ss
    JOIN activities a ON ss.activity_id = a.activity_id
    JOIN subjects s ON a.subject_id = s.subject_id  
    JOIN users u ON ss.student_id = u.user_id
    WHERE s.grade_level = '7'
    ORDER BY ss.student_id, ss.activity_id
");
while($row = $stmt->fetch()) {
    echo "Student: {$row['student_name']} | Activity: {$row['title']} | Status: {$row['submission_status']} | Score: {$row['total_score']} | Percentage: {$row['percentage']}\n";
}
?>