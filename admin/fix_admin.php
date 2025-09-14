<?php
try {
    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'lars_db');
    if($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
    
    // Admin account details with proper email format
    $username = 'adminuser';
    $password = 'securepass123';
    $email = 'adminuser@lars.edu.ph'; // Proper email with @ symbol
    $firstName = 'New';
    $lastName = 'Admin';
    $roleId = 1; // Admin role
    
    // Check if username already exists
    $checkUsername = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $checkUsername->bind_param('s', $username);
    $checkUsername->execute();
    $usernameResult = $checkUsername->get_result();
    
    if ($usernameResult->num_rows > 0) {
        // If username exists, try to update the email
        echo "Username already exists. Attempting to update email address to valid format." . PHP_EOL;
        $updateStmt = $conn->prepare("UPDATE users SET email = ? WHERE username = ?");
        $updateStmt->bind_param('ss', $email, $username);
        
        if ($updateStmt->execute()) {
            echo "Admin email updated successfully!" . PHP_EOL;
            echo "Username: $username" . PHP_EOL;
            echo "Password: $password" . PHP_EOL;
            echo "Email: $email" . PHP_EOL;
        } else {
            echo "Error updating admin email: " . $updateStmt->error . PHP_EOL;
        }
        $updateStmt->close();
    } else {
        // Insert the new admin with valid email
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role_id, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $username, $password, $email, $roleId, $firstName, $lastName);
        
        if ($stmt->execute()) {
            echo "Admin account created successfully!" . PHP_EOL;
            echo "Username: $username" . PHP_EOL;
            echo "Password: $password" . PHP_EOL;
            echo "Email: $email" . PHP_EOL;
        } else {
            echo "Error creating admin account: " . $stmt->error . PHP_EOL;
        }
        
        $stmt->close();
    }
    
    // Additional step: Create a new admin with a different username if needed
    $username2 = 'admin2';
    $email2 = 'admin2@lars.edu.ph';
    
    $checkUsername2 = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $checkUsername2->bind_param('s', $username2);
    $checkUsername2->execute();
    $usernameResult2 = $checkUsername2->get_result();
    
    if ($usernameResult2->num_rows == 0) {
        $stmt2 = $conn->prepare("INSERT INTO users (username, password, email, role_id, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param('ssssss', $username2, $password, $email2, $roleId, $firstName, $lastName);
        
        if ($stmt2->execute()) {
            echo "\nAdditional admin account created!" . PHP_EOL;
            echo "Username: $username2" . PHP_EOL;
            echo "Password: $password" . PHP_EOL;
            echo "Email: $email2" . PHP_EOL;
        } else {
            echo "\nError creating additional admin account: " . $stmt2->error . PHP_EOL;
        }
        
        $stmt2->close();
    }
    
    $conn->close();
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>