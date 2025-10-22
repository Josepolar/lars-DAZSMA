<?php
// Database creation script
$local_servername = "localhost";
$local_username = "root";
$local_password = "";

try {
    // Create connection without database selected
    $conn = new mysqli($local_servername, $local_username, $local_password);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS larss";
    if ($conn->query($sql) === TRUE) {
        echo "Database 'larss' created successfully or already exists<br>";
    } else {
        die("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db("larss");
    
    // Create activities table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS activities (
        activity_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        subject_id INT NOT NULL,
        activity_type VARCHAR(50) NOT NULL,
        status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
        time_limit INT,
        total_points INT DEFAULT 100,
        due_date DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'activities' created successfully or already exists<br>";
    } else {
        echo "Error creating activities table: " . $conn->error . "<br>";
    }

    // Create subjects table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS subjects (
        subject_id INT AUTO_INCREMENT PRIMARY KEY,
        subject_name VARCHAR(255) NOT NULL,
        subject_code VARCHAR(50) NOT NULL,
        status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'subjects' created successfully or already exists<br>";
    } else {
        echo "Error creating subjects table: " . $conn->error . "<br>";
    }

    // Create student_subjects table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS student_subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_id INT NOT NULL,
        status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'student_subjects' created successfully or already exists<br>";
    } else {
        echo "Error creating student_subjects table: " . $conn->error . "<br>";
    }

    // Create activity_game_content table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS activity_game_content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        activity_id INT NOT NULL,
        game_content JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (activity_id) REFERENCES activities(activity_id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'activity_game_content' created successfully or already exists<br>";
    } else {
        echo "Error creating activity_game_content table: " . $conn->error . "<br>";
    }

    echo "<br>Database setup completed successfully!";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>