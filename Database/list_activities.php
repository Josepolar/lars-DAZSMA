<?php
require_once 'database.php';

// Check if activity_id is provided
if (isset($_GET['setup']) && $_GET['setup'] == 'true') {
    // Insert a sample activity if none exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activities");
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    if ($count == 0) {
        // First, ensure we have a subject
        $stmt = $conn->prepare("SELECT subject_id FROM subjects LIMIT 1");
        $stmt->execute();
        $subject = $stmt->get_result()->fetch_assoc();
        
        if (!$subject) {
            // Create a sample subject
            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code) VALUES ('Filipino', 'FIL7')");
            $stmt->execute();
            $subject_id = $conn->insert_id;
        } else {
            $subject_id = $subject['subject_id'];
        }

        // Insert sample activities
        $activities = [
            [
                'title' => 'Filipino Vocabulary Flashcards',
                'description' => 'Learn common Filipino words and their meanings',
                'type' => 'flashcards',
                'time_limit' => 10
            ],
            [
                'title' => 'Philippine Geography Crossword',
                'description' => 'Test your knowledge of Philippine geography',
                'type' => 'crossword',
                'time_limit' => 15
            ],
            [
                'title' => 'Filipino Typing Practice',
                'description' => 'Practice typing Filipino sentences',
                'type' => 'speed_typing',
                'time_limit' => 12
            ]
        ];

        foreach ($activities as $activity) {
            $stmt = $conn->prepare("
                INSERT INTO activities (
                    title, description, subject_id, activity_type, 
                    time_limit, total_points, status
                ) VALUES (?, ?, ?, ?, ?, 100, 'active')
            ");
            
            $stmt->bind_param(
                "ssisi", 
                $activity['title'], 
                $activity['description'], 
                $subject_id, 
                $activity['type'], 
                $activity['time_limit']
            );
            
            $stmt->execute();
            
            echo "Created activity: " . $activity['title'] . " (ID: " . $conn->insert_id . ")<br>";
        }
    }
}

// List all activities
$stmt = $conn->prepare("
    SELECT a.*, s.subject_name, 
           CASE 
               WHEN agc.id IS NOT NULL THEN 'Yes'
               ELSE 'No'
           END as has_game_content
    FROM activities a
    LEFT JOIN subjects s ON a.subject_id = s.subject_id
    LEFT JOIN activity_game_content agc ON a.activity_id = agc.activity_id
    WHERE a.activity_type IN ('flashcards', 'crossword', 'speed_typing')
    ORDER BY a.activity_id DESC
");

$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Available Game Activities</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Subject</th><th>Has Game Content</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['activity_id'] . "</td>";
    echo "<td>" . $row['title'] . "</td>";
    echo "<td>" . $row['activity_type'] . "</td>";
    echo "<td>" . $row['subject_name'] . "</td>";
    echo "<td>" . $row['has_game_content'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><br>";
echo "To add game content to an activity, use: <br>";
echo "http://localhost/larss/Database/setup_game_content.php?activity_id=ACTIVITY_ID&type=GAME_TYPE<br><br>";
echo "Where:<br>";
echo "- ACTIVITY_ID is one of the IDs from the table above<br>";
echo "- GAME_TYPE matches the activity type (flashcards, crossword, or speed_typing)<br><br>";

if ($result->num_rows == 0) {
    echo "<a href='?setup=true'>Click here to create sample activities</a>";
}
?>