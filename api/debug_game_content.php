<?php
include '../Database/database.php';

try {
    // Check if activity_game_content table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'activity_game_content'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Check for actual game content
        $stmt = $pdo->query("
            SELECT 
                a.activity_id,
                a.title,
                agc.game_type,
                agc.content
            FROM activities a
            JOIN activity_game_content agc ON a.activity_id = agc.activity_id
            WHERE a.status = 'active'
        ");
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Game Activities Content Check</h2>";
        foreach ($results as $row) {
            echo "<hr>";
            echo "<strong>Activity ID:</strong> " . $row['activity_id'] . "<br>";
            echo "<strong>Title:</strong> " . $row['title'] . "<br>";
            echo "<strong>Game Type:</strong> " . $row['game_type'] . "<br>";
            echo "<strong>Content:</strong> <pre>" . htmlspecialchars($row['content']) . "</pre><br>";
        }
        
        if (empty($results)) {
            echo "<p>No active game activities found in the database.</p>";
        }
    } else {
        echo "activity_game_content table does not exist!";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>