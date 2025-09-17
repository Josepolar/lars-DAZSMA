<?php
$pdo = new PDO("mysql:host=localhost;dbname=lars_db", "root", "");

// Update activities to have future due dates
$pdo->exec("UPDATE activities SET due_date = DATE_ADD(NOW(), INTERVAL 2 HOUR) WHERE activity_id IN (27, 28)");
echo "Updated activities 27 and 28 to have future due dates\n";

// Test again
$student_id = 14;
$stmt = $pdo->prepare("
    SELECT 
        s.subject_id,
        s.subject_name,
        s.grade_level,
        COUNT(DISTINCT a.activity_id) as active_activities_count
    FROM subjects s
    JOIN activities a ON s.subject_id = a.subject_id 
        AND a.is_active = 1 
        AND (a.due_date IS NULL OR a.due_date > NOW())
    LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
    WHERE s.grade_level = '7'
    AND (ss.submission_status IS NULL OR ss.submission_status IN ('not_started', 'in_progress'))
    GROUP BY s.subject_id, s.subject_name, s.grade_level
    ORDER BY s.subject_name
");
$stmt->execute([$student_id]);
$subjects = $stmt->fetchAll();

echo "Subjects found: " . count($subjects) . "\n";
foreach($subjects as $subject) {
    echo "- {$subject['subject_name']} (Grade {$subject['grade_level']}): {$subject['active_activities_count']} activities\n";
}
?>