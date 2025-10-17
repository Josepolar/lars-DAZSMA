<?php
session_start();
require_once __DIR__ . '/../Database/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$teacher_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

switch ($action) {
    case 'bulk_upload':
        handle_bulk_upload($teacher_id);
        break;
        
    case 'export_grades':
        export_grades($teacher_id);
        break;
        
    case 'download_template':
        provide_template();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
}

function handle_bulk_upload($teacher_id) {
    global $pdo;
    
    if (!isset($_FILES['csvFile'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit();
    }

    $file = $_FILES['csvFile'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        exit();
    }

    // Read CSV file
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        echo json_encode(['success' => false, 'message' => 'Could not read file']);
        exit();
    }

    $headers = fgetcsv($handle);
    $results = ['success' => true, 'processed' => 0, 'errors' => []];
    
    try {
        $pdo->beginTransaction();
        
        // Determine file type based on headers
        $isGradesFile = in_array('student_id', $headers) && in_array('activity_id', $headers) && in_array('score', $headers);
        
        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($headers, $data);
            
            if ($isGradesFile) {
                // Process grades upload
                if (empty($row['student_id']) || empty($row['activity_id']) || !isset($row['score'])) {
                    $results['errors'][] = "Missing required fields in row: " . implode(',', $data);
                    continue;
                }

                // Verify student exists and is a student (role_id = 4)
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND role_id = 4");
                $stmt->execute([$row['student_id']]);
                if (!$stmt->fetch()) {
                    $results['errors'][] = "Invalid student ID: " . $row['student_id'];
                    continue;
                }
                
                // Verify the activity exists and belongs to the teacher
                $stmt = $pdo->prepare("SELECT activity_id, total_points FROM activities WHERE activity_id = ? AND teacher_id = ?");
                $stmt->execute([$row['activity_id'], $teacher_id]);
                $activity = $stmt->fetch();
                if (!$activity) {
                    $results['errors'][] = "Unauthorized access or invalid activity ID: " . $row['activity_id'];
                    continue;
                }

                // Validate score
                $maxScore = $row['max_score'] ?? $activity['total_points'];
                if (!is_numeric($row['score']) || $row['score'] < 0 || $row['score'] > $maxScore) {
                    $results['errors'][] = "Invalid score ({$row['score']}) for student ID {$row['student_id']}. Score must be between 0 and {$maxScore}";
                    continue;
                }
                
                // Check if submission already exists
                $stmt = $pdo->prepare("SELECT submission_id FROM student_submissions WHERE student_id = ? AND activity_id = ?");
                $stmt->execute([$row['student_id'], $row['activity_id']]);
                $existingSubmission = $stmt->fetch();
                
                if ($existingSubmission) {
                    // Update existing submission
                    $stmt = $pdo->prepare("UPDATE student_submissions SET 
                        total_score = ?, 
                        max_score = ?, 
                        percentage = ?, 
                        submission_status = 'graded',
                        graded_at = NOW(),
                        updated_at = NOW()
                        WHERE submission_id = ?");
                    
                    // Calculate percentage
                    $percentage = ($row['score'] / $maxScore) * 100;
                    
                    $stmt->execute([
                        $row['score'],
                        $maxScore,
                        $percentage,
                        $existingSubmission['submission_id']
                    ]);
                } else {
                    // Insert new submission
                    $stmt = $pdo->prepare("INSERT INTO student_submissions (
                        student_id, 
                        activity_id, 
                        total_score, 
                        max_score, 
                        percentage, 
                        submission_status, 
                        submitted_at, 
                        graded_at
                    ) VALUES (?, ?, ?, ?, ?, 'graded', NOW(), NOW())");
                    
                    // Calculate percentage
                    $percentage = ($row['score'] / $maxScore) * 100;
                    
                    $stmt->execute([
                        $row['student_id'],
                        $row['activity_id'],
                        $row['score'],
                        $maxScore,
                        $percentage
                    ]);
                }
                
            } else {
                // Process activities upload
                if (empty($row['title']) || empty($row['subject_id']) || empty($row['activity_type'])) {
                    $results['errors'][] = "Missing required fields in row: " . implode(',', $data);
                    continue;
                }
                
                // Insert activity
                $stmt = $pdo->prepare("INSERT INTO activities (title, description, subject_id, teacher_id, activity_type, total_points, time_limit, due_date) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $row['title'],
                    $row['description'] ?? '',
                    $row['subject_id'],
                    $teacher_id,
                    $row['activity_type'],
                    $row['total_points'] ?? 100,
                    $row['time_limit'] ?? null,
                    $row['due_date'] ?? null
                ]);
            }
            
            $results['processed']++;
        }
        
        $pdo->commit();
        echo json_encode($results);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
    fclose($handle);
}

function export_grades($teacher_id) {
    global $pdo;
    
    $activity_id = $_GET['activity_id'] ?? null;
    if (!$activity_id) {
        echo json_encode(['success' => false, 'message' => 'Activity ID is required']);
        exit();
    }
    
    // Verify teacher owns this activity
    $stmt = $pdo->prepare("SELECT activity_id FROM activities WHERE activity_id = ? AND teacher_id = ?");
    $stmt->execute([$activity_id, $teacher_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access to activity']);
        exit();
    }
    
    // Get activity submissions with student information
    $stmt = $pdo->prepare("
        SELECT 
            s.student_id,
            u.first_name,
            u.last_name,
            s.total_score as score,
            s.submitted_at as submission_date,
            s.max_score as total_points,
            s.percentage
        FROM student_submissions s
        JOIN users u ON s.student_id = u.user_id
        JOIN activities a ON s.activity_id = a.activity_id
        WHERE s.activity_id = ?
        ORDER BY u.last_name, u.first_name
    ");
    
    $stmt->execute([$activity_id]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="activity_grades_' . $activity_id . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, ['Student ID', 'Last Name', 'First Name', 'Raw Score', 'Total Points', 'Percentage', 'Submission Date']);
    
    // Add data
    foreach ($submissions as $submission) {
        fputcsv($output, [
            $submission['student_id'],
            $submission['last_name'],
            $submission['first_name'],
            $submission['score'],
            $submission['total_points'],
            $submission['percentage'] . '%',
            $submission['submission_date']
        ]);
    }
    
    fclose($output);
    exit();
}

function provide_template() {
    $template_type = $_GET['type'] ?? '';
    
    switch ($template_type) {
        case 'activities':
            $headers = ['title', 'description', 'subject_id', 'activity_type', 'total_points', 'time_limit', 'due_date'];
            $filename = 'activities_template.csv';
            break;
            
        case 'grades':
            $headers = ['student_id', 'activity_id', 'score', 'comments'];
            $filename = 'grades_template.csv';
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid template type']);
            exit();
    }
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    fclose($output);
    exit();
}