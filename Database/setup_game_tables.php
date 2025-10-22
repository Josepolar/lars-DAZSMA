<?php
include '../Database/database.php';

try {
    // Create activity_types table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_types (
        type_id INT PRIMARY KEY AUTO_INCREMENT,
        type_name VARCHAR(50) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create activity_game_content table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_game_content (
        content_id INT PRIMARY KEY AUTO_INCREMENT,
        activity_id INT NOT NULL,
        game_type VARCHAR(50) NOT NULL,
        content JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (activity_id) REFERENCES activities(activity_id) ON DELETE CASCADE
    )");

    // Insert default activity types
    $pdo->exec("INSERT INTO activity_types (type_name, description) VALUES 
        ('flashcards', 'Interactive flashcard learning activities'),
        ('crossword', 'Educational crossword puzzle games'),
        ('speed_typing', 'Typing practice and speed improvement games')
        ON DUPLICATE KEY UPDATE type_name=type_name");

    // Insert sample game activity if it doesn't exist
    $stmt = $pdo->prepare("SELECT activity_id FROM activities WHERE title = 'Philippine Geography Crossword' LIMIT 1");
    $stmt->execute();
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        // First, ensure we have a subject
        $pdo->exec("INSERT INTO subjects (subject_name, grade_level, description) 
                   VALUES ('Social Studies', 7, 'Philippine Geography and History')
                   ON DUPLICATE KEY UPDATE subject_name=subject_name");
        
        // Get the subject_id
        $stmt = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_name = 'Social Studies' LIMIT 1");
        $stmt->execute();
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Create the activity
        $stmt = $pdo->prepare("INSERT INTO activities (subject_id, title, description, total_points, status) 
                             VALUES (?, 'Philippine Geography Crossword', 'Learn Philippine geography through a fun crossword puzzle', 100, 'active')");
        $stmt->execute([$subject['subject_id']]);
        $activityId = $pdo->lastInsertId();
    } else {
        $activityId = $activity['activity_id'];
    }

    // Insert sample crossword content
    $crosswordContent = json_encode([
        'words' => [
            ['word' => 'MANILA', 'clue' => 'Capital city of the Philippines', 'startRow' => 0, 'startCol' => 0, 'direction' => 'across'],
            ['word' => 'MINDANAO', 'clue' => 'Second largest island in the Philippines', 'startRow' => 1, 'startCol' => 0, 'direction' => 'across'],
            ['word' => 'LUZON', 'clue' => 'Largest island in the Philippines', 'startRow' => 2, 'startCol' => 0, 'direction' => 'across']
        ],
        'gridSize' => '8x8'
    ]);

    $stmt = $pdo->prepare("INSERT INTO activity_game_content (activity_id, game_type, content) 
                          VALUES (?, 'crossword', ?)
                          ON DUPLICATE KEY UPDATE content = VALUES(content)");
    $stmt->execute([$activityId, $crosswordContent]);

    echo "Success! Tables and sample content created.<br>";
    echo "Created tables: activity_types, activity_game_content<br>";
    echo "Added sample crossword puzzle with ID: $activityId<br>";
    echo "Try accessing the game now!";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>