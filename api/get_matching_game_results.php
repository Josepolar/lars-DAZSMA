<?php
session_start();
header('Content-Type: application/json');
include '../Database/database.php';

// Check if user is STUDENT (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$student_id = $_SESSION['user_id'];

// Get matching game ID
if (!isset($_GET['matching_game_id'])) {
    echo json_encode(['error' => 'Missing matching_game_id parameter']);
    exit();
}

$matching_game_id = $_GET['matching_game_id'];

try {
    // Get game details
    $query = "SELECT mg.*, s.subject_name, CONCAT(u.first_name, ' ', u.last_name) as teacher_name
              FROM matching_games mg
              INNER JOIN subjects s ON mg.subject_id = s.subject_id
              INNER JOIN users u ON mg.teacher_id = u.user_id
              WHERE mg.matching_game_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$matching_game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        echo json_encode(['error' => 'Game not found']);
        exit();
    }

    // Get student's most recent completed session for this game
    $query = "SELECT * FROM matching_sessions 
              WHERE matching_game_id = ? AND student_id = ? AND completed_at IS NOT NULL
              ORDER BY completed_at DESC
              LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$matching_game_id, $student_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        echo json_encode(['error' => 'No completed sessions found for this game']);
        exit();
    }

    // Get all matching pairs for this game
    $query = "SELECT * FROM matching_pairs WHERE matching_game_id = ? ORDER BY pair_order";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$matching_game_id]);
    $pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format session date
    $session['completed_at_formatted'] = date('M d, Y - g:i A', strtotime($session['completed_at']));
    
    // Calculate percentage
    $percentage = ($session['total_pairs'] > 0) ? ($session['total_correct'] / $session['total_pairs']) * 100 : 0;

    // Return data
    echo json_encode([
        'success' => true,
        'game' => $game,
        'session' => $session,
        'pairs' => $pairs,
        'percentage' => round($percentage, 1)
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
?>
