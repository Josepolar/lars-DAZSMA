<?php
session_start();
require_once('../Database/database.php');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'data' => null];

$action = $_GET['action'] ?? '';
$activityId = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;

function getActivityDetails($activityId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT 
            a.activity_id,
            a.title,
            a.description,
            a.activity_type,
            a.time_limit,
            a.total_points,
            a.due_date,
            a.settings,
            a.content_data,
            s.subject_name
        FROM activities a
        LEFT JOIN subjects s ON a.subject_id = s.subject_id
        WHERE a.activity_id = ?
    ");
    
    $stmt->bind_param("i", $activityId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

switch ($action) {
    case 'get_activity':
        if ($activityId) {
            $activity = getActivityDetails($activityId);
            if ($activity) {
                // Convert game-specific fields to proper format
                $activity['settings'] = json_decode($activity['settings'], true);
                $activity['content_data'] = json_decode($activity['content_data'], true);
                
                // Provide default structures if needed
                if (!$activity['settings']) {
                    $activity['settings'] = [
                        'timeLimit' => $activity['time_limit'] ?? 10,
                        'totalPoints' => $activity['total_points'] ?? 100,
                        'allowRetry' => true
                    ];
                }
                
                if (!$activity['content_data']) {
                    switch ($activity['activity_type']) {
                        case 'crossword':
                            $activity['content_data'] = [
                                'words' => [],
                                'clues' => [],
                                'gridSize' => '10x10'
                            ];
                            break;
                        case 'flashcards':
                            $activity['content_data'] = [
                                'cards' => []
                            ];
                            break;
                        case 'speed_typing':
                            $activity['content_data'] = [
                                'text' => ''
                            ];
                            break;
                    }
                }
                
                $response['success'] = true;
                $response['data'] = $activity;
            } else {
                $response['message'] = 'Activity not found';
            }
        } else {
            $response['message'] = 'Invalid activity ID';
        }
        break;
        
    default:
        $response['message'] = 'Invalid action';
        break;
}

echo json_encode($response);