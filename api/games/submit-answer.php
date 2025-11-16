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
$time_taken = max(0, (int) ($_POST['time_taken'] ?? 0));
$student_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // Validate session & due date
    $session_query = "SELECT gs.session_id, gs.game_id, gs.student_id, ga.due_date
                      FROM game_sessions gs
                      INNER JOIN game_activities ga ON gs.game_id = ga.game_id
                      WHERE gs.session_id = ? AND gs.game_id = ? AND gs.student_id = ?";
    $stmt = $pdo->prepare($session_query);
    $stmt->execute([$session_id, $game_id, $student_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Invalid session data.']);
        exit;
    }
    
    if (!empty($session['due_date'])) {
        $due_date_obj = new DateTime($session['due_date']);
        if ($due_date_obj <= new DateTime()) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'This activity is already past due.']);
            exit;
        }
    }
    
    // Ensure question belongs to game
    $question_query = "SELECT question_id, time_limit, points FROM game_questions WHERE question_id = ? AND game_id = ?";
    $stmt = $pdo->prepare($question_query);
    $stmt->execute([$question_id, $game_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$question) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Question not found for this game.']);
        exit;
    }
    
    // Ensure selected option belongs to question
    $option_query = "SELECT is_correct FROM game_options WHERE option_id = ? AND question_id = ?";
    $stmt = $pdo->prepare($option_query);
    $stmt->execute([$option_id, $question_id]);
    $option = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$option) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Answer option is not valid for this question.']);
        exit;
    }
    
    // Prevent duplicate responses in the same session
    $existing_query = "SELECT response_id FROM game_responses WHERE session_id = ? AND question_id = ? LIMIT 1";
    $stmt = $pdo->prepare($existing_query);
    $stmt->execute([$session_id, $question_id]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Question already answered for this session.']);
        exit;
    }
    
    $is_correct = (int) $option['is_correct'];
    $points_earned = 0;
    if ($is_correct) {
        $base_points = (int) $question['points'];
        $time_limit = max(1, (int) $question['time_limit']);
        $time_bonus = max(0, ($time_limit - $time_taken) / $time_limit);
        $points_earned = round($base_points * (0.5 + ($time_bonus * 0.5)));
    }
    
    // Save response
    $response_query = "INSERT INTO game_responses 
                       (game_id, student_id, session_id, question_id, selected_option_id, is_correct, time_taken, points_earned)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($response_query);
    $stmt->execute([$game_id, $student_id, $session_id, $question_id, $option_id, $is_correct, $time_taken, $points_earned]);
    
    // Update session aggregate
    if ($is_correct) {
        $update_session = "UPDATE game_sessions 
                          SET total_score = total_score + ?, 
                              total_correct = total_correct + 1 
                          WHERE session_id = ?";
        $stmt = $pdo->prepare($update_session);
        $stmt->execute([$points_earned, $session_id]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'is_correct' => (bool) $is_correct,
        'points_earned' => $points_earned
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
