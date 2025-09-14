<?php
/**
 * LARSS Installation Script
 * Run this file to set up the database and required tables
 */

// Display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'lars_db';

// Function to run SQL from file
function run_sql_file($filename, $mysqli) {
    $queries = file_get_contents($filename);
    $mysqli->multi_query($queries);
    
    // Process all results to clear the queue
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    
    // Check for errors
    if ($mysqli->error) {
        return false;
    }
    
    return true;
}

// Output as HTML
echo "<!DOCTYPE html>
<html>
<head>
    <title>LARSS Installation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .step { margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-left: 5px solid #ddd; }
        .step.success { border-left: 5px solid green; }
        .step.error { border-left: 5px solid red; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>LARSS Installation</h1>";

// Step 1: Connect to MySQL server
echo "<div class='step'><h3>Step 1: Connecting to MySQL server</h3>";
try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass);
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    echo "<p class='success'>✓ Connected to MySQL server successfully.</p></div>";
} catch (Exception $e) {
    echo "<p class='error'>✗ " . $e->getMessage() . "</p>";
    echo "<p>Please make sure your MySQL server is running and credentials are correct.</p></div>";
    exit("</div></body></html>");
}

// Step 2: Execute SQL file
echo "<div class='step'><h3>Step 2: Setting up database and tables</h3>";
try {
    if (run_sql_file('database.sql', $mysqli)) {
        echo "<p class='success'>✓ Database and tables created successfully.</p></div>";
    } else {
        throw new Exception("Error executing SQL: " . $mysqli->error);
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ " . $e->getMessage() . "</p></div>";
    exit("</div></body></html>");
}

// Step 3: Verify setup
echo "<div class='step'><h3>Step 3: Verifying installation</h3>";
try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_error) {
        throw new Exception("Connection to database failed: " . $mysqli->connect_error);
    }
    
    // Check tables
    $tables = ['roles', 'users', 'subjects', 'teacher_subjects', 'user_logs'];
    $all_tables_exist = true;
    
    foreach ($tables as $table) {
        $result = $mysqli->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            $all_tables_exist = false;
            echo "<p class='error'>✗ Table '$table' does not exist.</p>";
        }
    }
    
    if ($all_tables_exist) {
        echo "<p class='success'>✓ All required tables exist.</p>";
    }
    
    // Check admin user
    $result = $mysqli->query("SELECT * FROM users WHERE username='admin' AND role_id=1");
    if ($result->num_rows > 0) {
        echo "<p class='success'>✓ Admin user exists.</p>";
    } else {
        echo "<p class='error'>✗ Admin user does not exist.</p>";
    }
    
    echo "</div>";
} catch (Exception $e) {
    echo "<p class='error'>✗ " . $e->getMessage() . "</p></div>";
}

// Installation complete
echo "<div class='step success'>
    <h3>Installation Complete</h3>
    <p>LARSS has been successfully installed. You can now:</p>
    <ul>
        <li>Log in as admin (username: admin, password: admin123)</li>
        <li>Start adding users, subjects, and managing the system</li>
        <li>For security reasons, please delete this file after installation</li>
    </ul>
    <p><a href='index.php'>Go to LARSS Home Page</a></p>
</div>";

echo "</div></body></html>";
?>