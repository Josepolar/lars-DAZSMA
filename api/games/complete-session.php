<?php
header('Content-Type: application/json');
session_start();
include '../../Database/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$session_id = $_POST['session_id'] ?? 0;
$total_score = $_POST['total_score'] ?? 0;

try {
    // Update session as completed
    $query = "UPDATE game_sessions 
              SET completed_at = NOW(), total_score = ?
              WHERE session_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$total_score, $session_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Session completed successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
