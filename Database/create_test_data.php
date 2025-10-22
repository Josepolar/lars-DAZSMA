<?php
include 'database.php';

try {
    // Create user_logs table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_logs (
        log_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        action VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )");

    // Create a test student account
    $studentPassword = password_hash("student123", PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (role_id, username, password, first_name, last_name, email, grade_level) 
                          VALUES (4, 'student1', ?, 'John', 'Doe', 'student1@example.com', 7)
                          ON DUPLICATE KEY UPDATE username=username");
    $stmt->execute([$studentPassword]);

    echo "Test student account created successfully!<br>";
    echo "Username: student1<br>";
    echo "Password: student123<br>";
    echo "Role: Student (Grade 7)<br>";
    
} catch(PDOException $e) {
    echo "Error creating test data: " . $e->getMessage();
}
?>