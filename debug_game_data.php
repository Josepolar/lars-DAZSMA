<?php
require_once('Database/database.php');

// Get the activity ID from URL
$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // Query the activity
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE activity_id = ?");
    $stmt->execute([$activity_id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    // Print activity data for debugging
    header('Content-Type: application/json');
    echo json_encode([
        'activity' => $activity,
        'content_decoded' => $activity ? json_decode($activity['content_data'], true) : null,
        'settings_decoded' => $activity ? json_decode($activity['settings'], true) : null
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}