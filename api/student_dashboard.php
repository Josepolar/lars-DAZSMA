<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lars_db";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$student_id = $_SESSION['user_id'];

try {
    // Get student profile information
    $profileStmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.first_name,
            u.last_name,
            u.username,
            u.email,
            u.grade_level,
            u.created_at
        FROM users u 
        WHERE u.user_id = ? AND u.role_id = 4
    ");
    $profileStmt->execute([$student_id]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        throw new Exception('Student profile not found');
    }

    // Get student's total points and submission statistics
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT ss.submission_id) as total_submissions,
            COUNT(DISTINCT CASE WHEN ss.submission_status = 'submitted' OR ss.submission_status = 'graded' THEN ss.submission_id END) as completed_submissions,
            COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') THEN ss.total_score ELSE 0 END), 0) as total_points_earned,
            COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') THEN ss.max_score ELSE 0 END), 0) as total_possible_points,
            COALESCE(AVG(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND ss.percentage IS NOT NULL THEN ss.percentage END), 0) as average_percentage
        FROM student_submissions ss
        WHERE ss.student_id = ?
    ");
    $statsStmt->execute([$student_id]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Get recent submissions (completed or graded)
    $recentSubmissionsStmt = $pdo->prepare("
        SELECT 
            a.title as activity_title,
            s.subject_name,
            a.activity_type,
            ss.total_score,
            ss.max_score,
            ss.percentage,
            ss.submitted_at,
            ss.submission_status,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name
        FROM student_submissions ss
        JOIN activities a ON ss.activity_id = a.activity_id
        JOIN subjects s ON a.subject_id = s.subject_id
        JOIN users t ON a.teacher_id = t.user_id
        WHERE ss.student_id = ? 
        AND (ss.submission_status = 'submitted' OR ss.submission_status = 'graded')
        ORDER BY ss.submitted_at DESC
        LIMIT 5
    ");
    $recentSubmissionsStmt->execute([$student_id]);
    $recentSubmissions = $recentSubmissionsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get pending activities (not started or in progress) - filtered by student's grade level
    $pendingActivitiesStmt = $pdo->prepare("
        SELECT 
            a.activity_id,
            a.title as activity_title,
            s.subject_name,
            s.grade_level,
            a.activity_type,
            a.total_points,
            a.time_limit,
            a.due_date,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            COALESCE(ss.submission_status, 'not_started') as submission_status
        FROM activities a
        JOIN subjects s ON a.subject_id = s.subject_id
        JOIN users t ON a.teacher_id = t.user_id
        LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
        WHERE a.is_active = 1 
        AND s.grade_level = ?
        AND (ss.submission_status IS NULL OR ss.submission_status IN ('not_started', 'in_progress'))
        AND (a.due_date IS NULL OR a.due_date > NOW())
        ORDER BY 
            CASE WHEN a.due_date IS NULL THEN 1 ELSE 0 END,
            a.due_date ASC
        LIMIT 10
    ");
    $pendingActivitiesStmt->execute([$student_id, $profile['grade_level']]);
    $pendingActivities = $pendingActivitiesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get activity of the day (nearest deadline or most recent) - filtered by student's grade level
    $activityOfDayStmt = $pdo->prepare("
        SELECT 
            a.activity_id,
            a.title as activity_title,
            s.subject_name,
            s.grade_level,
            a.activity_type,
            a.total_points,
            a.due_date,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name
        FROM activities a
        JOIN subjects s ON a.subject_id = s.subject_id
        JOIN users t ON a.teacher_id = t.user_id
        LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
        WHERE a.is_active = 1 
        AND s.grade_level = ?
        AND (ss.submission_status IS NULL OR ss.submission_status IN ('not_started', 'in_progress'))
        AND (a.due_date IS NULL OR a.due_date > NOW())
        ORDER BY 
            CASE WHEN a.due_date IS NULL THEN 1 ELSE 0 END,
            a.due_date ASC
        LIMIT 1
    ");
    $activityOfDayStmt->execute([$student_id, $profile['grade_level']]);
    $activityOfDay = $activityOfDayStmt->fetch(PDO::FETCH_ASSOC);

    // Get subjects with active recitations count - filtered by student's grade level
    $subjectsStmt = $pdo->prepare("
        SELECT 
            s.subject_id,
            s.subject_name,
            s.grade_level,
            COUNT(DISTINCT a.activity_id) as active_activities_count
        FROM subjects s
        JOIN activities a ON s.subject_id = a.subject_id 
            AND a.is_active = 1 
            AND (a.due_date IS NULL OR a.due_date > NOW())
        LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
        WHERE s.grade_level = ?
        AND (ss.submission_status IS NULL OR ss.submission_status IN ('not_started', 'in_progress'))
        GROUP BY s.subject_id, s.subject_name, s.grade_level
        ORDER BY s.subject_name
    ");
    $subjectsStmt->execute([$student_id, $profile['grade_level']]);
    $subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get class leaderboard (ALL students in same grade level with points only from their grade level activities)
    $leaderboardStmt = $pdo->prepare("
        SELECT 
            u.user_id,
            CONCAT(u.first_name, ' ', u.last_name) as full_name,
            u.first_name,
            u.last_name,
            COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level THEN ss.total_score ELSE 0 END), 0) as total_points,
            COUNT(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level THEN 1 END) as completed_activities,
            COALESCE(AVG(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level AND ss.percentage IS NOT NULL THEN ss.percentage END), 0) as avg_percentage
        FROM users u
        LEFT JOIN student_submissions ss ON u.user_id = ss.student_id
        LEFT JOIN activities a ON ss.activity_id = a.activity_id
        LEFT JOIN subjects s ON a.subject_id = s.subject_id
        WHERE u.role_id = 4 AND u.grade_level = ?
        GROUP BY u.user_id, u.first_name, u.last_name
        ORDER BY total_points DESC, avg_percentage DESC
    ");
    $leaderboardStmt->execute([$profile['grade_level']]);
    $leaderboard = $leaderboardStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add rank to leaderboard
    foreach ($leaderboard as $index => &$student) {
        $student['rank'] = $index + 1;
        $student['is_current_user'] = ($student['user_id'] == $student_id);
    }

    // Prepare response
    $response = [
        'profile' => $profile,
        'stats' => [
            'total_submissions' => (int)$stats['total_submissions'],
            'completed_submissions' => (int)$stats['completed_submissions'],
            'total_points_earned' => (float)$stats['total_points_earned'],
            'total_possible_points' => (float)$stats['total_possible_points'],
            'average_percentage' => round((float)$stats['average_percentage'], 1),
            'completion_rate' => $stats['total_submissions'] > 0 ? round(($stats['completed_submissions'] / $stats['total_submissions']) * 100, 1) : 0
        ],
        'recent_submissions' => $recentSubmissions,
        'pending_activities' => $pendingActivities,
        'activity_of_day' => $activityOfDay,
        'subjects' => $subjects,
        'leaderboard' => $leaderboard,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Debug output
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        echo json_encode([
            'student_id' => $student_id,
            'student_grade' => $profile['grade_level'],
            'pending_activities' => $pendingActivities,
            'activity_of_day' => $activityOfDay,
            'subjects' => $subjects,
            'now' => date('Y-m-d H:i:s'),
        ], JSON_PRETTY_PRINT);
        exit();
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching dashboard data: ' . $e->getMessage()]);
}
?>