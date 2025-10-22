<?php
include 'database.php';

try {
    // 1. Drop existing tables in correct order with foreign key checks disabled
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // Drop related tables that might have foreign key constraints
    $pdo->exec("DROP TABLE IF EXISTS student_submissions");
    $pdo->exec("DROP TABLE IF EXISTS activity_game_content");
    $pdo->exec("DROP TABLE IF EXISTS activity_types");
    $pdo->exec("DROP TABLE IF EXISTS activities");
    $pdo->exec("DROP TABLE IF EXISTS subjects");
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "✓ Cleaned up existing tables<br>";

    // 1. Create subjects table
    $pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
        subject_id INT PRIMARY KEY AUTO_INCREMENT,
        subject_name VARCHAR(100) NOT NULL,
        grade_level INT NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created subjects table<br>";

    // 2. Create activities table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activities (
        activity_id INT PRIMARY KEY AUTO_INCREMENT,
        subject_id INT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        total_points INT DEFAULT 100,
        status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
    )");
    echo "✓ Created activities table<br>";

    // 3. Create activity_types table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_types (
        type_id INT PRIMARY KEY AUTO_INCREMENT,
        type_name VARCHAR(50) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created activity_types table<br>";

    // 4. Create activity_game_content table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_game_content (
        content_id INT PRIMARY KEY AUTO_INCREMENT,
        activity_id INT NOT NULL,
        game_type VARCHAR(50) NOT NULL,
        content JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (activity_id) REFERENCES activities(activity_id) ON DELETE CASCADE
    )");
    echo "✓ Created activity_game_content table<br>";

    // 5.5 Create student_submissions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_submissions (
        submission_id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT,
        activity_id INT,
        submission_status ENUM('pending', 'submitted', 'graded') DEFAULT 'pending',
        total_score INT DEFAULT 0,
        percentage DECIMAL(5,2),
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (activity_id) REFERENCES activities(activity_id) ON DELETE CASCADE
    )");
    echo "✓ Created student_submissions table<br>";

    // 6. Insert default activity types
    $pdo->exec("INSERT INTO activity_types (type_name, description) VALUES 
        ('flashcards', 'Interactive flashcard learning activities'),
        ('crossword', 'Educational crossword puzzle games'),
        ('speed_typing', 'Typing practice and speed improvement games')
        ON DUPLICATE KEY UPDATE type_name=type_name");
    echo "✓ Added game types<br>";

    // 6. Add sample subjects
    $pdo->exec("INSERT INTO subjects (subject_name, grade_level, description) VALUES 
        ('Filipino', 7, 'Filipino Language and Literature'),
        ('Social Studies', 7, 'Philippine Geography and History'),
        ('Mathematics', 7, 'Basic Mathematics')
        ON DUPLICATE KEY UPDATE subject_name=subject_name");
    echo "✓ Added sample subjects<br>";

    // 7. Add sample activities
    $stmt = $pdo->prepare("INSERT INTO activities (subject_id, title, description, total_points, status) 
        SELECT subject_id, 'Filipino Vocabulary Flashcards', 'Practice Filipino vocabulary with interactive flashcards', 100, 'active'
        FROM subjects WHERE subject_name = 'Filipino'
        ON DUPLICATE KEY UPDATE title=title");
    $stmt->execute();

    $stmt = $pdo->prepare("INSERT INTO activities (subject_id, title, description, total_points, status) 
        SELECT subject_id, 'Philippine Geography Crossword', 'Learn Philippine geography through a fun crossword puzzle', 100, 'active'
        FROM subjects WHERE subject_name = 'Social Studies'
        ON DUPLICATE KEY UPDATE title=title");
    $stmt->execute();

    $stmt = $pdo->prepare("INSERT INTO activities (subject_id, title, description, total_points, status) 
        SELECT subject_id, 'Filipino Typing Practice', 'Improve your Filipino typing speed and accuracy', 100, 'active'
        FROM subjects WHERE subject_name = 'Filipino'
        ON DUPLICATE KEY UPDATE title=title");
    $stmt->execute();
    echo "✓ Added sample activities<br>";

    // 8. Add sample game content
    // Flashcards content
    $flashcardContent = json_encode([
        'cards' => [
            ['front' => 'Bahay', 'back' => 'House'],
            ['front' => 'Araw', 'back' => 'Sun'],
            ['front' => 'Tubig', 'back' => 'Water'],
            ['front' => 'Pagkain', 'back' => 'Food'],
            ['front' => 'Paaralan', 'back' => 'School']
        ]
    ]);

    // Crossword content
    $crosswordContent = json_encode([
        'words' => [
            ['word' => 'MANILA', 'clue' => 'Capital city of the Philippines', 'startRow' => 0, 'startCol' => 0, 'direction' => 'across'],
            ['word' => 'MINDANAO', 'clue' => 'Second largest island in the Philippines', 'startRow' => 1, 'startCol' => 0, 'direction' => 'across'],
            ['word' => 'LUZON', 'clue' => 'Largest island in the Philippines', 'startRow' => 2, 'startCol' => 0, 'direction' => 'across'],
            ['word' => 'VISAYAS', 'clue' => 'Central island group of the Philippines', 'startRow' => 3, 'startCol' => 0, 'direction' => 'across']
        ],
        'gridSize' => '8x8'
    ]);

    // Typing practice content
    $typingContent = json_encode([
        'texts' => [
            'Ang bawat isa ay may karapatan sa edukasyon.',
            'Mahalaga ang pag-aaral para sa kinabukasan.',
            'Masayang matuto ng bagong wika at kultura.'
        ]
    ]);

    // Insert game content
    $stmt = $pdo->prepare("INSERT INTO activity_game_content (activity_id, game_type, content)
        SELECT activity_id, 'flashcards', ?
        FROM activities WHERE title = 'Filipino Vocabulary Flashcards'");
    $stmt->execute([$flashcardContent]);

    $stmt = $pdo->prepare("INSERT INTO activity_game_content (activity_id, game_type, content)
        SELECT activity_id, 'crossword', ?
        FROM activities WHERE title = 'Philippine Geography Crossword'");
    $stmt->execute([$crosswordContent]);

    $stmt = $pdo->prepare("INSERT INTO activity_game_content (activity_id, game_type, content)
        SELECT activity_id, 'speed_typing', ?
        FROM activities WHERE title = 'Filipino Typing Practice'");
    $stmt->execute([$typingContent]);
    echo "✓ Added sample game content<br>";

    echo "<br>✨ Setup completed successfully!<br>";
    echo "You can now access the games through: <a href='../students/game-activities.php'>Game Activities</a>";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>