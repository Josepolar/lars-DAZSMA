<?php
session_start();

// Turn off all error output to prevent HTML errors from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Ensure clean output
ob_clean();

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
$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            handleListActivities($pdo, $student_id);
            break;
        case 'details':
            handleActivityDetails($pdo, $student_id);
            break;
        case 'start':
            handleStartActivity($pdo, $student_id);
            break;
        case 'submit':
            handleSubmitActivity($pdo, $student_id);
            break;
        case 'subjects':
            handleSubjectActivities($pdo, $student_id);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error processing request: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

function handleListActivities($pdo, $student_id) {
    $status_filter = $_GET['status'] ?? 'all';
    $subject_filter = $_GET['subject'] ?? 'all';
    $type_filter = $_GET['type'] ?? 'all';

    // Get student's grade level
    $gradeStmt = $pdo->prepare("SELECT grade_level FROM users WHERE user_id = ?");
    $gradeStmt->execute([$student_id]);
    $student_grade = $gradeStmt->fetchColumn();

    // Build WHERE conditions - include grade level filtering
    $where_conditions = ["a.is_active = 1", "s.grade_level = ?"];
    $params = [$student_id, $student_grade];

    if ($status_filter !== 'all') {
        switch ($status_filter) {
            case 'pending':
                $where_conditions[] = "(ss.submission_status IS NULL OR ss.submission_status IN ('not_started', 'in_progress'))";
                break;
            case 'completed':
                $where_conditions[] = "(ss.submission_status IN ('submitted', 'graded'))";
                break;
            case 'overdue':
                $where_conditions[] = "(a.due_date < NOW() AND (ss.submission_status IS NULL OR ss.submission_status IN ('not_started', 'in_progress')))";
                break;
        }
    }

    if ($subject_filter !== 'all') {
        $where_conditions[] = "s.subject_id = ?";
        $params[] = $subject_filter;
    }

    if ($type_filter !== 'all') {
        $where_conditions[] = "a.activity_type = ?";
        $params[] = $type_filter;
    }

    $where_clause = implode(' AND ', $where_conditions);

    $stmt = $pdo->prepare("
        SELECT 
            a.activity_id,
            a.title,
            a.description,
            a.activity_type,
            a.total_points,
            a.time_limit,
            a.due_date,
            a.created_at,
            s.subject_name,
            s.grade_level,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            COALESCE(ss.submission_status, 'not_started') as submission_status,
            ss.total_score,
            ss.max_score,
            ss.percentage,
            ss.submitted_at,
            ss.time_spent,
            CASE 
                WHEN a.due_date IS NULL THEN 'no_deadline'
                WHEN a.due_date > NOW() THEN 'upcoming'
                ELSE 'overdue'
            END as deadline_status,
            CASE 
                WHEN a.due_date IS NOT NULL THEN TIMESTAMPDIFF(HOUR, NOW(), a.due_date)
                ELSE NULL
            END as hours_until_due
        FROM activities a
        JOIN subjects s ON a.subject_id = s.subject_id
        JOIN users t ON a.teacher_id = t.user_id
        LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
        WHERE $where_clause
        ORDER BY 
            CASE WHEN a.due_date IS NULL THEN 1 ELSE 0 END,
            a.due_date ASC,
            a.created_at DESC
    ");

    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get available subjects for filtering - only show subjects for student's grade level
    $subjectStmt = $pdo->prepare("
        SELECT DISTINCT s.subject_id, s.subject_name
        FROM subjects s
        JOIN activities a ON s.subject_id = a.subject_id
        WHERE a.is_active = 1 AND s.grade_level = ?
        ORDER BY s.subject_name
    ");
    $subjectStmt->execute([$student_grade]);
    $subjects = $subjectStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'activities' => $activities,
        'subjects' => $subjects,
        'filters' => [
            'status' => $status_filter,
            'subject' => $subject_filter,
            'type' => $type_filter
        ],
        'counts' => [
            'total' => count($activities),
            'pending' => count(array_filter($activities, fn($a) => in_array($a['submission_status'], ['not_started', 'in_progress']))),
            'completed' => count(array_filter($activities, fn($a) => in_array($a['submission_status'], ['submitted', 'graded']))),
            'overdue' => count(array_filter($activities, fn($a) => $a['deadline_status'] === 'overdue' && in_array($a['submission_status'], ['not_started', 'in_progress'])))
        ]
    ]);
}

function handleActivityDetails($pdo, $student_id) {
    $activity_id = $_GET['activity_id'] ?? null;
    
    if (!$activity_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Activity ID is required']);
        return;
    }

    // Get student's grade level
    $gradeStmt = $pdo->prepare("SELECT grade_level FROM users WHERE user_id = ?");
    $gradeStmt->execute([$student_id]);
    $student_grade = $gradeStmt->fetchColumn();

    // Get activity details with questions
    $activityStmt = $pdo->prepare("
        SELECT 
            a.*,
            s.subject_name,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            COALESCE(ss.submission_status, 'not_started') as submission_status,
            ss.submission_id,
            ss.total_score,
            ss.max_score,
            ss.percentage,
            ss.submitted_at,
            ss.time_spent
        FROM activities a
        JOIN subjects s ON a.subject_id = s.subject_id
        JOIN users t ON a.teacher_id = t.user_id
        LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
        WHERE a.activity_id = ? AND a.is_active = 1 AND s.grade_level = ?
    ");
    $activityStmt->execute([$student_id, $activity_id, $student_grade]);
    $activity = $activityStmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        http_response_code(404);
        echo json_encode(['error' => 'Activity not found']);
        return;
    }

    // Get questions for this activity
    $questionsStmt = $pdo->prepare("
        SELECT 
            aq.question_id,
            aq.question_text,
            aq.question_type,
            aq.points,
            aq.question_order
        FROM activity_questions aq
        WHERE aq.activity_id = ?
        ORDER BY aq.question_order
    ");
    $questionsStmt->execute([$activity_id]);
    $questions = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get choices for multiple choice questions
    foreach ($questions as &$question) {
        if ($question['question_type'] === 'multiple_choice') {
            $choicesStmt = $pdo->prepare("
                SELECT 
                    choice_id,
                    choice_text,
                    choice_order
                FROM question_choices
                WHERE question_id = ?
                ORDER BY choice_order
            ");
            $choicesStmt->execute([$question['question_id']]);
            $question['choices'] = $choicesStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Get student's answers if they have a submission
    $answers = [];
    if ($activity['submission_id']) {
        $answersStmt = $pdo->prepare("
            SELECT 
                sa.question_id,
                sa.choice_id,
                sa.answer_text,
                sa.points_earned,
                sa.is_correct
            FROM student_answers sa
            WHERE sa.submission_id = ?
        ");
        $answersStmt->execute([$activity['submission_id']]);
        $answersResult = $answersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($answersResult as $answer) {
            $answers[$answer['question_id']] = $answer;
        }
    }

    echo json_encode([
        'activity' => $activity,
        'questions' => $questions,
        'answers' => $answers,
        'can_take' => in_array($activity['submission_status'], ['not_started', 'in_progress']) && 
                     ($activity['due_date'] === null || strtotime($activity['due_date']) > time())
    ]);
}

function handleStartActivity($pdo, $student_id) {
    $activity_id = $_POST['activity_id'] ?? null;
    
    if (!$activity_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Activity ID is required']);
        return;
    }

    $pdo->beginTransaction();

    try {
        // Get student's grade level
        $gradeStmt = $pdo->prepare("SELECT grade_level FROM users WHERE user_id = ?");
        $gradeStmt->execute([$student_id]);
        $student_grade = $gradeStmt->fetchColumn();

        // Check if activity exists, is active, and matches student's grade level
        $activityStmt = $pdo->prepare("
            SELECT a.activity_id, a.total_points, a.due_date, a.time_limit
            FROM activities a
            JOIN subjects s ON a.subject_id = s.subject_id
            WHERE a.activity_id = ? AND a.is_active = 1 AND s.grade_level = ?
        ");
        $activityStmt->execute([$activity_id, $student_grade]);
        $activity = $activityStmt->fetch(PDO::FETCH_ASSOC);

        if (!$activity) {
            throw new Exception('Activity not found or inactive');
        }

        // Check if student already has a submission
        $submissionStmt = $pdo->prepare("
            SELECT submission_id, submission_status
            FROM student_submissions 
            WHERE activity_id = ? AND student_id = ?
        ");
        $submissionStmt->execute([$activity_id, $student_id]);
        $existing = $submissionStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing && in_array($existing['submission_status'], ['submitted', 'graded'])) {
            throw new Exception('Activity already completed');
        }

        if ($existing) {
            // Update existing submission to in_progress
            $updateStmt = $pdo->prepare("
                UPDATE student_submissions 
                SET submission_status = 'in_progress', updated_at = NOW()
                WHERE submission_id = ?
            ");
            $updateStmt->execute([$existing['submission_id']]);
            $submission_id = $existing['submission_id'];
        } else {
            // Create new submission
            $insertStmt = $pdo->prepare("
                INSERT INTO student_submissions (activity_id, student_id, submission_status, max_score)
                VALUES (?, ?, 'in_progress', ?)
            ");
            $insertStmt->execute([$activity_id, $student_id, $activity['total_points']]);
            $submission_id = $pdo->lastInsertId();
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'submission_id' => $submission_id,
            'message' => 'Activity started successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

function handleSubmitActivity($pdo, $student_id) {
    $activity_id = $_POST['activity_id'] ?? null;
    $answers_json = $_POST['answers'] ?? '{}';
    
    // Decode JSON answers
    $answers = json_decode($answers_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid answers format']);
        return;
    }
    
    if (!$activity_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Activity ID is required']);
        return;
    }

    $pdo->beginTransaction();

    try {
        // Get submission
        $submissionStmt = $pdo->prepare("
            SELECT submission_id, submission_status
            FROM student_submissions 
            WHERE activity_id = ? AND student_id = ?
        ");
        $submissionStmt->execute([$activity_id, $student_id]);
        $submission = $submissionStmt->fetch(PDO::FETCH_ASSOC);

        if (!$submission || $submission['submission_status'] === 'submitted') {
            throw new Exception('Invalid submission');
        }

        $submission_id = $submission['submission_id'];

        // Delete existing answers
        $deleteAnswersStmt = $pdo->prepare("DELETE FROM student_answers WHERE submission_id = ?");
        $deleteAnswersStmt->execute([$submission_id]);

        $total_score = 0;

        // Process answers
        foreach ($answers as $question_id => $answer) {
            $points_earned = 0;
            $is_correct = false;
            $choice_id = null;
            $answer_text = null;

            // Get question details
            $questionStmt = $pdo->prepare("
                SELECT question_type, points
                FROM activity_questions 
                WHERE question_id = ?
            ");
            $questionStmt->execute([$question_id]);
            $question = $questionStmt->fetch(PDO::FETCH_ASSOC);

            if (!$question) continue;

            if ($question['question_type'] === 'multiple_choice') {
                $choice_id = intval($answer);
                
                // Validate that this choice belongs to this question
                $choiceValidateStmt = $pdo->prepare("
                    SELECT qc.is_correct 
                    FROM question_choices qc
                    WHERE qc.choice_id = ? AND qc.question_id = ?
                ");
                $choiceValidateStmt->execute([$choice_id, $question_id]);
                $choiceData = $choiceValidateStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($choiceData) {
                    $is_correct = $choiceData['is_correct'];
                    if ($is_correct) {
                        $points_earned = $question['points'];
                    }
                } else {
                    // Invalid choice, skip this answer
                    continue;
                }
            } else {
                $answer_text = $answer;
                // For non-multiple choice, give full points (manual grading later)
                $points_earned = $question['points'];
                $is_correct = true;
            }

            // Insert answer
            $answerStmt = $pdo->prepare("
                INSERT INTO student_answers 
                (submission_id, question_id, choice_id, answer_text, points_earned, is_correct)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $answerStmt->execute([
                $submission_id, $question_id, $choice_id, $answer_text, $points_earned, $is_correct
            ]);

            $total_score += $points_earned;
        }

        // Update submission
        $updateStmt = $pdo->prepare("
            UPDATE student_submissions 
            SET submission_status = 'submitted', 
                total_score = ?, 
                percentage = (? / max_score) * 100,
                submitted_at = NOW()
            WHERE submission_id = ?
        ");
        $updateStmt->execute([$total_score, $total_score, $submission_id]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'total_score' => $total_score,
            'message' => 'Activity submitted successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

function handleSubjectActivities($pdo, $student_id) {
    $subject_id = $_GET['subject_id'] ?? null;
    
    if (!$subject_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Subject ID is required']);
        return;
    }

    // Get student's grade level
    $gradeStmt = $pdo->prepare("SELECT grade_level FROM users WHERE user_id = ?");
    $gradeStmt->execute([$student_id]);
    $student_grade = $gradeStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT 
            a.activity_id,
            a.title,
            a.activity_type,
            a.total_points,
            a.due_date,
            s.grade_level,
            COALESCE(ss.submission_status, 'not_started') as submission_status,
            ss.total_score,
            ss.percentage
        FROM activities a
        JOIN subjects s ON a.subject_id = s.subject_id
        LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id AND ss.student_id = ?
        WHERE a.subject_id = ? AND a.is_active = 1 AND s.grade_level = ?
        ORDER BY a.created_at DESC
    ");
    
    $stmt->execute([$student_id, $subject_id, $student_grade]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['activities' => $activities]);
}
?>