<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lars";

try {
    // Determine if we're on localhost
    $is_localhost = isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost';
    
    if ($is_localhost) {
        // Local XAMPP connection
        $pdo = new PDO(
            "mysql:host=$local_servername;dbname=$local_dbname;charset=utf8mb4",
            $local_username,
            $local_password
        );
        $conn = new mysqli($local_servername, $local_username, $local_password, $local_dbname);
    } else {
        // Live server connection
        $pdo = new PDO(
            "mysql:host=$live_servername;dbname=$live_dbname;charset=utf8mb4",
            $live_username,
            $live_password
        );
        $conn = new mysqli($live_servername, $live_username, $live_password, $live_dbname);
    }
    
    // Configure PDO error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Check mysqli connection
    if ($conn->connect_error) {
        throw new Exception("MySQLi connection failed: " . $conn->connect_error);
    }
    
} catch (PDOException $e) {
    error_log("PDO Connection Error: " . $e->getMessage());
    die("Database connection error. Please try again later.");
    
} catch (Exception $e) {
    error_log("General Database Error: " . $e->getMessage());
    die("Application error. Please try again later.");
}
    }
}
?>