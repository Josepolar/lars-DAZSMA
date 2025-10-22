<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

include '../Database/database.php';

try {
    $response = [
        'success' => true,
        'student_id' => $_SESSION['user_id'],
        'checks' => []
    ];

    // 1. Check student profile
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role_id = 4");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['checks']['profile'] = !empty($student);
    $response['student'] = $student;

    // 2. Check available activities
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM activities a 
        JOIN subjects s ON a.subject_id = s.subject_id 
        WHERE a.status = 'active' AND s.grade_level = ?
    ");
    $stmt->execute([$student['grade_level']]);
    $activities = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['checks']['has_activities'] = $activities['count'] > 0;
    $response['activities_count'] = $activities['count'];

    // 3. Check submissions
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_submissions WHERE student_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $submissions = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['checks']['has_submissions'] = $submissions['count'] > 0;
    $response['submissions_count'] = $submissions['count'];

    // 4. Sample activity data
    $stmt = $pdo->prepare("
        SELECT 
            a.activity_id,
            a.title,
            s.subject_name,
            agc.game_type,
            COALESCE(ss.submission_status, 'not_started') as status
        FROM activities a
        JOIN subjects s ON a.subject_id = s.subject_id
        LEFT JOIN activity_game_content agc ON a.activity_id = agc.activity_id
        LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
        WHERE a.status = 'active' AND s.grade_level = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id'], $student['grade_level']]);
    $sampleActivity = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['sample_activity'] = $sampleActivity;

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>