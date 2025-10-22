<?php
// Debug handler for game activities
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once '../Database/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $activity_id = $_GET['id'] ?? null;
    
    if (!$activity_id) {
        echo json_encode(['error' => 'No activity ID provided']);
        exit;
    }

    try {
        // Fetch activity details including game content
        $query = "
            SELECT 
                a.*,
                s.subject_name,
                gc.content_data,
                gc.difficulty_level,
                u.first_name as teacher_first_name,
                u.last_name as teacher_last_name
            FROM activities a
            LEFT JOIN subjects s ON a.subject_id = s.subject_id
            LEFT JOIN game_content gc ON a.activity_id = gc.activity_id
            LEFT JOIN users u ON a.teacher_id = u.user_id
            WHERE a.activity_id = ?
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$activity_id]);
        $activity = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$activity) {
            echo json_encode(['error' => 'Activity not found']);
            exit;
        }

        // Debug information
        $debug = [
            'activity' => $activity,
            'game_settings' => json_decode($activity['game_settings'] ?? '{}', true),
            'content_data' => json_decode($activity['content_data'] ?? '{}', true)
        ];

        echo json_encode([
            'success' => true,
            'debug' => $debug
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            'error' => 'Database error',
            'message' => $e->getMessage()
        ]);
    }
}
?>