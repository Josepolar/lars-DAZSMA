<?php
session_start();
require_once('../Database/database.php');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Not authenticated']));
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => '', 'data' => null];

switch ($action) {
    case 'details':
        $activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
        
        if ($activity_id) {
            // Get activity details
            $stmt = $pdo->prepare("
                SELECT 
                    a.*,
                    s.subject_name,
                    CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
                    ap.completion_status,
                    ap.score,
                    ap.completion_time
                FROM activities a
                LEFT JOIN subjects s ON a.subject_id = s.subject_id
                LEFT JOIN users u ON a.teacher_id = u.user_id
                LEFT JOIN activity_progress ap ON a.activity_id = ap.activity_id 
                    AND ap.user_id = ?
                WHERE a.activity_id = ?
            ");
            
            $stmt->execute([$_SESSION['user_id'], $activity_id]);
            $activity = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($activity) {
                // Handle game type activities differently
                if (in_array($activity['activity_type'], ['crossword', 'flashcards', 'speed_typing'])) {
                    // Decode content and settings
                    $activity['content_data'] = $activity['content_data'] ?? '{}';
                    $activity['settings'] = $activity['settings'] ?? '{}';
                    
                    // Provide default content structure if empty
                    $content = json_decode($activity['content_data'], true) ?? [];
                    if (empty($content)) {
                        switch ($activity['activity_type']) {
                            case 'flashcards':
                                $content = ['cards' => []];
                                break;
                            case 'crossword':
                                $content = ['words' => [], 'clues' => [], 'gridSize' => '10x10'];
                                break;
                            case 'speed_typing':
                                $content = ['text' => ''];
                                break;
                        }
                        $activity['content_data'] = json_encode($content);
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'activity' => $activity,
                    'questions' => [], // Empty for game activities
                    'answers' => []    // Empty for game activities
                ]);
            } else {
                echo json_encode(['error' => 'Activity not found']);
            }
        } else {
            echo json_encode(['error' => 'Invalid activity ID']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}