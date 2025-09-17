<?php
// Debug script to test student activities API
$pdo = new PDO("mysql:host=localhost;dbname=lars_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$student_id = 14; // Grade 7 student - John Doe

// Get student profile
$profileStmt = $pdo->prepare("SELECT grade_level FROM users WHERE user_id = ?");
$profileStmt->execute([$student_id]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
echo "Student Grade: " . $profile['grade_level'] . "\n";

// Test subjects query - same as in student_dashboard.php
$subjectsStmt = $pdo->prepare("
    SELECT 
        s.subject_id,
        s.subject_name,
        s.grade_level,
        COUNT(a.activity_id) as active_activities_count
    FROM subjects s
    LEFT JOIN activities a ON s.subject_id = a.subject_id 
        AND a.is_active = 1 
        AND (a.due_date IS NULL OR a.due_date > NOW())
    LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
    WHERE s.grade_level = ?
    AND (ss.submission_status IS NULL OR ss.submission_status IN ('not_started', 'in_progress'))
    GROUP BY s.subject_id, s.subject_name, s.grade_level
    HAVING active_activities_count > 0
    ORDER BY s.subject_name
");
$subjectsStmt->execute([$student_id, $profile['grade_level']]);
$subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

echo "Subjects found: " . count($subjects) . "\n";
foreach($subjects as $subject) {
    echo sprintf("Subject: %s (Grade %s) - %d activities\n", 
        $subject['subject_name'], 
        $subject['grade_level'], 
        $subject['active_activities_count']
    );
}

// Test pending activities query 
$pendingActivitiesStmt = $pdo->prepare("
    SELECT 
        a.activity_id,
        a.title as activity_title,
        s.subject_name,
        s.grade_level,
        a.activity_type,
        a.total_points,
        a.time_limit,
        a.due_date,
        CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
        COALESCE(ss.submission_status, 'not_started') as submission_status
    FROM activities a
    JOIN subjects s ON a.subject_id = s.subject_id
    JOIN users t ON a.teacher_id = t.user_id
    LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
    WHERE a.is_active = 1 
    AND s.grade_level = ?
    AND (ss.submission_status IS NULL OR ss.submission_status IN ('not_started', 'in_progress'))
    AND (a.due_date IS NULL OR a.due_date > NOW())
    ORDER BY 
        CASE WHEN a.due_date IS NULL THEN 1 ELSE 0 END,
        a.due_date ASC
    LIMIT 10
");
$pendingActivitiesStmt->execute([$student_id, $profile['grade_level']]);
$pendingActivities = $pendingActivitiesStmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nPending activities found: " . count($pendingActivities) . "\n";
foreach($pendingActivities as $activity) {
    echo sprintf("Activity: %s (%s) - Subject: %s (Grade %s) - Status: %s\n", 
        $activity['activity_title'], 
        $activity['activity_type'],
        $activity['subject_name'], 
        $activity['grade_level'],
        $activity['submission_status']
    );
}
?>