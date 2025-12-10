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

$typing_game_id = intval($data['typing_game_id']);
$text_id = intval($data['text_id']);
$total_characters = intval($data['total_characters']);
$correct_characters = intval($data['correct_characters']);
$wrong_characters = intval($data['wrong_characters']);
$wpm = floatval($data['wpm']);
$accuracy = floatval($data['accuracy']);
$total_score = intval($data['total_score']);
$time_taken = intval($data['time_taken']);
$completed = intval($data['completed']);

try {
    // Verify game exists and is active
    $verify = $pdo->prepare("
        SELECT tg.*, s.subject_name 
        FROM typing_games tg
        JOIN subjects s ON tg.subject_id = s.subject_id
        WHERE tg.typing_game_id = ? AND tg.status = 'active'
        AND (tg.due_date IS NULL OR tg.due_date >= NOW())
    ");
    $verify->execute([$typing_game_id]);
    $game = $verify->fetch(PDO::FETCH_ASSOC);
    
    if (!$game) {
        echo json_encode(['success' => false, 'error' => 'Invalid or expired game']);
        exit();
    }
    
    // Verify text belongs to this game
    $verifyText = $pdo->prepare("SELECT text_id FROM typing_texts WHERE text_id = ? AND typing_game_id = ?");
    $verifyText->execute([$text_id, $typing_game_id]);
    if (!$verifyText->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Invalid text']);
        exit();
    }
    
    // Insert typing session
    $insert = $pdo->prepare("
        INSERT INTO typing_sessions 
        (typing_game_id, student_id, text_id, total_characters, correct_characters, wrong_characters, wpm, accuracy, total_score, time_taken, completed_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $insert->execute([
        $typing_game_id,
        $student_id,
        $text_id,
        $total_characters,
        $correct_characters,
        $wrong_characters,
        $wpm,
        $accuracy,
        $total_score,
        $time_taken
    ]);
    
    $session_id = $pdo->lastInsertId();
    
    // Get student's rank for this game
    $rankQuery = $pdo->prepare("
        SELECT COUNT(*) + 1 as rank
        FROM (
            SELECT student_id, MAX(total_score) as best_score
            FROM typing_sessions
            WHERE typing_game_id = ? AND completed_at IS NOT NULL
            GROUP BY student_id
            HAVING MAX(total_score) > ?
        ) as better_scores
    ");
    $rankQuery->execute([$typing_game_id, $total_score]);
    $rankResult = $rankQuery->fetch(PDO::FETCH_ASSOC);
    $rank = $rankResult['rank'];
    
    // Check if this is a new personal best
    $bestQuery = $pdo->prepare("
        SELECT MAX(total_score) as best_score
        FROM typing_sessions
        WHERE typing_game_id = ? AND student_id = ? AND session_id != ? AND completed_at IS NOT NULL
    ");
    $bestQuery->execute([$typing_game_id, $student_id, $session_id]);
    $bestResult = $bestQuery->fetch(PDO::FETCH_ASSOC);
    $isNewBest = !$bestResult['best_score'] || $total_score > $bestResult['best_score'];
    
    echo json_encode([
        'success' => true,
        'session_id' => $session_id,
        'rank' => $rank,
        'is_new_best' => $isNewBest,
        'passed' => $game['min_wpm'] == 0 || $wpm >= $game['min_wpm']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
