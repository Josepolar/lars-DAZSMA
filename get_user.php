<?php
// Database connection
include 'Database/database.php';

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $query = "SELECT user_id, first_name, last_name, username, email, grade_level, role_id 
              FROM users WHERE user_id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        // Remove sensitive data like password
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
} else {
    echo json_encode(['error' => 'No user ID provided']);
}
?>
