<?php
require_once __DIR__ . '/database.php';

// Check database connection
if (!$conn) {
    die("Database connection failed. Please check your database.php configuration.");
}

// Create activity_game_content table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS activity_game_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    game_content JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (activity_id) REFERENCES activities(activity_id)
) ENGINE=InnoDB;";

try {
    $conn->query($sql);
    echo "Game content table created successfully\n";
} catch (Exception $e) {
    die("Error creating game content table: " . $e->getMessage());
}

// Function to add game content
function addGameContent($activityId, $content) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO activity_game_content (activity_id, game_content) VALUES (?, ?)");
    $jsonContent = json_encode($content);
    $stmt->bind_param("is", $activityId, $jsonContent);
    
    try {
        $stmt->execute();
        echo "Added game content for activity ID: $activityId\n";
    } catch (Exception $e) {
        echo "Error adding game content for activity ID $activityId: " . $e->getMessage() . "\n";
    }
}

// Sample content for different game types
$flashcardsContent = [
    'cards' => [
        ['front' => 'What is the capital of Philippines?', 'back' => 'Manila'],
        ['front' => 'National language of Philippines', 'back' => 'Filipino'],
        ['front' => 'What is the largest island in the Philippines?', 'back' => 'Luzon'],
        ['front' => 'National hero of the Philippines', 'back' => 'Jose Rizal'],
        ['front' => 'What is the Philippine national flower?', 'back' => 'Sampaguita']
    ]
];

$crosswordContent = [
    'gridSize' => '10x10',
    'words' => [
        ['word' => 'MANILA', 'startRow' => 0, 'startCol' => 0, 'direction' => 'across'],
        ['word' => 'RIZAL', 'startRow' => 1, 'startCol' => 2, 'direction' => 'down']
    ],
    'clues' => [
        'across' => [
            '1. Capital city of the Philippines'
        ],
        'down' => [
            '1. Philippine national hero'
        ]
    ]
];

$speedTypingContent = [
    'texts' => [
        'My country is called the Philippines. It is made up of more than 7,000 islands.',
        'Filipino people are known for their hospitality and warm smiles.',
        'The Philippine flag has the colors red, white, blue, and yellow.'
    ],
    'timeLimit' => 60,
    'minWPM' => 30
];

// Add sample game content
// Note: Replace these IDs with actual activity IDs from your database
$activities = [
    ['id' => $_GET['activity_id'], 'type' => $_GET['type'], 'content' => null]
];

foreach ($activities as $activity) {
    switch ($activity['type']) {
        case 'flashcards':
            $activity['content'] = $flashcardsContent;
            break;
        case 'crossword':
            $activity['content'] = $crosswordContent;
            break;
        case 'speed_typing':
            $activity['content'] = $speedTypingContent;
            break;
    }
    
    if ($activity['content']) {
        addGameContent($activity['id'], $activity['content']);
    }
}

echo "Setup complete!";
?>