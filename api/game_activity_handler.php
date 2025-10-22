<?php
session_start();
require_once '../Database/database.php';

// Ensure user is logged in as a student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$action = $_GET['action'] ?? '';
$response = ['success' => false];

switch ($action) {
    case 'list':
        // Get all active game activities
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    a.activity_id,
                    a.title,
                    a.description,
                    a.total_points,
                    agc.game_type,
                    s.subject_name,
                    COALESCE(ss.submission_status, 'not_started') as status,
                    COALESCE(ss.total_score, 0) as score
                FROM activities a
                JOIN activity_game_content agc ON a.activity_id = agc.activity_id
                JOIN subjects s ON a.subject_id = s.subject_id
                LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id 
                    AND ss.student_id = ?
                WHERE a.status = 'active'
                ORDER BY a.activity_id
            ");
            
            $stmt->execute([$_SESSION['user_id']]);
            $response['success'] = true;
            $response['activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $response['error'] = $e->getMessage();
        }
        break;

    case 'get':
        $activityId = $_GET['id'] ?? 0;
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    a.*,
                    agc.game_type,
                    agc.content,
                    s.subject_name,
                    COALESCE(ss.submission_status, 'not_started') as status,
                    COALESCE(ss.total_score, 0) as score
                FROM activities a
                JOIN activity_game_content agc ON a.activity_id = agc.activity_id
                JOIN subjects s ON a.subject_id = s.subject_id
                LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id 
                    AND ss.student_id = ?
                WHERE a.activity_id = ? AND a.status = 'active'
            ");
            
            $stmt->execute([$_SESSION['user_id'], $activityId]);
            $activity = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($activity) {
                // Validate game content
                $content = $activity['content'];
                if (empty($content)) {
                    $response['error'] = 'Game content is empty';
                } else {
                    // Try to parse JSON content
                    try {
                        $decodedContent = json_decode($content, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new Exception('Invalid game content format');
                        }
                        $activity['content'] = $decodedContent;
                        $response['success'] = true;
                        $response['activity'] = $activity;
                    } catch (Exception $e) {
                        $response['error'] = 'Invalid game content: ' . $e->getMessage();
                    }
                }
            } else {
                $response['error'] = 'Activity not found';
            }
        } catch(PDOException $e) {
            $response['error'] = $e->getMessage();
        }
        break;

    case 'submit':
        $activityId = $_POST['activity_id'] ?? 0;
        $score = $_POST['score'] ?? 0;
        $percentage = $_POST['percentage'] ?? 0;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO student_submissions 
                    (student_id, activity_id, submission_status, total_score, percentage) 
                VALUES 
                    (?, ?, 'submitted', ?, ?)
                ON DUPLICATE KEY UPDATE 
                    total_score = VALUES(total_score),
                    percentage = VALUES(percentage),
                    submission_status = 'submitted'
            ");
            
            $stmt->execute([$_SESSION['user_id'], $activityId, $score, $percentage]);
            $response['success'] = true;
            $response['message'] = 'Score submitted successfully';
        } catch(PDOException $e) {
            $response['error'] = $e->getMessage();
        }
        break;

    default:
        $response['error'] = 'Invalid action';
}

header('Content-Type: application/json');
echo json_encode($response);
?>