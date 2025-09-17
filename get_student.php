<?php
header('Content-Type: application/json');

// Database connection
include 'Database/database.php';

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $query = "SELECT user_id, first_name, last_name, username, grade_level 
              FROM users 
              WHERE user_id = ? AND role_id = 4";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Student not found']);
    }
} else {
    echo json_encode(['error' => 'No ID provided']);
}
?>
