<?php
// Test the fixed query
$pdo = new PDO("mysql:host=localhost;dbname=lars_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$student_id = 14; // Grade 7 student
$profile = ['grade_level' => '7'];

echo "Testing fixed subjects query:\n";

$subjectsStmt = $pdo->prepare("
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
    WHERE s.grade_level = ?
    AND (ss.submission_status IS NULL OR ss.submission_status IN ('not_started', 'in_progress'))
    GROUP BY s.subject_id, s.subject_name, s.grade_level
    ORDER BY s.subject_name
");
$subjectsStmt->execute([$student_id, $profile['grade_level']]);
$subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

echo "Subjects found: " . count($subjects) . "\n";
foreach($subjects as $subject) {
    echo sprintf("- %s (Grade %s): %d activities\n", 
        $subject['subject_name'], 
        $subject['grade_level'], 
        $subject['active_activities_count']
    );
}
?>