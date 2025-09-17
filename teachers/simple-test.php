<?php
session_start();

// Simple test endpoint
if (isset($_GET['test'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'session_user_id' => $_SESSION['user_id'] ?? 'not set',
        'session_role_id' => $_SESSION['role_id'] ?? 'not set',
        'current_time' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Test database connection
$conn = new mysqli('localhost', 'root', '', 'lars_db');
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Test teacher query
if (isset($_SESSION['user_id'])) {
    $teacher_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ? AND role_id = 3");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'teacher_name' => $row['first_name'] . ' ' . $row['last_name'],
            'teacher_id' => $teacher_id
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Teacher not found with ID: ' . $teacher_id]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No session user_id']);
}
?>