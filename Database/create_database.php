<?php
// Include database connection
include 'database.php';

try {
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        role_id INT NOT NULL,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        grade_level INT,
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Create subjects table
    $pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
        subject_id INT PRIMARY KEY AUTO_INCREMENT,
        subject_name VARCHAR(100) NOT NULL,
        grade_level INT NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create activities table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activities (
        activity_id INT PRIMARY KEY AUTO_INCREMENT,
        subject_id INT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        due_date DATETIME,
        total_points INT DEFAULT 0,
        status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
    )");

    // Create student_submissions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_submissions (
        submission_id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT,
        activity_id INT,
        submission_status ENUM('pending', 'submitted', 'graded') DEFAULT 'pending',
        total_score INT DEFAULT 0,
        percentage DECIMAL(5,2),
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(user_id),
        FOREIGN KEY (activity_id) REFERENCES activities(activity_id)
    )");

    // Insert a test admin user
    $adminPassword = password_hash("admin123", PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (role_id, username, password, first_name, last_name, email) 
                VALUES (1, 'admin', '$adminPassword', 'System', 'Administrator', 'admin@example.com')
                ON DUPLICATE KEY UPDATE username=username");

    echo "Database tables created successfully!";
    
} catch(PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>