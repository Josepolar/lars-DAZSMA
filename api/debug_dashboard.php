<?php
session_start();
header('Content-Type: application/json');

// Database connection
include '../Database/database.php';

try {
    // Check tables existence
    $tables = [
        'users' => false,
        'subjects' => false,
        'activities' => false,
        'student_submissions' => false,
        'activity_game_content' => false
    ];

    foreach (array_keys($tables) as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $tables[$table] = $stmt->rowCount() > 0;
    }

    // Get table structures
    $structures = [];
    foreach ($tables as $table => $exists) {
        if ($exists) {
            $stmt = $pdo->query("DESCRIBE $table");
            $structures[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Check for sample data
    $counts = [];
    foreach ($tables as $table => $exists) {
        if ($exists) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $counts[$table] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        }
    }

    echo json_encode([
        'tables_status' => $tables,
        'table_structures' => $structures,
        'record_counts' => $counts,
        'session' => [
            'user_id' => $_SESSION['user_id'] ?? null,
            'role_id' => $_SESSION['role_id'] ?? null
        ]
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTrace()
    ]);
}
?>