<?php
// Import Game System Database Schema
echo "<!DOCTYPE html>
<html>
<head>
    <title>Import Game System Database</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #26890D;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #26890D;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: bold;
        }
        .btn:hover {
            background: #1e6a0a;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🎮 Game System Database Import</h1>";

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lars";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✓ Connected to database: <strong>$dbname</strong></div>";
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/DB/game_system.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    echo "<div class='info'>📄 Reading SQL file: <strong>DB/game_system.sql</strong></div>";
    
    // Split by semicolon to execute each statement separately
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errors = [];
    
    echo "<div class='info'>⚙️ Executing SQL statements...</div>";
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage() . "<br><pre>" . htmlspecialchars($statement) . "</pre>";
        }
    }
    
    if (count($errors) === 0) {
        echo "<div class='success'>";
        echo "<h2>✓ Import Successful!</h2>";
        echo "<p><strong>$successCount</strong> SQL statements executed successfully.</p>";
        echo "<h3>Tables Created:</h3>";
        echo "<ul>";
        echo "<li>✓ game_activities</li>";
        echo "<li>✓ game_questions</li>";
        echo "<li>✓ game_options</li>";
        echo "<li>✓ game_responses</li>";
        echo "<li>✓ game_sessions</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li>Login as a <strong>Teacher</strong></li>";
        echo "<li>Click on <strong>Game Activities</strong> in the dashboard</li>";
        echo "<li>Create your first game and add questions</li>";
        echo "<li>Students can then play the game from their dashboard!</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<a href='teachers/teacher-login.php' class='btn'>Go to Teacher Login</a>";
        echo "<a href='students/stud-login.php' class='btn' style='background: #007bff; margin-left: 10px;'>Go to Student Login</a>";
    } else {
        echo "<div class='error'>";
        echo "<h2>⚠ Import Completed with Errors</h2>";
        echo "<p><strong>$successCount</strong> statements succeeded.</p>";
        echo "<p><strong>" . count($errors) . "</strong> errors encountered:</p>";
        foreach ($errors as $error) {
            echo "<div style='margin: 10px 0;'>$error</div>";
        }
        echo "</div>";
    }
    
    // Verify tables were created
    echo "<div class='info'>";
    echo "<h3>Verification - Tables in Database:</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'game_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>✓ $table</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: #dc3545;'>⚠ No game tables found. Import may have failed.</p>";
    }
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>Database Connection Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>
