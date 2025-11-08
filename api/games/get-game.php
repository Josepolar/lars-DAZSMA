<?php
header('Content-Type: application/json');
session_start();
include '../../Database/database.php';

$game_id = $_GET['game_id'] ?? 0;

try {
    // Get game details
    $query = "SELECT * FROM game_activities WHERE game_id = ? AND status = 'active'";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$game) {
        echo json_encode(['error' => 'Game not found']);
        exit;
    }
    
    // Get questions with options
    $questions_query = "SELECT * FROM game_questions WHERE game_id = ? ORDER BY question_order";
    $stmt = $pdo->prepare($questions_query);
    $stmt->execute([$game_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get options for each question
    foreach ($questions as &$question) {
        $options_query = "SELECT option_id, option_text, color_code, option_order, is_correct 
                         FROM game_options 
                         WHERE question_id = ? 
                         ORDER BY option_order";
        $stmt = $pdo->prepare($options_query);
        $stmt->execute([$question['question_id']]);
        $question['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $game['questions'] = $questions;
    
    echo json_encode($game);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
