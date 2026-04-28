<?php
// Database connection for both local XAMPP and cloud/serverless deployments.
// Priority:
// 1) Environment variables (Vercel/production)
// 2) Local XAMPP defaults

$env_host = getenv('DB_HOST') ?: '';
$env_port = getenv('DB_PORT') ?: '3306';
$env_name = getenv('DB_NAME') ?: '';
$env_user = getenv('DB_USER') ?: '';
$env_pass = getenv('DB_PASS') ?: '';

$local_host = 'localhost';
$local_port = '3306';
$local_name = 'lars';
$local_user = 'root';
$local_pass = '';

function connect_pdo($host, $port, $db, $user, $pass) {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    return $pdo;
}

try {
    if ($env_host !== '' && $env_name !== '' && $env_user !== '') {
        $pdo = connect_pdo($env_host, $env_port, $env_name, $env_user, $env_pass);
    } else {
        $pdo = connect_pdo($local_host, $local_port, $local_name, $local_user, $local_pass);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please check database settings.');
}
?>