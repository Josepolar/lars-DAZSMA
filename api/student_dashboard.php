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
include '../Database/database.php';

$student_id = $_SESSION['user_id'];

try {
    // Set PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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

    // Get student's total points and submission statistics (including game scores, matching game scores, and typing game scores)
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT ss.submission_id) as total_submissions,
            COUNT(DISTINCT CASE WHEN ss.submission_status = 'submitted' OR ss.submission_status = 'graded' THEN ss.submission_id END) as completed_submissions,
            (
                COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') THEN ss.total_score ELSE 0 END), 0) +
                COALESCE((SELECT SUM(gs.total_score) FROM game_sessions gs WHERE gs.student_id = ? AND gs.completed_at IS NOT NULL), 0) +
                COALESCE((SELECT SUM(ms.total_score) FROM matching_sessions ms WHERE ms.student_id = ? AND ms.completed_at IS NOT NULL), 0) +
                COALESCE((SELECT SUM(ts.total_score) FROM typing_sessions ts WHERE ts.student_id = ? AND ts.completed_at IS NOT NULL), 0)
            ) as total_points_earned,
            COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') THEN ss.max_score ELSE 0 END), 0) as total_possible_points,
            COALESCE(AVG(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND ss.percentage IS NOT NULL THEN ss.percentage END), 0) as average_percentage
        FROM student_submissions ss
        WHERE ss.student_id = ?
    ");
    $statsStmt->execute([$student_id, $student_id, $student_id, $student_id]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Get recent submissions (completed or graded activities AND completed games)
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
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            NULL as game_id,
            NULL as matching_game_id,
            'activity' as item_type
        FROM student_submissions ss
        JOIN activities a ON ss.activity_id = a.activity_id
        JOIN subjects s ON a.subject_id = s.subject_id
        JOIN users t ON a.teacher_id = t.user_id
        WHERE ss.student_id = ? 
        AND (ss.submission_status = 'submitted' OR ss.submission_status = 'graded')
        
        UNION ALL
        
        SELECT 
            ga.title as activity_title,
            s.subject_name,
            'game' as activity_type,
            gs.total_score,
            (SELECT SUM(points) FROM game_questions WHERE game_id = ga.game_id) as max_score,
            CASE 
                WHEN (SELECT SUM(points) FROM game_questions WHERE game_id = ga.game_id) > 0 
                THEN (gs.total_score / (SELECT SUM(points) FROM game_questions WHERE game_id = ga.game_id)) * 100 
                ELSE 0 
            END as percentage,
            gs.completed_at as submitted_at,
            'completed' as submission_status,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            gs.game_id,
            NULL as matching_game_id,
            'game' as item_type
        FROM game_sessions gs
        JOIN game_activities ga ON gs.game_id = ga.game_id
        JOIN subjects s ON ga.subject_id = s.subject_id
        JOIN users t ON ga.teacher_id = t.user_id
        WHERE gs.student_id = ? 
        AND gs.completed_at IS NOT NULL
        
        UNION ALL
        
        SELECT 
            mg.title as activity_title,
            s.subject_name,
            'matching' as activity_type,
            ms.total_score,
            COALESCE((SELECT COUNT(*) FROM matching_pairs WHERE matching_game_id = mg.matching_game_id), 0) * 100 as max_score,
            CASE 
                WHEN ms.total_pairs > 0 
                THEN (ms.total_correct / ms.total_pairs) * 100 
                ELSE 0 
            END as percentage,
            ms.completed_at as submitted_at,
            'completed' as submission_status,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            NULL as game_id,
            ms.matching_game_id,
            'matching_game' as item_type
        FROM matching_sessions ms
        JOIN matching_games mg ON ms.matching_game_id = mg.matching_game_id
        JOIN subjects s ON mg.subject_id = s.subject_id
        JOIN users t ON mg.teacher_id = t.user_id
        WHERE ms.student_id = ? 
        AND ms.completed_at IS NOT NULL
        
        UNION ALL
        
        SELECT 
            tg.title as activity_title,
            s.subject_name,
            'typing' as activity_type,
            ts.total_score,
            1000 as max_score,
            ts.accuracy as percentage,
            ts.completed_at as submitted_at,
            'completed' as submission_status,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            NULL as game_id,
            ts.typing_game_id as matching_game_id,
            'typing_game' as item_type
        FROM typing_sessions ts
        JOIN typing_games tg ON ts.typing_game_id = tg.typing_game_id
        JOIN subjects s ON tg.subject_id = s.subject_id
        JOIN users t ON tg.teacher_id = t.user_id
        WHERE ts.student_id = ? 
        AND ts.completed_at IS NOT NULL
        
        ORDER BY submitted_at DESC
        LIMIT 10
    ");
    $recentSubmissionsStmt->execute([$student_id, $student_id, $student_id, $student_id]);
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

    // Get active games matching student's grade level (only games not yet played)
    $gamesStmt = $pdo->prepare("
        SELECT 
            ga.game_id,
            ga.title as game_title,
            ga.description,
            ga.time_limit,
            ga.show_leaderboard,
            ga.status,
            s.subject_name,
            s.grade_level,
            CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
            (SELECT COUNT(*) FROM game_questions WHERE game_id = ga.game_id) as question_count,
            (SELECT COUNT(*) FROM game_sessions WHERE game_id = ga.game_id AND student_id = ? AND completed_at IS NOT NULL) as times_played,
            (SELECT total_score FROM game_sessions WHERE game_id = ga.game_id AND student_id = ? AND completed_at IS NOT NULL ORDER BY total_score DESC LIMIT 1) as best_score,
            'quiz' as game_type_flag
        FROM game_activities ga
        INNER JOIN subjects s ON ga.subject_id = s.subject_id
        INNER JOIN users u ON ga.teacher_id = u.user_id
        WHERE s.grade_level = ? 
        AND ga.status = 'active'
        AND NOT EXISTS (
            SELECT 1 FROM game_sessions gs 
            WHERE gs.game_id = ga.game_id 
            AND gs.student_id = ? 
            AND gs.completed_at IS NOT NULL
        )
        
        UNION ALL
        
        SELECT 
            mg.matching_game_id as game_id,
            mg.title as game_title,
            mg.description,
            mg.time_limit,
            mg.show_leaderboard,
            mg.status,
            s.subject_name,
            s.grade_level,
            CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
            (SELECT COUNT(*) FROM matching_pairs WHERE matching_game_id = mg.matching_game_id) as question_count,
            (SELECT COUNT(*) FROM matching_sessions WHERE matching_game_id = mg.matching_game_id AND student_id = ? AND completed_at IS NOT NULL) as times_played,
            (SELECT total_score FROM matching_sessions WHERE matching_game_id = mg.matching_game_id AND student_id = ? AND completed_at IS NOT NULL ORDER BY total_score DESC LIMIT 1) as best_score,
            'matching' as game_type_flag
        FROM matching_games mg
        INNER JOIN subjects s ON mg.subject_id = s.subject_id
        INNER JOIN users u ON mg.teacher_id = u.user_id
        WHERE s.grade_level = ? 
        AND mg.status = 'active'
        AND NOT EXISTS (
            SELECT 1 FROM matching_sessions ms 
            WHERE ms.matching_game_id = mg.matching_game_id 
            AND ms.student_id = ? 
            AND ms.completed_at IS NOT NULL
        )
        
        UNION ALL
        
        SELECT 
            tg.typing_game_id as game_id,
            tg.title as game_title,
            tg.description,
            tg.time_limit,
            tg.show_leaderboard,
            tg.status,
            s.subject_name,
            s.grade_level,
            CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
            (SELECT COUNT(*) FROM typing_texts WHERE typing_game_id = tg.typing_game_id) as question_count,
            (SELECT COUNT(*) FROM typing_sessions WHERE typing_game_id = tg.typing_game_id AND student_id = ? AND completed_at IS NOT NULL) as times_played,
            (SELECT total_score FROM typing_sessions WHERE typing_game_id = tg.typing_game_id AND student_id = ? AND completed_at IS NOT NULL ORDER BY total_score DESC LIMIT 1) as best_score,
            'typing' as game_type_flag
        FROM typing_games tg
        INNER JOIN subjects s ON tg.subject_id = s.subject_id
        INNER JOIN users u ON tg.teacher_id = u.user_id
        WHERE s.grade_level = ? 
        AND tg.status = 'active'
        AND NOT EXISTS (
            SELECT 1 FROM typing_sessions ts 
            WHERE ts.typing_game_id = tg.typing_game_id 
            AND ts.student_id = ? 
            AND ts.completed_at IS NOT NULL
        )
        
        ORDER BY game_title
    ");
    $gamesStmt->execute([$student_id, $student_id, $profile['grade_level'], $student_id, $student_id, $student_id, $profile['grade_level'], $student_id, $student_id, $student_id, $profile['grade_level'], $student_id]);
    $activeGames = $gamesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get class leaderboard (ALL students in same grade level with points from activities, games, matching games, AND typing games)
    $leaderboardStmt = $pdo->prepare("
        SELECT 
            u.user_id,
            CONCAT(u.first_name, ' ', u.last_name) as full_name,
            u.first_name,
            u.last_name,
            (
                COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level THEN ss.total_score ELSE 0 END), 0) +
                COALESCE(SUM(CASE WHEN gs.completed_at IS NOT NULL AND subj.grade_level = u.grade_level THEN gs.total_score ELSE 0 END), 0) +
                COALESCE(SUM(CASE WHEN ms.completed_at IS NOT NULL AND msubj.grade_level = u.grade_level THEN ms.total_score ELSE 0 END), 0) +
                COALESCE(SUM(CASE WHEN ts.completed_at IS NOT NULL AND tsubj.grade_level = u.grade_level THEN ts.total_score ELSE 0 END), 0)
            ) as total_points,
            COUNT(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level THEN 1 END) as completed_activities,
            COUNT(CASE WHEN gs.completed_at IS NOT NULL AND subj.grade_level = u.grade_level THEN 1 END) as completed_games,
            COUNT(CASE WHEN ms.completed_at IS NOT NULL AND msubj.grade_level = u.grade_level THEN 1 END) as completed_matching_games,
            COUNT(CASE WHEN ts.completed_at IS NOT NULL AND tsubj.grade_level = u.grade_level THEN 1 END) as completed_typing_games,
            COALESCE(AVG(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level AND ss.percentage IS NOT NULL THEN ss.percentage END), 0) as avg_percentage
        FROM users u
        LEFT JOIN student_submissions ss ON u.user_id = ss.student_id
        LEFT JOIN activities a ON ss.activity_id = a.activity_id
        LEFT JOIN subjects s ON a.subject_id = s.subject_id
        LEFT JOIN game_sessions gs ON u.user_id = gs.student_id
        LEFT JOIN game_activities ga ON gs.game_id = ga.game_id
        LEFT JOIN subjects subj ON ga.subject_id = subj.subject_id
        LEFT JOIN matching_sessions ms ON u.user_id = ms.student_id
        LEFT JOIN matching_games mg ON ms.matching_game_id = mg.matching_game_id
        LEFT JOIN subjects msubj ON mg.subject_id = msubj.subject_id
        LEFT JOIN typing_sessions ts ON u.user_id = ts.student_id
        LEFT JOIN typing_games tg ON ts.typing_game_id = tg.typing_game_id
        LEFT JOIN subjects tsubj ON tg.subject_id = tsubj.subject_id
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
        'active_games' => $activeGames,
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

} catch (PDOException $e) {
    error_log("Database error in student_dashboard.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred while fetching dashboard data',
        'details' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    error_log("General error in student_dashboard.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Error fetching dashboard data: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>