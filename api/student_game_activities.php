<?php
session_start();

// Turn off all error output to prevent HTML errors from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Database connection
require_once '../Database/database.php';

$student_id = $_SESSION['user_id'];
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;

if (!$activity_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing activity ID']);
    exit();
}

try {
    // First, verify this activity is available to the student
    $stmt = $conn->prepare("
        SELECT a.*, s.subject_name 
        FROM activities a 
        INNER JOIN subjects s ON a.subject_id = s.subject_id
        WHERE a.activity_id = ? 
        AND a.status = 'active'
        AND EXISTS (
            SELECT 1 
            FROM student_subjects ss 
            WHERE ss.subject_id = a.subject_id 
            AND ss.student_id = ?
        )
    ");
    
    $stmt->bind_param("ii", $activity_id, $student_id);
    $stmt->execute();
    $activity = $stmt->get_result()->fetch_assoc();

    if (!$activity) {
        throw new Exception('Activity not found or not available');
    }

    // Get the game content based on activity type
    $stmt = $conn->prepare("
        SELECT game_content 
        FROM activity_game_content 
        WHERE activity_id = ?
    ");
    
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result || !$result['game_content']) {
        // Initialize default game content if none exists
        $content = null;
        
        switch ($activity['activity_type']) {
            case 'flashcards':
                $content = [
                    'cards' => [
                        ['front' => 'What is the capital of Philippines?', 'back' => 'Manila'],
                        ['front' => 'National language of Philippines', 'back' => 'Filipino'],
                        ['front' => 'What is the largest island in the Philippines?', 'back' => 'Luzon'],
                        ['front' => 'National hero of the Philippines', 'back' => 'Jose Rizal'],
                        ['front' => 'What is the Philippine national flower?', 'back' => 'Sampaguita']
                    ]
                ];
                break;
                
            case 'crossword':
                $content = [
                    'gridSize' => '10x10',
                    'words' => [
                        ['word' => 'MANILA', 'startRow' => 0, 'startCol' => 0, 'direction' => 'across'],
                        ['word' => 'RIZAL', 'startRow' => 1, 'startCol' => 2, 'direction' => 'down']
                    ],
                    'clues' => [
                        'across' => ['1. Capital city of the Philippines'],
                        'down' => ['1. Philippine national hero']
                    ]
                ];
                break;
                
            case 'speed_typing':
                $content = [
                    'texts' => [
                        'My country is called the Philippines. It is made up of more than 7,000 islands.',
                        'Filipino people are known for their hospitality and warm smiles.',
                        'The Philippine flag has the colors red, white, blue, and yellow.'
                    ],
                    'timeLimit' => 60,
                    'minWPM' => 30
                ];
                break;
        }
        
        if ($content) {
            // Insert the default content
            $stmt = $conn->prepare("
                INSERT INTO activity_game_content (activity_id, game_content) 
                VALUES (?, ?)
            ");
            $jsonContent = json_encode($content);
            $stmt->bind_param("is", $activity_id, $jsonContent);
            $stmt->execute();
            
            $result['game_content'] = $jsonContent;
        } else {
            throw new Exception('No game content available for this activity');
        }
    }

    // Decode the game content
    $gameContent = json_decode($result['game_content'], true);
    if (!$gameContent) {
        throw new Exception('Invalid game content format');
    }

    // Add activity metadata
    $response = [
        'success' => true,
        'activity' => [
            'id' => $activity['activity_id'],
            'title' => $activity['title'],
            'type' => $activity['activity_type'],
            'subject' => $activity['subject_name'],
            'time_limit' => $activity['time_limit'],
            'total_points' => $activity['total_points'],
            'game_content' => $gameContent
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}