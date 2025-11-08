<?php
header('Content-Type: application/json');
session_start();
include '../../Database/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$game_id = $_POST['game_id'] ?? 0;
$session_id = $_POST['session_id'] ?? 0;
$question_id = $_POST['question_id'] ?? 0;
$option_id = $_POST['option_id'] ?? 0;
$time_taken = $_POST['time_taken'] ?? 0;

try {
    // Get the selected option
    $option_query = "SELECT is_correct FROM game_options WHERE option_id = ?";
    $stmt = $pdo->prepare($option_query);
    $stmt->execute([$option_id]);
    $option = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$option) {
        echo json_encode(['error' => 'Option not found']);
        exit;
    }
    
    $is_correct = $option['is_correct'];
    
    // Calculate points (Kahoot-style: faster = more points)
    $points_earned = 0;
    if ($is_correct) {
        // Get question details for time limit and base points
        $question_query = "SELECT time_limit, points FROM game_questions WHERE question_id = ?";
        $stmt = $pdo->prepare($question_query);
        $stmt->execute([$question_id]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $base_points = $question['points'];
        $time_limit = $question['time_limit'];
        
        // Calculate time bonus (50% base + 50% time bonus)
        $time_bonus = max(0, ($time_limit - $time_taken) / $time_limit);
        $points_earned = round($base_points * (0.5 + ($time_bonus * 0.5)));
    }
    
    // Save response
    $student_id = $_SESSION['user_id'];
    $response_query = "INSERT INTO game_responses 
                       (game_id, student_id, question_id, selected_option_id, is_correct, time_taken, points_earned)
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($response_query);
    $stmt->execute([$game_id, $student_id, $question_id, $option_id, $is_correct, $time_taken, $points_earned]);
    
    // Update session
    if ($is_correct) {
        $update_session = "UPDATE game_sessions 
                          SET total_score = total_score + ?, 
                              total_correct = total_correct + 1 
                          WHERE session_id = ?";
        $stmt = $pdo->prepare($update_session);
        $stmt->execute([$points_earned, $session_id]);
    }
    
    echo json_encode([
        'success' => true,
        'is_correct' => $is_correct,
        'points_earned' => $points_earned
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
