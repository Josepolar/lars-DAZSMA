<?php
session_start();
require_once '../log_activity.php';

// Redirect to login if session is missing or expired
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../Database/database.php';

$action = $_POST['action'] ?? '';

if ($action === 'export') {
    handleExport($pdo);
} elseif ($action === 'archive') {
    handleArchive($pdo);
} elseif ($action === 'get_details') {
    handleGetDetails($pdo);
} elseif ($action === 'get_students') {
    handleGetStudents($pdo);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleExport($pdo) {
    $format = $_POST['format'] ?? 'csv';
    $grades = explode(',', $_POST['grades'] ?? '7,8,9,10');
    $fields = isset($_POST['fields']) ? json_decode($_POST['fields'], true) : ['email', 'grade', 'section'];

    // Validate grades
    $grades = array_filter($grades, function($g) {
        return in_array(trim($g), ['7', '8', '9', '10']);
    });

    if (empty($grades)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No valid grades selected']);
        exit();
    }

    $gradeList = "'" . implode("','", array_map('trim', $grades)) . "'";

    // Get students
    $query = "SELECT user_id, first_name, last_name, username, email, grade_level, section, password 
              FROM users WHERE role_id = 4 AND grade_level IN ($gradeList) 
              ORDER BY grade_level, first_name, last_name";
    
    $result = $pdo->query($query);
    $students = $result->fetchAll(PDO::FETCH_ASSOC);

    if ($format === 'csv') {
        exportCSV($students, $fields);
    } elseif ($format === 'excel') {
        exportExcel($students, $fields);
    } elseif ($format === 'json') {
        exportJSON($students, $fields);
    } elseif ($format === 'pdf') {
        exportPDF($students, $fields);
    }
}

function exportCSV($students, $fields) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d_His') . '.csv"');

    // Build header
    $headers = ['First Name', 'Last Name', 'Username'];
    if (in_array('email', $fields)) $headers[] = 'Email';
    if (in_array('grade', $fields)) $headers[] = 'Grade Level';
    if (in_array('section', $fields)) $headers[] = 'Section';
    if (in_array('password', $fields)) $headers[] = 'Password';

    // Output header
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);

    // Output data
    foreach ($students as $student) {
        $row = [
            $student['first_name'],
            $student['last_name'],
            $student['username']
        ];
        if (in_array('email', $fields)) $row[] = $student['email'];
        if (in_array('grade', $fields)) $row[] = $student['grade_level'];
        if (in_array('section', $fields)) $row[] = $student['section'] ?? '';
        if (in_array('password', $fields)) $row[] = $student['password'];
        
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

function exportExcel($students, $fields) {
    // Check if PhpSpreadsheet is available
    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($composerAutoload)) {
        // Fallback to CSV if PhpSpreadsheet is not available
        exportCSV($students, $fields);
        return;
    }

    require_once $composerAutoload;

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Build header
    $headers = ['First Name', 'Last Name', 'Username'];
    if (in_array('email', $fields)) $headers[] = 'Email';
    if (in_array('grade', $fields)) $headers[] = 'Grade Level';
    if (in_array('section', $fields)) $headers[] = 'Section';
    if (in_array('password', $fields)) $headers[] = 'Password';

    // Add header row
    foreach ($headers as $col => $header) {
        $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
    }

    // Add data rows
    foreach ($students as $rowIndex => $student) {
        $row = [
            $student['first_name'],
            $student['last_name'],
            $student['username']
        ];
        if (in_array('email', $fields)) $row[] = $student['email'];
        if (in_array('grade', $fields)) $row[] = $student['grade_level'];
        if (in_array('section', $fields)) $row[] = $student['section'] ?? '';
        if (in_array('password', $fields)) $row[] = $student['password'];

        foreach ($row as $col => $value) {
            $sheet->setCellValueByColumnAndRow($col + 1, $rowIndex + 2, $value);
        }
    }

    // Auto-fit columns
    foreach ($headers as $col => $header) {
        $sheet->getColumnDimensionByColumn($col + 1)->setAutoSize(true);
    }

    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d_His') . '.xlsx"');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

function exportJSON($students, $fields) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d_His') . '.json"');

    $output = [];
    foreach ($students as $student) {
        $record = [
            'first_name' => $student['first_name'],
            'last_name' => $student['last_name'],
            'username' => $student['username']
        ];
        if (in_array('email', $fields)) $record['email'] = $student['email'];
        if (in_array('grade', $fields)) $record['grade_level'] = $student['grade_level'];
        if (in_array('section', $fields)) $record['section'] = $student['section'];
        if (in_array('password', $fields)) $record['password'] = $student['password'];

        $output[] = $record;
    }

    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

function exportPDF($students, $fields) {
    // Check if TCPDF is available
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        // Fallback to CSV if TCPDF is not available
        exportCSV($students, $fields);
        return;
    }

    require __DIR__ . '/../vendor/autoload.php';

    $pdf = new \TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 15, 'Student Export Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Ln(5);

    // Build table header
    $pdf->SetFont('helvetica', 'B', 10);
    $headers = ['First Name', 'Last Name', 'Username'];
    if (in_array('email', $fields)) $headers[] = 'Email';
    if (in_array('grade', $fields)) $headers[] = 'Grade';
    if (in_array('section', $fields)) $headers[] = 'Section';
    if (in_array('password', $fields)) $headers[] = 'Password';

    foreach ($headers as $header) {
        $pdf->Cell(30, 7, $header, 1);
    }
    $pdf->Ln();

    // Add data
    $pdf->SetFont('helvetica', '', 9);
    foreach ($students as $student) {
        $row = [
            $student['first_name'],
            $student['last_name'],
            $student['username']
        ];
        if (in_array('email', $fields)) $row[] = substr($student['email'], 0, 15);
        if (in_array('grade', $fields)) $row[] = $student['grade_level'];
        if (in_array('section', $fields)) $row[] = $student['section'] ?? '';
        if (in_array('password', $fields)) $row[] = substr($student['password'], 0, 8);

        foreach ($row as $value) {
            $pdf->Cell(30, 7, $value, 1);
        }
        $pdf->Ln();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d_His') . '.pdf"');

    $pdf->Output('php://output', 'D');
    exit();
}

function handleArchive($pdo) {
    $currentYear = $_POST['current_year'] ?? '';
    $nextYear = $_POST['next_year'] ?? '';
    $promoteGrades = json_decode($_POST['promote_grades'] ?? '[]', true);
    $notes = $_POST['notes'] ?? '';

    header('Content-Type: application/json');

    if (!$currentYear || !$nextYear || empty($promoteGrades)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Create archive record
        $schoolYear = $currentYear . ' → ' . $nextYear;
        $stmt = $pdo->prepare("INSERT INTO student_archives (school_year, archive_status, archived_by, notes, archived_date) 
                             VALUES (?, 'pending', ?, ?, NOW())");
        $stmt->execute([$schoolYear, $_SESSION['user_id'], $notes]);
        $archiveId = $pdo->lastInsertId();

        $totalArchived = 0;

        // For each promoted grade
        foreach ($promoteGrades as $currentGrade) {
            $currentGrade = trim($currentGrade);
            
            if ($currentGrade === '10') {
                // Grade 10 students are graduating/removed
                $stmt = $pdo->prepare("
                    SELECT user_id, first_name, last_name, username, email, grade_level, section, password 
                    FROM users WHERE role_id = 4 AND grade_level = ?
                ");
                $stmt->execute([$currentGrade]);
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($students as $student) {
                    // Archive the student record
                    $archiveStmt = $pdo->prepare("
                        INSERT INTO student_archive_records 
                        (archive_id, user_id, first_name, last_name, username, email, grade_level, section, password, action) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'graduated')
                    ");
                    $archiveStmt->execute([
                        $archiveId,
                        $student['user_id'],
                        $student['first_name'],
                        $student['last_name'],
                        $student['username'],
                        $student['email'],
                        $student['grade_level'],
                        $student['section'],
                        $student['password']
                    ]);

                    // Deactivate or delete the student account
                    $deleteStmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $deleteStmt->execute([$student['user_id']]);
                    
                    $totalArchived++;
                }
            } else {
                // Promote to next grade
                $nextGrade = (int)$currentGrade + 1;
                
                $stmt = $pdo->prepare("
                    SELECT user_id, first_name, last_name, username, email, grade_level, section, password 
                    FROM users WHERE role_id = 4 AND grade_level = ?
                ");
                $stmt->execute([$currentGrade]);
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($students as $student) {
                    // Archive the current record
                    $archiveStmt = $pdo->prepare("
                        INSERT INTO student_archive_records 
                        (archive_id, user_id, first_name, last_name, username, email, grade_level, section, password, action) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'promoted')
                    ");
                    $archiveStmt->execute([
                        $archiveId,
                        $student['user_id'],
                        $student['first_name'],
                        $student['last_name'],
                        $student['username'],
                        $student['email'],
                        $student['grade_level'],
                        $student['section'],
                        $student['password']
                    ]);

                    // Update student's grade level
                    $updateStmt = $pdo->prepare("UPDATE users SET grade_level = ? WHERE user_id = ?");
                    $updateStmt->execute([$nextGrade, $student['user_id']]);
                    
                    $totalArchived++;
                }
            }
        }

        // Update archive status
        $updateStmt = $pdo->prepare("UPDATE student_archives SET archive_status = 'completed' WHERE archive_id = ?");
        $updateStmt->execute([$archiveId]);

        $pdo->commit();

        log_activity('Archived students', $archiveId);

        echo json_encode([
            'success' => true,
            'archive_id' => $archiveId,
            'count' => $totalArchived,
            'message' => 'Students archived and transitioned successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Error archiving students: ' . $e->getMessage()
        ]);
    }
}

function handleGetDetails($pdo) {
    $archiveId = $_POST['archive_id'] ?? 0;

    header('Content-Type: application/json');

    try {
        $stmt = $pdo->prepare("SELECT archive_id, school_year, archive_status, archived_date, notes FROM student_archives WHERE archive_id = ?");
        $stmt->execute([$archiveId]);
        $archive = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$archive) {
            echo json_encode(['success' => false, 'message' => 'Archive not found']);
            exit();
        }

        // Get grade distribution
        $stmt = $pdo->prepare("
            SELECT grade_level, COUNT(*) as count FROM student_archive_records 
            WHERE archive_id = ? GROUP BY grade_level ORDER BY grade_level
        ");
        $stmt->execute([$archiveId]);
        $gradeDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_archive_records WHERE archive_id = ?");
        $stmt->execute([$archiveId]);
        $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        echo json_encode([
            'success' => true,
            'archive_id' => $archive['archive_id'],
            'school_year' => $archive['school_year'],
            'archive_status' => $archive['archive_status'],
            'archived_date' => $archive['archived_date'],
            'notes' => $archive['notes'],
            'student_count' => $totalCount,
            'grade_distribution' => $gradeDistribution
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching details: ' . $e->getMessage()
        ]);
    }
}

function handleGetStudents($pdo) {
    $archiveId = $_POST['archive_id'] ?? 0;
    $gradeLevel = $_POST['grade_level'] ?? null;

    header('Content-Type: application/json');

    try {
        // Verify archive exists
        $stmt = $pdo->prepare("SELECT archive_id FROM student_archives WHERE archive_id = ?");
        $stmt->execute([$archiveId]);
        $archive = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$archive) {
            echo json_encode(['success' => false, 'message' => 'Archive not found']);
            exit();
        }

        // Get students for this archive
        if ($gradeLevel) {
            $stmt = $pdo->prepare("
                SELECT first_name, last_name, username, email, grade_level, section, action
                FROM student_archive_records 
                WHERE archive_id = ? AND grade_level = ?
                ORDER BY grade_level, last_name, first_name
            ");
            $stmt->execute([$archiveId, $gradeLevel]);
        } else {
            $stmt = $pdo->prepare("
                SELECT first_name, last_name, username, email, grade_level, section, action
                FROM student_archive_records 
                WHERE archive_id = ?
                ORDER BY grade_level, last_name, first_name
            ");
            $stmt->execute([$archiveId]);
        }
        
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'students' => $students
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching students: ' . $e->getMessage()
        ]);
    }
}
?>
