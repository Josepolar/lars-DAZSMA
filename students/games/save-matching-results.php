<?php
session_start();
include '../../Database/database.php';

header('Content-Type: application/json');

// Check if user is STUDENT (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['user_id'];

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

$session_id = $data['session_id'];
$total_correct = $data['total_correct'];
$time_taken = $data['time_taken'];
$total_score = $data['total_score'];
$completed = $data['completed'];
$responses = isset($data['responses']) ? $data['responses'] : [];

try {
    // Verify session belongs to this student and get game info
    $verify = $pdo->prepare("
        SELECT ms.*, mg.matching_game_id, mg.title, mg.due_date
        FROM matching_sessions ms
        JOIN matching_games mg ON ms.matching_game_id = mg.matching_game_id
        WHERE ms.session_id = ? AND ms.student_id = ?
    ");
    $verify->execute([$session_id, $student_id]);
    $session = $verify->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode(['success' => false, 'error' => 'Invalid session']);
        exit();
    }
    
    if (!empty($session['due_date'])) {
        $due_date_obj = new DateTime($session['due_date']);
        if ($due_date_obj <= new DateTime()) {
            echo json_encode(['success' => false, 'error' => 'This matching game is already past due.']);
            exit();
        }
    }
    
    // Try to get points_per_pair, use default if column doesn't exist
    try {
        $pointsQuery = $pdo->prepare("SELECT points_per_pair FROM matching_games WHERE matching_game_id = ?");
        $pointsQuery->execute([$session['matching_game_id']]);
        $gameData = $pointsQuery->fetch(PDO::FETCH_ASSOC);
        $points_per_pair = isset($gameData['points_per_pair']) ? $gameData['points_per_pair'] : 100;
    } catch (Exception $e) {
        // Column doesn't exist, use default
        $points_per_pair = 100;
    }
    
    // Update session
    $update = "UPDATE matching_sessions 
               SET total_score = ?, total_correct = ?, time_taken = ?, completed_at = NOW() 
               WHERE session_id = ?";
    $stmt = $pdo->prepare($update);
    $stmt->execute([$total_score, $total_correct, $time_taken, $session_id]);
    
    // Save individual responses
    if (!empty($responses)) {
        $insertResponse = "INSERT INTO matching_responses (session_id, pair_id, student_answer_pair_id, is_correct, points_earned) 
                          VALUES (?, ?, ?, ?, ?)";
        $stmtResponse = $pdo->prepare($insertResponse);
        
        foreach ($responses as $response) {
            $points = $response['is_correct'] ? $points_per_pair : 0;
            $stmtResponse->execute([
                $session_id,
                $response['pair_id'],
                $response['student_answer'],
                $response['is_correct'] ? 1 : 0,
                $points
            ]);
        }
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
