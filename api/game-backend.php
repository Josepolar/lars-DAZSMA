<?php
session_start();
require_once('../Database/database.php');

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => '', 'data' => null];

function getGameActivity($activityId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM activities WHERE activity_id = ? AND activity_type IN ('crossword', 'flashcards', 'speed_typing')");
    $stmt->bind_param("i", $activityId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getGameData($activityId) {
    global $conn;
    $stmt = $conn->prepare("SELECT activity_type as game_type, settings as game_settings, content_data as game_content FROM activities WHERE activity_id = ?");
    $stmt->bind_param("i", $activityId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $result['game_settings'] = json_decode($result['game_settings'], true);
        $result['game_content'] = json_decode($result['game_content'], true);
        
        // Set default content if null
        if ($result['game_content'] === null) {
            switch ($result['game_type']) {
                case 'flashcards':
                    $result['game_content'] = ['cards' => []];
                    break;
                case 'crossword':
                    $result['game_content'] = ['words' => [], 'clues' => [], 'gridSize' => '10x10'];
                    break;
                case 'speed_typing':
                    $result['game_content'] = ['text' => ''];
                    break;
            }
        }
        
        // Set default settings if null
        if ($result['game_settings'] === null) {
            $result['game_settings'] = [
                'timeLimit' => 10,
                'attempts' => 3,
                'showFeedback' => true
            ];
        }
    }
    
    return $result;
}

function saveGameProgress($userId, $activityId, $progress) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO activity_progress (user_id, activity_id, progress_data, completion_status) 
                           VALUES (?, ?, ?, 'in_progress') 
                           ON DUPLICATE KEY UPDATE progress_data = ?, last_updated = CURRENT_TIMESTAMP");
    $progressJson = json_encode($progress);
    $stmt->bind_param("iiss", $userId, $activityId, $progressJson, $progressJson);
    return $stmt->execute();
}

function completeGame($userId, $activityId, $results) {
    global $conn;
    $stmt = $conn->prepare("UPDATE activity_progress 
                           SET completion_status = 'completed', 
                               score = ?, 
                               completion_time = CURRENT_TIMESTAMP,
                               results_data = ?
                           WHERE user_id = ? AND activity_id = ?");
    
    $score = $results['score'] ?? 0;
    $resultsJson = json_encode($results);
    $stmt->bind_param("isii", $score, $resultsJson, $userId, $activityId);
    return $stmt->execute();
}

switch ($action) {
    case 'get_game_data':
        $activityId = $_GET['activity_id'] ?? 0;
        $gameData = getGameData($activityId);
        if ($gameData) {
            $response['success'] = true;
            $response['data'] = $gameData;
        } else {
            $response['message'] = 'Game data not found';
        }
        break;

    case 'save_progress':
        if (isset($_SESSION['user_id'])) {
            $activityId = $_POST['activity_id'] ?? 0;
            $progress = $_POST['progress'] ?? [];
            
            if (saveGameProgress($_SESSION['user_id'], $activityId, $progress)) {
                $response['success'] = true;
                $response['message'] = 'Progress saved successfully';
            } else {
                $response['message'] = 'Error saving progress';
            }
        } else {
            $response['message'] = 'User not authenticated';
        }
        break;

    case 'complete_game':
        if (isset($_SESSION['user_id'])) {
            $activityId = $_POST['activity_id'] ?? 0;
            $results = $_POST['results'] ?? [];
            
            if (completeGame($_SESSION['user_id'], $activityId, $results)) {
                $response['success'] = true;
                $response['message'] = 'Game completed successfully';
            } else {
                $response['message'] = 'Error completing game';
            }
        } else {
            $response['message'] = 'User not authenticated';
        }
        break;

    default:
        $response['message'] = 'Invalid action';
        break;
}

echo json_encode($response);