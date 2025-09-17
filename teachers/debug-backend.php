<?php
session_start();

// Simulate a teacher session for testing
$_SESSION['user_id'] = 8; // Teacher with user_id 8 from the database
$_SESSION['role_id'] = 3;

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lars_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Connection Test</h2>";
echo "Connected successfully<br><br>";

// Test teacher data
$teacher_id = $_SESSION['user_id'];
echo "<h3>Teacher Info (ID: $teacher_id)</h3>";

$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    echo "Teacher Name: " . $row['first_name'] . ' ' . $row['last_name'] . "<br>";
}
$stmt->close();

// Test teacher subjects
echo "<h3>Teacher Subjects</h3>";
$stmt = $conn->prepare("SELECT DISTINCT s.grade_level, s.subject_name FROM subjects s 
                       JOIN teacher_subjects ts ON s.subject_id = ts.subject_id 
                       WHERE ts.teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$res = $stmt->get_result();
$grade_levels = [];
while ($row = $res->fetch_assoc()) {
    echo "Subject: " . $row['subject_name'] . " (Grade " . $row['grade_level'] . ")<br>";
    $grade_levels[] = $row['grade_level'];
}
$stmt->close();

// Test students in those grade levels
echo "<h3>Students in Grade Levels: " . implode(', ', $grade_levels) . "</h3>";
if (count($grade_levels) > 0) {
    $in = implode(',', array_fill(0, count($grade_levels), '?'));
    $types = str_repeat('s', count($grade_levels));
    $sql = "SELECT user_id, first_name, last_name, grade_level FROM users 
            WHERE grade_level IN ($in) AND role_id = 4";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$grade_levels);
    $stmt->execute();
    $res = $stmt->get_result();
    $student_count = 0;
    while ($row = $res->fetch_assoc()) {
        echo "Student: " . $row['first_name'] . ' ' . $row['last_name'] . " (Grade " . $row['grade_level'] . ")<br>";
        $student_count++;
    }
    echo "<br>Total Students: $student_count<br>";
    $stmt->close();
}

// Test the dashboard stats endpoint
echo "<h3>Dashboard Stats Endpoint Test</h3>";
$_GET['action'] = 'dashboard_stats';
include 'teacher-activities-backend.php';
?>