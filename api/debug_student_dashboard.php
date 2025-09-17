<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lars_db";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$student_id = $_SESSION['user_id'];

// Get student's grade level
$gradeStmt = $pdo->prepare("SELECT grade_level FROM users WHERE user_id = ?");
$gradeStmt->execute([$student_id]);
$student_grade = $gradeStmt->fetchColumn();

// Get all activities for this grade
$activitiesStmt = $pdo->prepare("
    SELECT a.*, s.subject_name, s.grade_level, a.is_active, a.due_date
    FROM activities a
    JOIN subjects s ON a.subject_id = s.subject_id
    WHERE s.grade_level = ?
    ORDER BY a.due_date DESC
");
$activitiesStmt->execute([$student_grade]);
$all_activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get only visible activities (active, not past due)
$visible_activities = array_filter($all_activities, function($a) {
    $now = date('Y-m-d H:i:s');
    return $a['is_active'] == 1 && (empty($a['due_date']) || $a['due_date'] > $now);
});

// Output for debugging
$output = [
    'student_id' => $student_id,
    'student_grade' => $student_grade,
    'all_activities_for_grade' => $all_activities,
    'visible_activities_for_grade' => array_values($visible_activities),
    'now' => date('Y-m-d H:i:s'),
];
echo json_encode($output, JSON_PRETTY_PRINT);
