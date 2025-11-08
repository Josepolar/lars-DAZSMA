<?php
header('Content-Type: application/json');
session_start();
include '../../Database/database.php';

$game_id = $_GET['game_id'] ?? 0;
$limit = $_GET['limit'] ?? 10;

try {
    $query = "SELECT 
              CONCAT(u.first_name, ' ', u.last_name) as student_name,
              gs.total_score,
              gs.total_correct,
              gs.total_questions,
              gs.completed_at,
              ROUND((gs.total_correct / gs.total_questions * 100), 1) as accuracy
              FROM game_sessions gs
              INNER JOIN users u ON gs.student_id = u.user_id
              WHERE gs.game_id = ? AND gs.completed_at IS NOT NULL
              ORDER BY gs.total_score DESC, gs.completed_at ASC
              LIMIT ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$game_id, $limit]);
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'leaderboard' => $leaderboard
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
