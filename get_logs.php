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

    // Check if required tables exist
    $tables = ['user_logs', 'users', 'roles'];
    foreach ($tables as $table) {
        $tableCheck = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($tableCheck->rowCount() === 0) {
            throw new Exception("Table '$table' does not exist");
        }
    }

    // Get logs for the specified date
    $query = "SELECT 
                ul.log_id,
                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                r.role_name as role,
                ul.action,
                DATE_FORMAT(ul.action_timestamp, '%h:%i %p') as time
              FROM user_logs ul
              JOIN users u ON ul.user_id = u.user_id
              JOIN roles r ON u.role_id = r.role_id
              WHERE DATE(ul.action_timestamp) = ?
              ORDER BY ul.action_timestamp DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$date]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted_logs = [];
    foreach ($logs as $row) {
        $formatted_logs[] = [
            'user_name' => $row['user_name'],
            'role' => $row['role'],
            'action' => $row['action'],
            'time' => $row['time']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $formatted_logs
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
