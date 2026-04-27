<?php
// Database connection for both local XAMPP and cloud/serverless deployments.
// Priority:
// 1) Supabase/Postgres via DATABASE_URL or PG* env vars (Vercel/production)
// 2) Generic MySQL via DB_* env vars
// 2) Local XAMPP defaults

$database_url = getenv('DATABASE_URL') ?: '';
$pg_host = getenv('PGHOST') ?: '';
$pg_port = getenv('PGPORT') ?: '5432';
$pg_name = getenv('PGDATABASE') ?: '';
$pg_user = getenv('PGUSER') ?: '';
$pg_pass = getenv('PGPASSWORD') ?: '';
$pg_sslmode = getenv('PGSSLMODE') ?: 'require';

$db_host = getenv('DB_HOST') ?: '';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: '';
$db_user = getenv('DB_USER') ?: '';
$db_pass = getenv('DB_PASS') ?: '';
$db_driver = strtolower(getenv('DB_DRIVER') ?: 'mysql');

$local_host = 'localhost';
$local_port = '3306';
$local_name = 'lars';
$local_user = 'root';
$local_pass = '';

function create_pdo($dsn, $user, $pass) {
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

function connect_mysql($host, $port, $db, $user, $pass) {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    return create_pdo($dsn, $user, $pass);
}

function connect_pgsql($host, $port, $db, $user, $pass, $sslmode = 'require') {
    $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode={$sslmode}";
    return create_pdo($dsn, $user, $pass);
}

function connect_from_database_url($database_url) {
    $parts = parse_url($database_url);
    if ($parts === false || !isset($parts['host']) || !isset($parts['path'])) {
        throw new PDOException('Invalid DATABASE_URL format.');
    }

    $host = $parts['host'];
    $port = isset($parts['port']) ? (string)$parts['port'] : '5432';
    $db = ltrim($parts['path'], '/');
    $user = isset($parts['user']) ? urldecode($parts['user']) : '';
    $pass = isset($parts['pass']) ? urldecode($parts['pass']) : '';

    parse_str($parts['query'] ?? '', $queryParams);
    $sslmode = $queryParams['sslmode'] ?? 'require';

    return connect_pgsql($host, $port, $db, $user, $pass, $sslmode);
}

try {
    if ($database_url !== '') {
        $pdo = connect_from_database_url($database_url);
    } elseif ($pg_host !== '' && $pg_name !== '' && $pg_user !== '') {
        $pdo = connect_pgsql($pg_host, $pg_port, $pg_name, $pg_user, $pg_pass, $pg_sslmode);
    } elseif ($db_host !== '' && $db_name !== '' && $db_user !== '') {
        if ($db_driver === 'pgsql' || $db_driver === 'postgres' || $db_driver === 'postgresql') {
            $pdo = connect_pgsql($db_host, $db_port, $db_name, $db_user, $db_pass, $pg_sslmode);
        } else {
            $pdo = connect_mysql($db_host, $db_port, $db_name, $db_user, $db_pass);
        }
    } else {
        $pdo = connect_mysql($local_host, $local_port, $local_name, $local_user, $local_pass);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please check database settings.');
}
?>