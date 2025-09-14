<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lars_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Security function to prevent SQL injection
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to log activities
function log_activity($conn, $user_id, $action, $affected_id = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, affected_user_id, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $action, $affected_id, $ip_address);
    $stmt->execute();
    $stmt->close();
}

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$teacher_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'create_activity':
        create_activity($conn, $teacher_id);
        break;
    
    case 'get_activities':
        get_activities($conn, $teacher_id);
        break;
    
    case 'get_activity_details':
        get_activity_details($conn, $teacher_id);
        break;
    
    case 'update_activity':
        update_activity($conn, $teacher_id);
        break;
    
    case 'delete_activity':
        delete_activity($conn, $teacher_id);
        break;
    
    case 'get_teacher_subjects':
        get_teacher_subjects($conn, $teacher_id);
        break;
    
    case 'get_activity_submissions':
        get_activity_submissions($conn, $teacher_id);
        break;
    
    case 'get_student_answers':
        get_student_answers($conn, $teacher_id);
        break;
    
    case 'grade_submission':
        grade_submission($conn, $teacher_id);
        break;
    
    case 'get_activity_analytics':
        get_activity_analytics($conn, $teacher_id);
        break;
    
    case 'toggle_activity_status':
        toggle_activity_status($conn, $teacher_id);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function create_activity($conn, $teacher_id) {
    try {
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $subject_id = (int)$_POST['subject_id'];
        $activity_type = sanitize_input($_POST['activity_type']);
        $total_points = (int)$_POST['total_points'];
        $time_limit = !empty($_POST['time_limit']) ? (int)$_POST['time_limit'] : null;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        
        // Validate that teacher teaches this subject
        $check_stmt = $conn->prepare("SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?");
        $check_stmt->bind_param("ii", $teacher_id, $subject_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'You are not authorized to create activities for this subject']);
            return;
        }
        
        // Insert activity - handle nullable fields properly
        if ($time_limit === null && $due_date === null) {
            $stmt = $conn->prepare("INSERT INTO activities (title, description, teacher_id, subject_id, activity_type, total_points) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiisi", $title, $description, $teacher_id, $subject_id, $activity_type, $total_points);
        } elseif ($time_limit === null) {
            $stmt = $conn->prepare("INSERT INTO activities (title, description, teacher_id, subject_id, activity_type, total_points, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiisis", $title, $description, $teacher_id, $subject_id, $activity_type, $total_points, $due_date);
        } elseif ($due_date === null) {
            $stmt = $conn->prepare("INSERT INTO activities (title, description, teacher_id, subject_id, activity_type, total_points, time_limit) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiisii", $title, $description, $teacher_id, $subject_id, $activity_type, $total_points, $time_limit);
        } else {
            $stmt = $conn->prepare("INSERT INTO activities (title, description, teacher_id, subject_id, activity_type, total_points, time_limit, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiisiss", $title, $description, $teacher_id, $subject_id, $activity_type, $total_points, $time_limit, $due_date);
        }
        
        if ($stmt->execute()) {
            $activity_id = $conn->insert_id;
            
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
                        
                        $q_stmt = $conn->prepare("INSERT INTO activity_questions (activity_id, question_text, question_type, points, question_order) VALUES (?, ?, ?, ?, ?)");
                        $q_stmt->bind_param("issii", $activity_id, $question_text, $question_type, $points, $order);
                        
                        if ($q_stmt->execute()) {
                            $question_id = $conn->insert_id;
                            
                            // Handle choices for multiple choice questions
                            if ($question_type === 'multiple_choice' && isset($question['choices']) && is_array($question['choices'])) {
                                foreach ($question['choices'] as $choice_index => $choice) {
                                    if (empty($choice['text'])) {
                                        continue; // Skip empty choices
                                    }
                                    
                                    $choice_text = sanitize_input($choice['text']);
                                    $is_correct = isset($choice['is_correct']) && $choice['is_correct'] === true ? 1 : 0;
                                    $choice_order = $choice_index + 1;
                                    
                                    $c_stmt = $conn->prepare("INSERT INTO question_choices (question_id, choice_text, is_correct, choice_order) VALUES (?, ?, ?, ?)");
                                    $c_stmt->bind_param("isii", $question_id, $choice_text, $is_correct, $choice_order);
                                    $c_stmt->execute();
                                }
                            }
                        }
                    }
                }
            }
            
            log_activity($conn, $teacher_id, 'Created Activity', $activity_id);
            echo json_encode(['success' => true, 'activity_id' => $activity_id, 'message' => 'Activity created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create activity']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_activities($conn, $teacher_id) {
    try {
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
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
        
        echo json_encode(['success' => true, 'activities' => $activities]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_activity_details($conn, $teacher_id) {
    try {
        $activity_id = (int)$_GET['activity_id'];
        
        // Verify ownership
        $check_stmt = $conn->prepare("SELECT * FROM activities WHERE activity_id = ? AND teacher_id = ?");
        $check_stmt->bind_param("ii", $activity_id, $teacher_id);
        $check_stmt->execute();
        $activity_result = $check_stmt->get_result();
        
        if ($activity_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Activity not found or unauthorized']);
            return;
        }
        
        $activity = $activity_result->fetch_assoc();
        
        // Get questions
        $q_stmt = $conn->prepare("SELECT * FROM activity_questions WHERE activity_id = ? ORDER BY question_order");
        $q_stmt->bind_param("i", $activity_id);
        $q_stmt->execute();
        $questions_result = $q_stmt->get_result();
        
        $questions = [];
        while ($question = $questions_result->fetch_assoc()) {
            // Get choices for multiple choice questions
            if ($question['question_type'] === 'multiple_choice') {
                $c_stmt = $conn->prepare("SELECT * FROM question_choices WHERE question_id = ? ORDER BY choice_order");
                $c_stmt->bind_param("i", $question['question_id']);
                $c_stmt->execute();
                $choices_result = $c_stmt->get_result();
                
                $choices = [];
                while ($choice = $choices_result->fetch_assoc()) {
                    $choices[] = $choice;
                }
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

function get_teacher_subjects($conn, $teacher_id) {
    try {
        $sql = "SELECT s.subject_id, s.subject_name, s.grade_level 
                FROM subjects s 
                JOIN teacher_subjects ts ON s.subject_id = ts.subject_id 
                WHERE ts.teacher_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        
        echo json_encode(['success' => true, 'subjects' => $subjects]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_activity_submissions($conn, $teacher_id) {
    try {
        $activity_id = (int)$_GET['activity_id'];
        
        // Verify ownership
        $check_stmt = $conn->prepare("SELECT activity_id FROM activities WHERE activity_id = ? AND teacher_id = ?");
        $check_stmt->bind_param("ii", $activity_id, $teacher_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Activity not found or unauthorized']);
            return;
        }
        
        $sql = "SELECT sub.*, CONCAT(u.first_name, ' ', u.last_name) as student_name, u.grade_level
                FROM student_submissions sub
                JOIN users u ON sub.student_id = u.user_id
                WHERE sub.activity_id = ?
                ORDER BY sub.submitted_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $activity_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $submissions = [];
        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }
        
        echo json_encode(['success' => true, 'submissions' => $submissions]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_activity_analytics($conn, $teacher_id) {
    try {
        $activity_id = (int)$_GET['activity_id'];
        
        // Verify ownership
        $check_stmt = $conn->prepare("SELECT activity_id FROM activities WHERE activity_id = ? AND teacher_id = ?");
        $check_stmt->bind_param("ii", $activity_id, $teacher_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
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
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $activity_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics = $result->fetch_assoc();
        
        echo json_encode(['success' => true, 'analytics' => $analytics]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function update_activity($conn, $teacher_id) {
    try {
        $activity_id = (int)$_POST['activity_id'];
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $activity_type = sanitize_input($_POST['activity_type']);
        $total_points = (int)$_POST['total_points'];
        $time_limit = !empty($_POST['time_limit']) ? (int)$_POST['time_limit'] : null;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        
        // Verify ownership
        $check_stmt = $conn->prepare("SELECT activity_id FROM activities WHERE activity_id = ? AND teacher_id = ?");
        $check_stmt->bind_param("ii", $activity_id, $teacher_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Activity not found or unauthorized']);
            return;
        }
        
        $stmt = $conn->prepare("UPDATE activities SET title = ?, description = ?, activity_type = ?, total_points = ?, time_limit = ?, due_date = ? WHERE activity_id = ?");
        $stmt->bind_param("sssisii", $title, $description, $activity_type, $total_points, $time_limit, $due_date, $activity_id);
        
        if ($stmt->execute()) {
            log_activity($conn, $teacher_id, 'Updated Activity', $activity_id);
            echo json_encode(['success' => true, 'message' => 'Activity updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update activity']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function delete_activity($conn, $teacher_id) {
    try {
        $activity_id = (int)$_POST['activity_id'];
        
        // Verify ownership
        $check_stmt = $conn->prepare("SELECT activity_id FROM activities WHERE activity_id = ? AND teacher_id = ?");
        $check_stmt->bind_param("ii", $activity_id, $teacher_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Activity not found or unauthorized']);
            return;
        }
        
        // Delete activity (cascade will handle related records)
        $stmt = $conn->prepare("DELETE FROM activities WHERE activity_id = ?");
        $stmt->bind_param("i", $activity_id);
        
        if ($stmt->execute()) {
            log_activity($conn, $teacher_id, 'Deleted Activity', $activity_id);
            echo json_encode(['success' => true, 'message' => 'Activity deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete activity']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_student_answers($conn, $teacher_id) {
    try {
        $submission_id = (int)$_GET['submission_id'];
        
        // Verify ownership through activity
        $check_stmt = $conn->prepare("
            SELECT a.activity_id 
            FROM student_submissions sub
            JOIN activities a ON sub.activity_id = a.activity_id
            WHERE sub.submission_id = ? AND a.teacher_id = ?
        ");
        $check_stmt->bind_param("ii", $submission_id, $teacher_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
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
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $submission_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $answers = [];
        while ($row = $result->fetch_assoc()) {
            $answers[] = $row;
        }
        
        echo json_encode(['success' => true, 'answers' => $answers]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function grade_submission($conn, $teacher_id) {
    try {
        $submission_id = (int)$_POST['submission_id'];
        $total_score = (float)$_POST['total_score'];
        $max_score = (float)$_POST['max_score'];
        
        // Verify ownership
        $check_stmt = $conn->prepare("
            SELECT a.activity_id 
            FROM student_submissions sub
            JOIN activities a ON sub.activity_id = a.activity_id
            WHERE sub.submission_id = ? AND a.teacher_id = ?
        ");
        $check_stmt->bind_param("ii", $submission_id, $teacher_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Submission not found or unauthorized']);
            return;
        }
        
        $percentage = ($total_score / $max_score) * 100;
        
        $stmt = $conn->prepare("
            UPDATE student_submissions 
            SET total_score = ?, percentage = ?, submission_status = 'graded', graded_at = NOW()
            WHERE submission_id = ?
        ");
        $stmt->bind_param("ddi", $total_score, $percentage, $submission_id);
        
        if ($stmt->execute()) {
            log_activity($conn, $teacher_id, 'Graded Submission', $submission_id);
            echo json_encode(['success' => true, 'message' => 'Submission graded successfully', 'percentage' => $percentage]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to grade submission']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function toggle_activity_status($conn, $teacher_id) {
    try {
        $activity_id = (int)$_POST['activity_id'];
        
        // Verify ownership
        $check_stmt = $conn->prepare("SELECT is_active FROM activities WHERE activity_id = ? AND teacher_id = ?");
        $check_stmt->bind_param("ii", $activity_id, $teacher_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Activity not found or unauthorized']);
            return;
        }
        
        $current_status = $result->fetch_assoc()['is_active'];
        $new_status = $current_status ? 0 : 1;
        
        $update_stmt = $conn->prepare("UPDATE activities SET is_active = ? WHERE activity_id = ?");
        $update_stmt->bind_param("ii", $new_status, $activity_id);
        
        if ($update_stmt->execute()) {
            $action = $new_status ? 'Activated Activity' : 'Deactivated Activity';
            log_activity($conn, $teacher_id, $action, $activity_id);
            echo json_encode(['success' => true, 'new_status' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update activity status']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

$conn->close();
?>
