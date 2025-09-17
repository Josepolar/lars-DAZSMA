<?php
// Detailed debug script
$pdo = new PDO("mysql:host=localhost;dbname=lars_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$student_id = 14; // Grade 7 student

echo "=== DEBUG: Step by step analysis ===\n\n";

// Step 1: Check if Grade 7 subjects exist
echo "Step 1: Grade 7 subjects:\n";
$result = $pdo->query("SELECT subject_id, subject_name FROM subjects WHERE grade_level = '7'");
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "- {$row['subject_name']} (ID: {$row['subject_id']})\n";
}

// Step 2: Check if Grade 7 activities exist
echo "\nStep 2: Grade 7 active activities:\n";
$stmt = $pdo->prepare("
    SELECT a.activity_id, a.title, s.subject_name 
    FROM activities a 
    JOIN subjects s ON a.subject_id = s.subject_id 
    WHERE s.grade_level = '7' AND a.is_active = 1
");
$stmt->execute();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- {$row['title']} in {$row['subject_name']} (ID: {$row['activity_id']})\n";
}

// Step 3: Check student submissions for this student
echo "\nStep 3: Student submissions for student ID $student_id:\n";
$stmt = $pdo->prepare("SELECT activity_id, submission_status FROM student_submissions WHERE student_id = ?");
$stmt->execute([$student_id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($submissions) > 0) {
    foreach($submissions as $sub) {
        echo "- Activity {$sub['activity_id']}: {$sub['submission_status']}\n";
    }
} else {
    echo "- No submissions found\n";
}

// Step 4: Test simplified subjects query (without submissions filter)
echo "\nStep 4: Simplified subjects query (no submission filter):\n";
$stmt = $pdo->prepare("
    SELECT 
        s.subject_id,
        s.subject_name,
        COUNT(a.activity_id) as active_activities_count
    FROM subjects s
    LEFT JOIN activities a ON s.subject_id = a.subject_id 
        AND a.is_active = 1 
        AND (a.due_date IS NULL OR a.due_date > NOW())
    WHERE s.grade_level = ?
    GROUP BY s.subject_id, s.subject_name
    HAVING active_activities_count > 0
");
$stmt->execute(['7']);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- {$row['subject_name']}: {$row['active_activities_count']} activities\n";
}

// Step 5: Test the problematic query with detailed output
echo "\nStep 5: Full query with submission logic:\n";
$stmt = $pdo->prepare("
    SELECT 
        s.subject_id,
        s.subject_name,
        s.grade_level,
        a.activity_id,
        a.title,
        ss.submission_status,
        COUNT(a.activity_id) as active_activities_count
    FROM subjects s
    LEFT JOIN activities a ON s.subject_id = a.subject_id 
        AND a.is_active = 1 
        AND (a.due_date IS NULL OR a.due_date > NOW())
    LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
    WHERE s.grade_level = ?
    GROUP BY s.subject_id, s.subject_name, s.grade_level
");
$stmt->execute([$student_id, '7']);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- Subject: {$row['subject_name']}, Activity: {$row['title']}, Status: {$row['submission_status']}, Count: {$row['active_activities_count']}\n";
}
?>