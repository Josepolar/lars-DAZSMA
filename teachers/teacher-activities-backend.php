<?php
session_start();

// Use shared PDO connection
require_once __DIR__ . '/../Database/database.php';

// Security function to prevent SQL injection
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to log activities
function log_activity_local($user_id, $action, $affected_id = null) {
    global $pdo;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, action, affected_user_id, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $affected_id, $ip_address]);
}

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    // Debug: log what session data we have
    error_log("Session debug - user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
    error_log("Session debug - role_id: " . (isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 'not set'));
    
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode(['success' => false, 'message' => 'Unauthorized access - not logged in as teacher']);
    exit();
}

$teacher_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!headers_sent()) {
    header('Content-Type: application/json');
}

switch ($action) {
    case 'create_activity':
    create_activity($teacher_id);
        break;
    
    case 'get_activities':
    get_activities($teacher_id);
        break;
    
    case 'get_activity_details':
    get_activity_details($teacher_id);
        break;
    
    case 'update_activity':
    update_activity($teacher_id);
        break;
    
    case 'delete_activity':
    delete_activity($teacher_id);
        break;
    
    case 'get_teacher_subjects':
    get_teacher_subjects($teacher_id);
        break;
    
    case 'get_students_for_subject':
    get_students_for_subject($teacher_id);
        break;
    
    case 'dashboard_stats':
    get_dashboard_stats($teacher_id);
        break;
    
    case 'get_activity_submissions':
    get_activity_submissions($teacher_id);
        break;
    
    case 'get_student_answers':
    get_student_answers($teacher_id);
        break;
    
    case 'grade_submission':
    grade_submission($teacher_id);
        break;
    
    case 'get_activity_analytics':
    get_activity_analytics($teacher_id);
        break;
    
    case 'toggle_activity_status':
    toggle_activity_status($teacher_id);
        break;
    
    case 'delete_game':
    delete_game($teacher_id);
        break;
    
    case 'toggle_game_status':
    toggle_game_status($teacher_id);
        break;
    
    case 'create_game':
        create_game($teacher_id);
        break;
    
    case 'add_question':
        add_question($teacher_id);
        break;
    
    case 'delete_question':
        delete_question($teacher_id);
        break;
    
    case 'get_game_questions':
        get_game_questions($teacher_id);
        break;
    
    case 'create_matching_game':
        create_matching_game($teacher_id);
        break;
    
    case 'add_matching_pair':
        add_matching_pair($teacher_id);
        break;
    
    case 'get_matching_pairs':
        get_matching_pairs($teacher_id);
        break;
    
    case 'delete_matching_pair':
        delete_matching_pair($teacher_id);
        break;
    
    case 'activate_matching_game':
        activate_matching_game($teacher_id);
        break;
    
    case 'toggle_matching_game_status':
        toggle_matching_game_status($teacher_id);
        break;
    
    case 'delete_matching_game':
        delete_matching_game_from_dashboard($teacher_id);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function create_activity($teacher_id) {
    global $pdo;
    try {
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $subject_id = (int)$_POST['subject_id'];
        $activity_type = sanitize_input($_POST['activity_type']);
        $total_points = (int)$_POST['total_points'];
        $time_limit = !empty($_POST['time_limit']) ? (int)$_POST['time_limit'] : null;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        
        // Validate that teacher teaches this subject
        $check_stmt = $pdo->prepare("SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?");
        $check_stmt->execute([$teacher_id, $subject_id]);
        if ($check_stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'You are not authorized to create activities for this subject']);
            return;
        }
        
        // Insert activity - handle nullable fields properly
        if ($time_limit === null && $due_date === null) {
            $stmt = $pdo->prepare("INSERT INTO activities (title, description, teacher_id, subject_id, activity_type, total_points) VALUES (?, ?, ?, ?, ?, ?)");
            $ok = $stmt->execute([$title, $description, $teacher_id, $subject_id, $activity_type, $total_points]);
        } elseif ($time_limit === null) {
            $stmt = $pdo->prepare("INSERT INTO activities (title, description, teacher_id, subject_id, activity_type, total_points, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ok = $stmt->execute([$title, $description, $teacher_id, $subject_id, $activity_type, $total_points, $due_date]);
        } elseif ($due_date === null) {
            $stmt = $pdo->prepare("INSERT INTO activities (title, description, teacher_id, subject_id, activity_type, total_points, time_limit) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ok = $stmt->execute([$title, $description, $teacher_id, $subject_id, $activity_type, $total_points, $time_limit]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO activities (title, description, teacher_id, subject_id, activity_type, total_points, time_limit, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ok = $stmt->execute([$title, $description, $teacher_id, $subject_id, $activity_type, $total_points, $time_limit, $due_date]);
        }
        
        if ($ok) {
            $activity_id = (int)$pdo->lastInsertId();
            
            // Handle questions if provided
            if (isset($_POST['questions']) && !empty($_POST['questions'])) {
                // Decode JSON string to array (frontend sends JSON via FormData)
                $questions_data = json_decode($_POST['questions'], true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($questions_data)) {
                    foreach ($questions_data as $index => $question) {
                        if (empty($question['text']) || empty($question['type']) || empty($question['points'])) {
                            continue; // Skip invalid questions
                        }
                        
                        $question_text = sanitize_input($question['text']);
                        $question_type = sanitize_input($question['type']);
                        $points = (int)$question['points'];
                        $order = $index + 1;
                        
                        $q_stmt = $pdo->prepare("INSERT INTO activity_questions (activity_id, question_text, question_type, points, question_order) VALUES (?, ?, ?, ?, ?)");
                        if ($q_stmt->execute([$activity_id, $question_text, $question_type, $points, $order])) {
                            $question_id = (int)$pdo->lastInsertId();
                            
                            // Handle choices for multiple choice questions
                            if ($question_type === 'multiple_choice' && isset($question['choices']) && is_array($question['choices'])) {
                                foreach ($question['choices'] as $choice_index => $choice) {
                                    if (empty($choice['text'])) {
                                        continue; // Skip empty choices
                                    }
                                    
                                    $choice_text = sanitize_input($choice['text']);
                                    $is_correct = isset($choice['is_correct']) && $choice['is_correct'] === true ? 1 : 0;
                                    $choice_order = $choice_index + 1;
                                    
                                    $c_stmt = $pdo->prepare("INSERT INTO question_choices (question_id, choice_text, is_correct, choice_order) VALUES (?, ?, ?, ?)");
                                    $c_stmt->execute([$question_id, $choice_text, $is_correct, $choice_order]);
                                }
                            }
                        }
                    }
                }
            }
            
            log_activity_local($teacher_id, 'Created Activity', $activity_id);
            echo json_encode(['success' => true, 'activity_id' => $activity_id, 'message' => 'Activity created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create activity']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_activities($teacher_id) {
    global $pdo;
    try {
        // Get regular activities
        $sql = "SELECT a.*, s.subject_name, s.grade_level,
                       COUNT(DISTINCT sub.student_id) as total_submissions,
                       COUNT(CASE WHEN sub.submission_status = 'graded' THEN 1 END) as graded_submissions,
                       AVG(CASE WHEN sub.submission_status = 'graded' THEN sub.percentage END) as avg_score
                FROM activities a 
                JOIN subjects s ON a.subject_id = s.subject_id 
                LEFT JOIN student_submissions sub ON a.activity_id = sub.activity_id
                WHERE a.teacher_id = ? 
                GROUP BY a.activity_id
                ORDER BY a.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$teacher_id]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get quiz game activities
        $game_sql = "SELECT 
                        ga.game_id,
                        ga.title,
                        ga.description,
                        ga.subject_id,
                        s.subject_name,
                        s.grade_level,
                        'game' as activity_type,
                        ga.status,
                        ga.created_at,
                        NULL as due_date,
                        (SELECT SUM(points) FROM game_questions WHERE game_id = ga.game_id) as total_points,
                        ga.time_limit,
                        COUNT(DISTINCT gs.student_id) as total_submissions,
                        COUNT(DISTINCT CASE WHEN gs.completed_at IS NOT NULL THEN gs.student_id END) as graded_submissions,
                        ROUND(AVG(CASE WHEN gs.completed_at IS NOT NULL THEN (gs.total_correct / gs.total_questions * 100) END), 1) as avg_score,
                        'quiz' as game_type_flag
                    FROM game_activities ga
                    JOIN subjects s ON ga.subject_id = s.subject_id
                    LEFT JOIN game_sessions gs ON ga.game_id = gs.game_id
                    WHERE ga.teacher_id = ?
                    GROUP BY ga.game_id
                    ORDER BY ga.created_at DESC";
        $game_stmt = $pdo->prepare($game_sql);
        $game_stmt->execute([$teacher_id]);
        $games = $game_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get matching game activities
        $matching_sql = "SELECT 
                        mg.matching_game_id as game_id,
                        mg.title,
                        mg.description,
                        mg.subject_id,
                        s.subject_name,
                        s.grade_level,
                        'game' as activity_type,
                        mg.status,
                        mg.created_at,
                        NULL as due_date,
                        (SELECT COUNT(*) * 100 FROM matching_pairs WHERE matching_game_id = mg.matching_game_id) as total_points,
                        mg.time_limit,
                        COUNT(DISTINCT ms.student_id) as total_submissions,
                        COUNT(DISTINCT CASE WHEN ms.completed_at IS NOT NULL THEN ms.student_id END) as graded_submissions,
                        ROUND(AVG(CASE WHEN ms.completed_at IS NOT NULL THEN (ms.total_correct / ms.total_pairs * 100) END), 1) as avg_score,
                        'matching' as game_type_flag
                    FROM matching_games mg
                    JOIN subjects s ON mg.subject_id = s.subject_id
                    LEFT JOIN matching_sessions ms ON mg.matching_game_id = ms.matching_game_id
                    WHERE mg.teacher_id = ?
                    GROUP BY mg.matching_game_id
                    ORDER BY mg.created_at DESC";
        $matching_stmt = $pdo->prepare($matching_sql);
        $matching_stmt->execute([$teacher_id]);
        $matching_games = $matching_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Merge and mark games
        foreach ($games as &$game) {
            $game['is_game'] = true;
            $game['activity_id'] = 'game_' . $game['game_id']; // Unique ID for frontend
        }
        
        foreach ($matching_games as &$game) {
            $game['is_game'] = true;
            $game['activity_id'] = 'matching_' . $game['game_id']; // Unique ID for frontend
        }
        
        // Combine all arrays
        $all_activities = array_merge($activities, $games, $matching_games);
        
        // Sort by created_at
        usort($all_activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        echo json_encode(['success' => true, 'activities' => $all_activities]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_activity_details($teacher_id) {
    global $pdo;
    try {
        $activity_id = (int)$_GET['activity_id'];
        
        // Verify ownership
        $check_stmt = $pdo->prepare("SELECT * FROM activities WHERE activity_id = ? AND teacher_id = ?");
        $check_stmt->execute([$activity_id, $teacher_id]);
        $activity = $check_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$activity) {
            echo json_encode(['success' => false, 'message' => 'Activity not found or unauthorized']);
            return;
        }
        
        // Get questions
        $q_stmt = $pdo->prepare("SELECT * FROM activity_questions WHERE activity_id = ? ORDER BY question_order");
        $q_stmt->execute([$activity_id]);
        $questions = [];
        while ($question = $q_stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get choices for multiple choice questions
            if ($question['question_type'] === 'multiple_choice') {
                $c_stmt = $pdo->prepare("SELECT * FROM question_choices WHERE question_id = ? ORDER BY choice_order");
                $c_stmt->execute([$question['question_id']]);
                $choices = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
                $question['choices'] = $choices;
            }
            $questions[] = $question;
        }
        
        $activity['questions'] = $questions;
        
        echo json_encode(['success' => true, 'activity' => $activity]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_teacher_subjects($teacher_id) {
    global $pdo;
    try {
        $sql = "SELECT s.subject_id, s.subject_name, s.grade_level 
                FROM subjects s 
                JOIN teacher_subjects ts ON s.subject_id = ts.subject_id 
                WHERE ts.teacher_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$teacher_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'subjects' => $subjects]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_students_for_subject($teacher_id) {
    global $pdo;
    try {
        $subject_id = (int)$_GET['subject_id'];
        
        // Verify that teacher teaches this subject
        $check_stmt = $pdo->prepare("SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?");
        $check_stmt->execute([$teacher_id, $subject_id]);
        if ($check_stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'You are not authorized to view students for this subject']);
            return;
        }
        
        // Get the grade level for this subject
        $grade_stmt = $pdo->prepare("SELECT grade_level FROM subjects WHERE subject_id = ?");
        $grade_stmt->execute([$subject_id]);
        $grade_row = $grade_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$grade_row) {
            echo json_encode(['success' => false, 'message' => 'Subject not found']);
            return;
        }
        
        $grade_level = $grade_row['grade_level'];
        
        // Get students in this grade level with their total points and average grade from all sources
        $sql = "SELECT u.user_id, u.first_name, u.last_name, u.grade_level,
                       COALESCE(
                           (SELECT SUM(ss.total_score) 
                            FROM student_submissions ss
                            JOIN activities a ON ss.activity_id = a.activity_id
                            WHERE ss.student_id = u.user_id 
                            AND ss.submission_status = 'graded'
                            AND a.subject_id = ?), 0
                       ) +
                       COALESCE(
                           (SELECT SUM(ms.total_score)
                            FROM matching_sessions ms
                            JOIN matching_games mg ON ms.matching_game_id = mg.matching_game_id
                            WHERE ms.student_id = u.user_id
                            AND ms.completed_at IS NOT NULL
                            AND mg.subject_id = ?), 0
                       ) as total_points,
                       COALESCE(
                           (SELECT AVG(ss.percentage)
                            FROM student_submissions ss
                            JOIN activities a ON ss.activity_id = a.activity_id
                            WHERE ss.student_id = u.user_id 
                            AND ss.submission_status = 'graded'
                            AND a.subject_id = ?), 0
                       ) as avg_grade
                FROM users u
                WHERE u.role_id = 4 AND u.grade_level = ?
                ORDER BY u.first_name, u.last_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$subject_id, $subject_id, $subject_id, $grade_level]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure total_points is an integer and avg_grade is rounded to 1 decimal
        foreach ($students as &$student) {
            $student['total_points'] = (int)round($student['total_points']);
            $student['avg_grade'] = round($student['avg_grade'], 1);
        }
        
        // Debug info
        $debug_info = [
            'subject_id' => $subject_id,
            'grade_level' => $grade_level,
            'student_count' => count($students),
            'sample_student' => count($students) > 0 ? [
                'name' => $students[0]['first_name'] . ' ' . $students[0]['last_name'],
                'total_points' => $students[0]['total_points'],
                'avg_grade' => $students[0]['avg_grade']
            ] : null
        ];
        
        echo json_encode([
            'success' => true, 
            'students' => $students,
            'debug' => $debug_info
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_dashboard_stats($teacher_id) {
    global $pdo;
    try {
        $response = [
            'teacher_name' => '',
            'total_students' => 0,
            'user_distribution' => null,
            'grade_distribution' => null
        ];

        // Get teacher name
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
        $stmt->execute([$teacher_id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $response['teacher_name'] = $row['first_name'] . ' ' . $row['last_name'];
        }

        // Get grade levels for subjects taught by this teacher
        $grade_levels = [];
        $stmt = $pdo->prepare("SELECT DISTINCT s.grade_level FROM subjects s 
                               JOIN teacher_subjects ts ON s.subject_id = ts.subject_id 
                               WHERE ts.teacher_id = ?");
        $stmt->execute([$teacher_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $grade_levels[] = $row['grade_level'];
        }

        // Get students in those grade levels
        $students = [];
        if (count($grade_levels) > 0) {
            $in = implode(',', array_fill(0, count($grade_levels), '?'));
            $sql = "SELECT user_id, first_name, last_name, grade_level FROM users 
                    WHERE grade_level IN ($in) AND role_id = 4";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($grade_levels);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $response['total_students'] = count($students);

        // User distribution (students only for now)
        $response['user_distribution'] = [
            'labels' => ['Students'],
            'counts' => [count($students)]
        ];

        // Grade distribution
        $grade_counts = [];
        foreach ($students as $stu) {
            $grade = $stu['grade_level'];
            if (!isset($grade_counts[$grade])) $grade_counts[$grade] = 0;
            $grade_counts[$grade]++;
        }
        
        if (empty($grade_counts)) {
            $response['grade_distribution'] = [
                'labels' => ['No Data'],
                'counts' => [1]
            ];
        } else {
            $response['grade_distribution'] = [
                'labels' => array_keys($grade_counts),
                'counts' => array_values($grade_counts)
            ];
        }

        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
    }
}

function get_activity_submissions($teacher_id) {
    global $pdo;
    try {
        $activity_id = (int)$_GET['activity_id'];
        
        // Verify ownership
        $check_stmt = $pdo->prepare("SELECT activity_id FROM activities WHERE activity_id = ? AND teacher_id = ?");
        $check_stmt->execute([$activity_id, $teacher_id]);
        if ($check_stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Activity not found or unauthorized']);
            return;
        }
        
        $sql = "SELECT sub.*, CONCAT(u.first_name, ' ', u.last_name) as student_name, u.grade_level
                FROM student_submissions sub
                JOIN users u ON sub.student_id = u.user_id
                WHERE sub.activity_id = ?
                ORDER BY sub.submitted_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$activity_id]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'submissions' => $submissions]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_activity_analytics($teacher_id) {
    global $pdo;
    try {
        $activity_id = (int)$_GET['activity_id'];
        
        // Verify ownership
        $check_stmt = $pdo->prepare("SELECT activity_id FROM activities WHERE activity_id = ? AND teacher_id = ?");
        $check_stmt->execute([$activity_id, $teacher_id]);
        if ($check_stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Activity not found or unauthorized']);
            return;
        }
        
        // Get analytics data
        $sql = "SELECT 
                    COUNT(DISTINCT sub.student_id) as total_students,
                    COUNT(CASE WHEN sub.submission_status = 'submitted' OR sub.submission_status = 'graded' THEN 1 END) as completed_submissions,
                    COUNT(CASE WHEN sub.submission_status = 'graded' THEN 1 END) as graded_submissions,
                    AVG(CASE WHEN sub.submission_status = 'graded' THEN sub.percentage END) as average_score,
                    MAX(CASE WHEN sub.submission_status = 'graded' THEN sub.percentage END) as highest_score,
                    MIN(CASE WHEN sub.submission_status = 'graded' THEN sub.percentage END) as lowest_score
                FROM student_submissions sub
                WHERE sub.activity_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$activity_id]);
        $analytics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'analytics' => $analytics]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function update_activity($teacher_id) {
    global $pdo;
    try {
        $activity_id = (int)$_POST['activity_id'];
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $activity_type = sanitize_input($_POST['activity_type']);
        $total_points = (int)$_POST['total_points'];
        $time_limit = !empty($_POST['time_limit']) ? (int)$_POST['time_limit'] : null;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        
        // Verify ownership
        $check_stmt = $pdo->prepare("SELECT activity_id FROM activities WHERE activity_id = ? AND teacher_id = ?");
        $check_stmt->execute([$activity_id, $teacher_id]);
        if ($check_stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Activity not found or unauthorized']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE activities SET title = ?, description = ?, activity_type = ?, total_points = ?, time_limit = ?, due_date = ? WHERE activity_id = ?");
        $ok = $stmt->execute([$title, $description, $activity_type, $total_points, $time_limit, $due_date, $activity_id]);
        if ($ok) {
            log_activity_local($teacher_id, 'Updated Activity', $activity_id);
            echo json_encode(['success' => true, 'message' => 'Activity updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update activity']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function delete_activity($teacher_id) {
    global $pdo;
    try {
        $activity_id = (int)$_POST['activity_id'];
        
        // Verify ownership
        $check_stmt = $pdo->prepare("SELECT activity_id FROM activities WHERE activity_id = ? AND teacher_id = ?");
        $check_stmt->execute([$activity_id, $teacher_id]);
        if ($check_stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Activity not found or unauthorized']);
            return;
        }
        
        // Delete activity (cascade will handle related records)
        $stmt = $pdo->prepare("DELETE FROM activities WHERE activity_id = ?");
        $ok = $stmt->execute([$activity_id]);
        if ($ok) {
            log_activity_local($teacher_id, 'Deleted Activity', $activity_id);
            echo json_encode(['success' => true, 'message' => 'Activity deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete activity']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_student_answers($teacher_id) {
    global $pdo;
    try {
        $submission_id = (int)$_GET['submission_id'];
        
        // Verify ownership through activity
        $check_stmt = $pdo->prepare("
            SELECT a.activity_id 
            FROM student_submissions sub
            JOIN activities a ON sub.activity_id = a.activity_id
            WHERE sub.submission_id = ? AND a.teacher_id = ?
        ");
        $check_stmt->execute([$submission_id, $teacher_id]);
        if ($check_stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Submission not found or unauthorized']);
            return;
        }
        
        $sql = "SELECT sa.*, aq.question_text, aq.question_type, aq.points,
                       qc.choice_text, qc.is_correct as choice_is_correct
                FROM student_answers sa
                JOIN activity_questions aq ON sa.question_id = aq.question_id
                LEFT JOIN question_choices qc ON sa.choice_id = qc.choice_id
                WHERE sa.submission_id = ?
                ORDER BY aq.question_order";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$submission_id]);
        $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'answers' => $answers]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function grade_submission($teacher_id) {
    global $pdo;
    try {
        $submission_id = (int)$_POST['submission_id'];
        $total_score = (float)$_POST['total_score'];
        $max_score = (float)$_POST['max_score'];
        
        // Verify ownership
        $check_stmt = $pdo->prepare("
            SELECT a.activity_id 
            FROM student_submissions sub
            JOIN activities a ON sub.activity_id = a.activity_id
            WHERE sub.submission_id = ? AND a.teacher_id = ?
        ");
        $check_stmt->execute([$submission_id, $teacher_id]);
        if ($check_stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Submission not found or unauthorized']);
            return;
        }
        
        $percentage = ($total_score / $max_score) * 100;
        
        $stmt = $pdo->prepare("
            UPDATE student_submissions 
            SET total_score = ?, percentage = ?, submission_status = 'graded', graded_at = NOW()
            WHERE submission_id = ?
        ");
        $ok = $stmt->execute([$total_score, $percentage, $submission_id]);
        if ($ok) {
            log_activity_local($teacher_id, 'Graded Submission', $submission_id);
            echo json_encode(['success' => true, 'message' => 'Submission graded successfully', 'percentage' => $percentage]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to grade submission']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function toggle_activity_status($teacher_id) {
    global $pdo;
    try {
        $activity_id = (int)$_POST['activity_id'];
        
        // Verify ownership
        $check_stmt = $pdo->prepare("SELECT is_active FROM activities WHERE activity_id = ? AND teacher_id = ?");
        $check_stmt->execute([$activity_id, $teacher_id]);
        $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Activity not found or unauthorized']);
            return;
        }
        $current_status = (int)$row['is_active'];
        $new_status = $current_status ? 0 : 1;
        $update_stmt = $pdo->prepare("UPDATE activities SET is_active = ? WHERE activity_id = ?");
        $ok = $update_stmt->execute([$new_status, $activity_id]);
        if ($ok) {
            $action = $new_status ? 'Activated Activity' : 'Deactivated Activity';
            log_activity_local($teacher_id, $action, $activity_id);
            echo json_encode(['success' => true, 'new_status' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update activity status']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function delete_game($teacher_id) {
    global $pdo;
    try {
        $game_id = (int)$_POST['game_id'];
        
        // Verify ownership
        $check_stmt = $pdo->prepare("SELECT game_id FROM game_activities WHERE game_id = ? AND teacher_id = ?");
        $check_stmt->execute([$game_id, $teacher_id]);
        if (!$check_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Game not found or unauthorized']);
            return;
        }
        
        // Delete game (cascade will delete questions, options, responses, sessions)
        $delete_stmt = $pdo->prepare("DELETE FROM game_activities WHERE game_id = ? AND teacher_id = ?");
        $ok = $delete_stmt->execute([$game_id, $teacher_id]);
        
        if ($ok) {
            log_activity_local($teacher_id, 'Deleted Game Activity (ID: ' . $game_id . ')', null);
            echo json_encode(['success' => true, 'message' => 'Game deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete game']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function toggle_game_status($teacher_id) {
    global $pdo;
    try {
        $game_id = (int)$_POST['game_id'];
        $new_status = $_POST['new_status'] ?? 'draft';
        
        // Validate status
        $valid_statuses = ['draft', 'active', 'completed', 'archived'];
        if (!in_array($new_status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            return;
        }
        
        // Verify ownership
        $check_stmt = $pdo->prepare("SELECT status FROM game_activities WHERE game_id = ? AND teacher_id = ?");
        $check_stmt->execute([$game_id, $teacher_id]);
        if (!$check_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Game not found or unauthorized']);
            return;
        }
        
        // Update status
        $update_stmt = $pdo->prepare("UPDATE game_activities SET status = ? WHERE game_id = ? AND teacher_id = ?");
        $ok = $update_stmt->execute([$new_status, $game_id, $teacher_id]);
        
        if ($ok) {
            $action = 'Changed Game Status to ' . ucfirst($new_status) . ' (Game ID: ' . $game_id . ')';
            log_activity_local($teacher_id, $action, null);
            echo json_encode(['success' => true, 'new_status' => $new_status, 'message' => 'Game status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update game status']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function create_game($teacher_id) {
    global $pdo;
    try {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $time_limit = (int)($_POST['time_limit'] ?? 30);
        $show_leaderboard = isset($_POST['show_leaderboard']) ? 1 : 0;
        
        // Validation
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Game title is required']);
            return;
        }
        
        if (empty($subject_id)) {
            echo json_encode(['success' => false, 'message' => 'Please select a subject']);
            return;
        }
        
        // Verify teacher teaches this subject
        $verify_stmt = $pdo->prepare("SELECT 1 FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?");
        $verify_stmt->execute([$teacher_id, $subject_id]);
        
        if (!$verify_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to create games for this subject']);
            return;
        }
        
        // Insert game activity
        $query = "INSERT INTO game_activities (subject_id, teacher_id, title, description, time_limit, show_leaderboard, status, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, 'draft', NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$subject_id, $teacher_id, $title, $description, $time_limit, $show_leaderboard]);
        
        $game_id = $pdo->lastInsertId();
        
        // Get subject name for response
        $subject_query = "SELECT subject_name FROM subjects WHERE subject_id = ?";
        $subject_stmt = $pdo->prepare($subject_query);
        $subject_stmt->execute([$subject_id]);
        $subject_name = $subject_stmt->fetchColumn();
        
        // Log activity (pass null for affected_user_id since game_id is not a user_id)
        log_activity_local($teacher_id, 'Created Game Activity: ' . $title, null);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Game created successfully',
            'game_id' => $game_id,
            'game_title' => $title,
            'subject_name' => $subject_name,
            'time_limit' => $time_limit
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function add_question($teacher_id) {
    global $pdo;
    try {
        $game_id = (int)($_POST['game_id'] ?? 0);
        $question_text = trim($_POST['question_text'] ?? '');
        $time_limit = (int)($_POST['time_limit'] ?? 30);
        $points = (int)($_POST['points'] ?? 1000);
        $options = $_POST['options'] ?? [];
        $correct_option = (int)($_POST['correct_option'] ?? 0);
        
        // Verify game ownership
        $verify_stmt = $pdo->prepare("SELECT game_id FROM game_activities WHERE game_id = ? AND teacher_id = ?");
        $verify_stmt->execute([$game_id, $teacher_id]);
        if (!$verify_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Game not found or unauthorized']);
            return;
        }
        
        // Validation
        if (empty($question_text)) {
            echo json_encode(['success' => false, 'message' => 'Question text is required']);
            return;
        }
        
        // Filter out empty options
        $valid_options = array_filter($options, function($opt) {
            return !empty(trim($opt));
        });
        
        if (count($valid_options) < 2) {
            echo json_encode(['success' => false, 'message' => 'Please provide at least 2 answer options']);
            return;
        }
        
        // Get next question order
        $order_query = "SELECT COALESCE(MAX(question_order), 0) + 1 as next_order FROM game_questions WHERE game_id = ?";
        $stmt = $pdo->prepare($order_query);
        $stmt->execute([$game_id]);
        $next_order = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
        
        // Insert question
        $query = "INSERT INTO game_questions (game_id, question_text, question_order, time_limit, points) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$game_id, $question_text, $next_order, $time_limit, $points]);
        $question_id = $pdo->lastInsertId();
        
        // Insert options
        $colors = ['red', 'blue', 'yellow', 'green'];
        $option_order = 0;
        
        foreach ($options as $index => $option_text) {
            if (!empty(trim($option_text))) {
                $is_correct = ($index == $correct_option) ? 1 : 0;
                $color = $colors[$option_order % 4];
                
                $option_query = "INSERT INTO game_options (question_id, option_text, is_correct, option_order, color_code) 
                                VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($option_query);
                $stmt->execute([$question_id, trim($option_text), $is_correct, $option_order, $color]);
                $option_order++;
            }
        }
        
        log_activity_local($teacher_id, 'Added question to game (ID: ' . $game_id . ')', null);
        
        echo json_encode([
            'success' => true,
            'message' => 'Question added successfully',
            'question_id' => $question_id
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function delete_question($teacher_id) {
    global $pdo;
    try {
        $question_id = (int)($_POST['question_id'] ?? 0);
        $game_id = (int)($_POST['game_id'] ?? 0);
        
        // Verify ownership through game
        $verify_stmt = $pdo->prepare("SELECT gq.question_id FROM game_questions gq
                                      INNER JOIN game_activities ga ON gq.game_id = ga.game_id
                                      WHERE gq.question_id = ? AND gq.game_id = ? AND ga.teacher_id = ?");
        $verify_stmt->execute([$question_id, $game_id, $teacher_id]);
        
        if (!$verify_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Question not found or unauthorized']);
            return;
        }
        
        // Delete question (cascade will delete options)
        $delete_stmt = $pdo->prepare("DELETE FROM game_questions WHERE question_id = ?");
        $ok = $delete_stmt->execute([$question_id]);
        
        if ($ok) {
            log_activity_local($teacher_id, 'Deleted question from game (ID: ' . $game_id . ')', null);
            echo json_encode(['success' => true, 'message' => 'Question deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete question']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_game_questions($teacher_id) {
    global $pdo;
    try {
        $game_id = (int)($_GET['game_id'] ?? 0);
        
        // Verify game ownership
        $verify_stmt = $pdo->prepare("SELECT game_id FROM game_activities WHERE game_id = ? AND teacher_id = ?");
        $verify_stmt->execute([$game_id, $teacher_id]);
        if (!$verify_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Game not found or unauthorized']);
            return;
        }
        
        // Get questions with option count
        $query = "SELECT gq.*, 
                  (SELECT COUNT(*) FROM game_options WHERE question_id = gq.question_id) as option_count
                  FROM game_questions gq
                  WHERE gq.game_id = ?
                  ORDER BY gq.question_order";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$game_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'questions' => $questions
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ========================================
// Matching Game Functions
// ========================================

function create_matching_game($teacher_id) {
    global $pdo;
    try {
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description'] ?? '');
        $subject_id = (int)$_POST['subject_id'];
        $game_type = sanitize_input($_POST['game_type']);
        $time_limit = (int)$_POST['time_limit'];
        $show_leaderboard = (int)($_POST['show_leaderboard'] ?? 1);
        
        // Validate teacher teaches this subject
        $verify = $pdo->prepare("SELECT 1 FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?");
        $verify->execute([$teacher_id, $subject_id]);
        if (!$verify->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to create games for this subject']);
            return;
        }
        
        // Get subject name
        $subject_stmt = $pdo->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
        $subject_stmt->execute([$subject_id]);
        $subject = $subject_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Insert matching game
        $query = "INSERT INTO matching_games (subject_id, teacher_id, title, description, game_type, time_limit, show_leaderboard, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$subject_id, $teacher_id, $title, $description, $game_type, $time_limit, $show_leaderboard]);
        
        $matching_game_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'matching_game_id' => $matching_game_id,
            'game_title' => $title,
            'subject_name' => $subject['subject_name'],
            'game_type' => $game_type,
            'message' => 'Matching game created successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function add_matching_pair($teacher_id) {
    global $pdo;
    try {
        $matching_game_id = (int)$_POST['matching_game_id'];
        $game_type = sanitize_input($_POST['game_type']);
        
        // Verify game ownership
        $verify = $pdo->prepare("SELECT matching_game_id FROM matching_games WHERE matching_game_id = ? AND teacher_id = ?");
        $verify->execute([$matching_game_id, $teacher_id]);
        if (!$verify->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Game not found or unauthorized']);
            return;
        }
        
        $left_item = null;
        $right_item = null;
        $left_image = null;
        $right_image = null;
        
        // Handle different game types
        if ($game_type === 'image-to-text') {
            // Left is image, right is text
            if (isset($_FILES['left_image']) && $_FILES['left_image']['error'] === UPLOAD_ERR_OK) {
                $left_image = upload_image($_FILES['left_image']);
                if (!$left_image) {
                    echo json_encode(['success' => false, 'message' => 'Failed to upload left image']);
                    return;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Left image is required']);
                return;
            }
            $right_item = sanitize_input($_POST['right_item']);
        } elseif ($game_type === 'text-to-text' || $game_type === 'number-to-text') {
            // Both are text
            $left_item = sanitize_input($_POST['left_item']);
            $right_item = sanitize_input($_POST['right_item']);
        } elseif ($game_type === 'image-to-image') {
            // Both are images
            if (isset($_FILES['left_image']) && $_FILES['left_image']['error'] === UPLOAD_ERR_OK) {
                $left_image = upload_image($_FILES['left_image']);
                if (!$left_image) {
                    echo json_encode(['success' => false, 'message' => 'Failed to upload left image']);
                    return;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Left image is required']);
                return;
            }
            
            if (isset($_FILES['right_image']) && $_FILES['right_image']['error'] === UPLOAD_ERR_OK) {
                $right_image = upload_image($_FILES['right_image']);
                if (!$right_image) {
                    echo json_encode(['success' => false, 'message' => 'Failed to upload right image']);
                    return;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Right image is required']);
                return;
            }
        }
        
        // Get next pair order
        $order_stmt = $pdo->prepare("SELECT COALESCE(MAX(pair_order), 0) + 1 as next_order FROM matching_pairs WHERE matching_game_id = ?");
        $order_stmt->execute([$matching_game_id]);
        $pair_order = $order_stmt->fetchColumn();
        
        // Insert pair with correct column names
        $query = "INSERT INTO matching_pairs (matching_game_id, left_item_text, right_item_text, left_item_image, right_item_image, pair_order) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$matching_game_id, $left_item, $right_item, $left_image, $right_image, $pair_order]);
        
        echo json_encode(['success' => true, 'message' => 'Pair added successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function upload_image($file) {
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $max_file_size = 5 * 1024 * 1024; // 5MB
    
    $file_name = $file['name'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_extensions)) {
        return false;
    }
    
    if ($file_size > $max_file_size) {
        return false;
    }
    
    $new_file_name = uniqid() . '_' . time() . '.' . $file_ext;
    $upload_dir = '../uploads/matching_games/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $upload_path = $upload_dir . $new_file_name;
    
    if (move_uploaded_file($file_tmp, $upload_path)) {
        return 'uploads/matching_games/' . $new_file_name;
    }
    
    return false;
}

function get_matching_pairs($teacher_id) {
    global $pdo;
    try {
        $matching_game_id = (int)($_GET['matching_game_id'] ?? 0);
        
        // Verify game ownership
        $verify = $pdo->prepare("SELECT matching_game_id FROM matching_games WHERE matching_game_id = ? AND teacher_id = ?");
        $verify->execute([$matching_game_id, $teacher_id]);
        if (!$verify->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Game not found or unauthorized']);
            return;
        }
        
        // Get pairs
        $query = "SELECT * FROM matching_pairs WHERE matching_game_id = ? ORDER BY pair_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$matching_game_id]);
        $pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'pairs' => $pairs
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function delete_matching_pair($teacher_id) {
    global $pdo;
    try {
        $pair_id = (int)$_POST['pair_id'];
        
        // Verify ownership through matching_games with correct column names
        $verify = $pdo->prepare("
            SELECT mp.pair_id, mp.left_item_image, mp.right_item_image
            FROM matching_pairs mp
            INNER JOIN matching_games mg ON mp.matching_game_id = mg.matching_game_id
            WHERE mp.pair_id = ? AND mg.teacher_id = ?
        ");
        $verify->execute([$pair_id, $teacher_id]);
        $pair = $verify->fetch(PDO::FETCH_ASSOC);
        
        if (!$pair) {
            echo json_encode(['success' => false, 'message' => 'Pair not found or unauthorized']);
            return;
        }
        
        // Delete image files if they exist
        if ($pair['left_item_image'] && file_exists('../' . $pair['left_item_image'])) {
            unlink('../' . $pair['left_item_image']);
        }
        if ($pair['right_item_image'] && file_exists('../' . $pair['right_item_image'])) {
            unlink('../' . $pair['right_item_image']);
        }
        
        // Delete pair
        $stmt = $pdo->prepare("DELETE FROM matching_pairs WHERE pair_id = ?");
        $stmt->execute([$pair_id]);
        
        echo json_encode(['success' => true, 'message' => 'Pair deleted successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function activate_matching_game($teacher_id) {
    global $pdo;
    try {
        $matching_game_id = (int)$_POST['matching_game_id'];
        
        // Verify game ownership
        $verify = $pdo->prepare("SELECT matching_game_id FROM matching_games WHERE matching_game_id = ? AND teacher_id = ?");
        $verify->execute([$matching_game_id, $teacher_id]);
        if (!$verify->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Game not found or unauthorized']);
            return;
        }
        
        // Check minimum pairs (at least 3)
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM matching_pairs WHERE matching_game_id = ?");
        $count_stmt->execute([$matching_game_id]);
        $pair_count = $count_stmt->fetchColumn();
        
        if ($pair_count < 3) {
            echo json_encode(['success' => false, 'message' => 'Game needs at least 3 pairs to be activated']);
            return;
        }
        
        // Activate game
        $stmt = $pdo->prepare("UPDATE matching_games SET status = 'active' WHERE matching_game_id = ?");
        $stmt->execute([$matching_game_id]);
        
        echo json_encode(['success' => true, 'message' => 'Game activated successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function toggle_matching_game_status($teacher_id) {
    global $pdo;
    try {
        $matching_game_id = (int)$_POST['matching_game_id'];
        $new_status = sanitize_input($_POST['new_status']);
        
        // Verify game ownership
        $verify = $pdo->prepare("SELECT matching_game_id FROM matching_games WHERE matching_game_id = ? AND teacher_id = ?");
        $verify->execute([$matching_game_id, $teacher_id]);
        if (!$verify->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Game not found or unauthorized']);
            return;
        }
        
        // If activating, check minimum pairs
        if ($new_status === 'active') {
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM matching_pairs WHERE matching_game_id = ?");
            $count_stmt->execute([$matching_game_id]);
            $pair_count = $count_stmt->fetchColumn();
            
            if ($pair_count < 3) {
                echo json_encode(['success' => false, 'message' => 'Game needs at least 3 pairs to be activated']);
                return;
            }
        }
        
        // Update status
        $stmt = $pdo->prepare("UPDATE matching_games SET status = ? WHERE matching_game_id = ?");
        $stmt->execute([$new_status, $matching_game_id]);
        
        echo json_encode(['success' => true, 'message' => 'Matching game status updated successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function delete_matching_game_from_dashboard($teacher_id) {
    global $pdo;
    try {
        $matching_game_id = (int)$_POST['matching_game_id'];
        
        // Verify game ownership and get all image paths
        $verify = $pdo->prepare("
            SELECT mp.left_item_image, mp.right_item_image
            FROM matching_pairs mp
            INNER JOIN matching_games mg ON mp.matching_game_id = mg.matching_game_id
            WHERE mg.matching_game_id = ? AND mg.teacher_id = ?
        ");
        $verify->execute([$matching_game_id, $teacher_id]);
        $pairs = $verify->fetchAll(PDO::FETCH_ASSOC);
        
        if ($pairs === false && !empty($pairs)) {
            // Check if game exists at all
            $check = $pdo->prepare("SELECT matching_game_id FROM matching_games WHERE matching_game_id = ? AND teacher_id = ?");
            $check->execute([$matching_game_id, $teacher_id]);
            if (!$check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Game not found or unauthorized']);
                return;
            }
        }
        
        // Delete all associated image files
        foreach ($pairs as $pair) {
            if ($pair['left_item_image'] && file_exists('../' . $pair['left_item_image'])) {
                unlink('../' . $pair['left_item_image']);
            }
            if ($pair['right_item_image'] && file_exists('../' . $pair['right_item_image'])) {
                unlink('../' . $pair['right_item_image']);
            }
        }
        
        // Delete the game (CASCADE will delete pairs, sessions, and responses)
        $stmt = $pdo->prepare("DELETE FROM matching_games WHERE matching_game_id = ? AND teacher_id = ?");
        $stmt->execute([$matching_game_id, $teacher_id]);
        
        echo json_encode(['success' => true, 'message' => 'Matching game deleted successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
