<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'lars_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Get admin account details
$result = $conn->query("SELECT username, email, password FROM users WHERE role_id = 1");
if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "Admin Account Details:\n";
    echo "Username: " . $admin['username'] . "\n";
    echo "Email: " . $admin['email'] . "\n";
    echo "Password: " . $admin['password'] . "\n";
} else {
    echo "No admin account found.";
}

$conn->close();
?>