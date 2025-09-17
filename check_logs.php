<?php
header('Content-Type: application/json');

// Database connection
include 'Database/database.php';

try {
    // Validate date parameter
    $date = $_GET['date'] ?? date('Y-m-d');
    if (!strtotime($date)) {
        throw new Exception('Invalid date format');
    }

    // Check if user_logs table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'user_logs'");
    if ($tableCheck->fetchColumn() === false) {
        throw new Exception('User logs table does not exist');
    }

    // Check if there are any logs for the given date
    $query = "SELECT COUNT(*) as count FROM user_logs WHERE DATE(action_timestamp) = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'hasLogs' => $row['count'] > 0,
        'count' => $row['count']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
