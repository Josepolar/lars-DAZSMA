<?php
// Database connection
include 'Database/database.php';

// Add affected_user_id column if it doesn't exist
$result = $pdo->query("SHOW COLUMNS FROM user_logs LIKE 'affected_user_id'");
if ($result->rowCount() == 0) {
    $pdo->exec("ALTER TABLE user_logs ADD affected_user_id INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE user_logs ADD FOREIGN KEY (affected_user_id) REFERENCES users(user_id)");
    echo "Added affected_user_id column\n";
}

echo "Database update completed\n";
?>