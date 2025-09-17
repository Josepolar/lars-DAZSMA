<?php
$pdo = new PDO("mysql:host=localhost;dbname=lars_db", "root", "");

echo "Grade 7 activities with details:\n";
$stmt = $pdo->query("
    SELECT a.activity_id, a.title, a.is_active, a.due_date, a.created_at, s.subject_name 
    FROM activities a 
    JOIN subjects s ON a.subject_id = s.subject_id 
    WHERE s.grade_level = '7'
");
while($row = $stmt->fetch()) {
    echo sprintf("ID: %d, Title: %s, Subject: %s, Active: %d, Due: %s, Created: %s\n", 
        $row['activity_id'], 
        $row['title'], 
        $row['subject_name'],
        $row['is_active'], 
        $row['due_date'] ?: 'NULL', 
        $row['created_at']
    );
}

echo "\nNow testing each part of the query step by step:\n";

// Test 1: Basic join
echo "\nTest 1: Basic activities with grade 7:\n";
$stmt = $pdo->query("
    SELECT a.activity_id, a.title, s.subject_name 
    FROM subjects s
    JOIN activities a ON s.subject_id = a.subject_id 
    WHERE s.grade_level = '7'
");
while($row = $stmt->fetch()) {
    echo "- {$row['title']} in {$row['subject_name']}\n";
}

// Test 2: Add active filter
echo "\nTest 2: Active activities only:\n";
$stmt = $pdo->query("
    SELECT a.activity_id, a.title, s.subject_name 
    FROM subjects s
    JOIN activities a ON s.subject_id = a.subject_id 
        AND a.is_active = 1 
    WHERE s.grade_level = '7'
");
while($row = $stmt->fetch()) {
    echo "- {$row['title']} in {$row['subject_name']}\n";
}

// Test 3: Add due date filter
echo "\nTest 3: Active activities with due date filter:\n";
$stmt = $pdo->query("
    SELECT a.activity_id, a.title, s.subject_name, a.due_date 
    FROM subjects s
    JOIN activities a ON s.subject_id = a.subject_id 
        AND a.is_active = 1 
        AND (a.due_date IS NULL OR a.due_date > NOW())
    WHERE s.grade_level = '7'
");
while($row = $stmt->fetch()) {
    echo "- {$row['title']} in {$row['subject_name']} (Due: " . ($row['due_date'] ?: 'No deadline') . ")\n";
}

// Test 4: Add student submissions join
echo "\nTest 4: With student submissions (student ID 14):\n";
$stmt = $pdo->prepare("
    SELECT a.activity_id, a.title, s.subject_name, ss.submission_status 
    FROM subjects s
    JOIN activities a ON s.subject_id = a.subject_id 
        AND a.is_active = 1 
        AND (a.due_date IS NULL OR a.due_date > NOW())
    LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
    WHERE s.grade_level = '7'
");
$stmt->execute([14]);
while($row = $stmt->fetch()) {
    echo "- {$row['title']} in {$row['subject_name']} (Status: " . ($row['submission_status'] ?: 'not_started') . ")\n";
}

// Test 5: Add submission status filter
echo "\nTest 5: With submission status filter:\n";
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
$stmt->execute([14]);
$count = 0;
while($row = $stmt->fetch()) {
    echo "- {$row['title']} in {$row['subject_name']} (Status: " . ($row['submission_status'] ?: 'not_started') . ")\n";
    $count++;
}
echo "Total: $count activities\n";
?>