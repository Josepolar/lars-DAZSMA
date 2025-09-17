<?php
include 'Database/database.php';

// Update one activity to have no due date for testing
$pdo->exec("UPDATE activities SET due_date = NULL WHERE activity_id = 26");
echo "Updated activity 26 to have no due date\n";

// Now test the query again
$student_id = 14;
$stmt = $pdo->prepare("
    SELECT a.activity_id, a.title, s.subject_name, ss.submission_status 
    FROM subjects s
    JOIN activities a ON s.subject_id = a.subject_id 
        AND a.is_active = 1 
        AND (a.due_date IS NULL OR a.due_date > NOW())
    LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
    WHERE s.grade_level = '7'
    AND (ss.submission_status IS NULL OR ss.submission_status IN ('not_started', 'in_progress'))
");
$stmt->execute([$student_id]);
$activities = $stmt->fetchAll();

echo "Found " . count($activities) . " activities\n";
foreach($activities as $activity) {
    echo "- {$activity['title']} (Status: " . ($activity['submission_status'] ?: 'not_started') . ")\n";
}
?>