<?php
// Database connection - supports both Hostinger live server and local XAMPP

// Live server credentials
$live_servername = "localhost";
$live_username = "u456758764_lars";
$live_password = "Lars@DB00123";
$live_dbname = "u456758764_lars";

// Local XAMPP credentials
$local_servername = "localhost";
$local_username = "root";
$local_password = "";
$local_dbname = "lars";

try {
    // Try live server first
    $pdo = new PDO("mysql:host=$live_servername;dbname=$live_dbname", $live_username, $live_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // If live fails, try local
    try {
        $pdo = new PDO("mysql:host=$local_servername;dbname=$local_dbname", $local_username, $local_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e2) {
        die("Database connection failed for both live and local servers: " . $e2->getMessage());
    }
}
?>