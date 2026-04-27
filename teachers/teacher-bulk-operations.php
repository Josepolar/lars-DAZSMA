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
        
    case 'export_game_results':
        export_game_results($teacher_id);
        break;
        
    case 'bulk_export_grades':
        bulk_export_grades($teacher_id);
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
            (int)round($submission['score']),
            (int)round($submission['total_points']),
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

function export_game_results($teacher_id) {
    global $pdo;
    
    $game_id = $_GET['game_id'] ?? 0;
    
    // Verify game ownership
    $stmt = $pdo->prepare("SELECT * FROM game_activities WHERE game_id = ? AND teacher_id = ?");
    $stmt->execute([$game_id, $teacher_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access to game']);
        exit();
    }
    
    // Get game results with student information
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id as student_id,
            u.last_name,
            u.first_name,
            gs.total_score,
            gs.total_correct,
            gs.total_questions,
            ROUND((gs.total_correct / gs.total_questions * 100), 1) as accuracy,
            gs.completed_at
        FROM game_sessions gs
        JOIN users u ON gs.student_id = u.user_id
        WHERE gs.game_id = ? AND gs.completed_at IS NOT NULL
        ORDER BY gs.total_score DESC, u.last_name, u.first_name
    ");
    
    $stmt->execute([$game_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="game_results_' . $game_id . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, ['Rank', 'Student ID', 'Last Name', 'First Name', 'Total Score', 'Correct Answers', 'Total Questions', 'Accuracy %', 'Completed Date']);
    
    // Add data
    $rank = 1;
    foreach ($results as $result) {
        fputcsv($output, [
            $rank++,
            $result['student_id'],
            $result['last_name'],
            $result['first_name'],
            (int)round($result['total_score']),
            $result['total_correct'],
            $result['total_questions'],
            $result['accuracy'] . '%',
            $result['completed_at']
        ]);
    }
    
    fclose($output);
    exit();
}

function bulk_export_grades($teacher_id) {
    global $pdo;
    
    $grade_level = $_GET['grade_level'] ?? '';
    
    if (empty($grade_level)) {
        echo json_encode(['success' => false, 'message' => 'Grade level is required']);
        exit();
    }
    
    try {
        // Query to get all students in the specified grade level with their total points
        // and average grade from activities and matching games taught by this teacher
        $query = "SELECT 
                  u.user_id,
                  u.first_name,
                  u.last_name,
                  u.grade_level,
                  (
                    COALESCE(
                        (SELECT SUM(ss.total_score)
                         FROM student_submissions ss
                         JOIN activities a ON ss.activity_id = a.activity_id
                         WHERE ss.student_id = u.user_id
                         AND ss.submission_status = 'graded'
                         AND a.teacher_id = ?), 0
                    ) +
                    COALESCE(
                        (SELECT SUM(ms.total_score)
                         FROM matching_sessions ms
                         JOIN matching_games mg ON ms.matching_game_id = mg.matching_game_id
                         WHERE ms.student_id = u.user_id
                         AND ms.completed_at IS NOT NULL
                         AND mg.teacher_id = ?), 0
                    )
                  ) as total_points,
                  COALESCE(
                      (SELECT AVG(ss.percentage)
                       FROM student_submissions ss
                       JOIN activities a ON ss.activity_id = a.activity_id
                       WHERE ss.student_id = u.user_id
                       AND ss.submission_status = 'graded'
                       AND a.teacher_id = ?), 0
                  ) as avg_grade
                  FROM users u
                  WHERE u.role_id = 4 
                  AND u.grade_level = ?
                  ORDER BY u.last_name, u.first_name";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$teacher_id, $teacher_id, $teacher_id, $grade_level]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check if we need to use PHPSpreadsheet or simple CSV
        // For .xlsx, we'll need PHPSpreadsheet library
        // Let's check if it's available
        if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            generate_xlsx_export($students, $grade_level);
        } else {
            // Fallback to CSV if PHPSpreadsheet is not available
            generate_csv_export($students, $grade_level);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit();
    }
}

function generate_xlsx_export($students, $grade_level) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $sheet->setCellValue('A1', 'Student Name');
    $sheet->setCellValue('B1', 'Grade Level');
    $sheet->setCellValue('C1', 'Total Points');
    $sheet->setCellValue('D1', 'Average Grade (%)');
    
    // Style headers
    $headerStyle = [
        'font' => [
            'bold' => true, 
            'size' => 12,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '004b9c']
        ]
    ];
    $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
    
    // Auto-size columns
    $sheet->getColumnDimension('A')->setAutoSize(true);
    $sheet->getColumnDimension('B')->setAutoSize(true);
    $sheet->getColumnDimension('C')->setAutoSize(true);
    $sheet->getColumnDimension('D')->setAutoSize(true);
    
    // Add data
    $row = 2;
    foreach ($students as $student) {
        $sheet->setCellValue('A' . $row, $student['first_name'] . ' ' . $student['last_name']);
        $sheet->setCellValue('B' . $row, 'Grade ' . $student['grade_level']);
        $sheet->setCellValue('C' . $row, (int)round($student['total_points']));
        $sheet->setCellValue('D' . $row, round($student['avg_grade'], 1) . '%');
        $row++;
    }
    
    // Set headers for download
    $filename = 'Grade_' . $grade_level . '_Class_Record_' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

function generate_csv_export($students, $grade_level) {
    // Fallback to CSV if XLSX library is not available
    $filename = 'Grade_' . $grade_level . '_Class_Record_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, ['Student Name', 'Grade Level', 'Total Points', 'Average Grade (%)']);
    
    // Add data
    foreach ($students as $student) {
        fputcsv($output, [
            $student['first_name'] . ' ' . $student['last_name'],
            'Grade ' . $student['grade_level'],
            (int)round($student['total_points']),
            round($student['avg_grade'], 1) . '%'
        ]);
    }
    
    fclose($output);
    exit();
}
