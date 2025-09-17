<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lars_db";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

echo "<h1>Debugging Profile Points Issue</h1>\n";

// Find Jasmine Bautista
$userStmt = $pdo->prepare("SELECT user_id, first_name, last_name, grade_level FROM users WHERE first_name = 'Jasmine' AND last_name = 'Bautista' AND role_id = 4");
$userStmt->execute();
$jasmine = $userStmt->fetch(PDO::FETCH_ASSOC);

if ($jasmine) {
    echo "<h2>Found User: {$jasmine['first_name']} {$jasmine['last_name']}</h2>\n";
    echo "<p><strong>User ID:</strong> {$jasmine['user_id']}</p>\n";
    echo "<p><strong>Grade Level:</strong> {$jasmine['grade_level']}</p>\n";
    
    // Check her submissions
    echo "<h3>Student Submissions for Jasmine:</h3>\n";
    $submissionsStmt = $pdo->prepare("
        SELECT 
            ss.submission_id,
            ss.activity_id,
            ss.student_id,
            ss.submission_status,
            ss.total_score,
            ss.percentage,
            a.activity_title,
            s.subject_name,
            s.grade_level as subject_grade
        FROM student_submissions ss
        JOIN activities a ON ss.activity_id = a.activity_id
        JOIN subjects s ON a.subject_id = s.subject_id
        WHERE ss.student_id = ?
        ORDER BY ss.submission_id
    ");
    $submissionsStmt->execute([$jasmine['user_id']]);
    $submissions = $submissionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($submissions) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Submission ID</th><th>Activity</th><th>Subject</th><th>Subject Grade</th><th>Status</th><th>Total Score</th><th>Percentage</th></tr>\n";
        
        $totalPoints = 0;
        foreach ($submissions as $sub) {
            echo "<tr>\n";
            echo "<td>{$sub['submission_id']}</td>\n";
            echo "<td>{$sub['activity_title']}</td>\n";
            echo "<td>{$sub['subject_name']}</td>\n";
            echo "<td>{$sub['subject_grade']}</td>\n";
            echo "<td>{$sub['submission_status']}</td>\n";
            echo "<td>{$sub['total_score']}</td>\n";
            echo "<td>{$sub['percentage']}%</td>\n";
            echo "</tr>\n";
            
            if (in_array($sub['submission_status'], ['submitted', 'graded']) && $sub['subject_grade'] == $jasmine['grade_level']) {
                $totalPoints += $sub['total_score'];
            }
        }
        echo "</table>\n";
        echo "<p><strong>Manual calculation of total points (grade {$jasmine['grade_level']} activities only):</strong> {$totalPoints}</p>\n";
    } else {
        echo "<p>No submissions found for Jasmine</p>\n";
    }
    
    // Test the current userStatsStmt query
    echo "<h3>Current userStatsStmt Query Result:</h3>\n";
    $userStatsStmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = ? THEN ss.total_score ELSE 0 END), 0) as total_points,
            COUNT(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = ? THEN 1 END) as completed_activities,
            COUNT(CASE WHEN a.activity_id IS NOT NULL AND s.grade_level = ? THEN 1 END) as total_available_activities
        FROM student_submissions ss
        JOIN activities a ON ss.activity_id = a.activity_id
        JOIN subjects s ON a.subject_id = s.subject_id
        WHERE ss.student_id = ?
    ");
    $userStatsStmt->execute([$jasmine['grade_level'], $jasmine['grade_level'], $jasmine['grade_level'], $jasmine['user_id']]);
    $userStats = $userStatsStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userStats) {
        echo "<p><strong>Query Result - Total Points:</strong> {$userStats['total_points']}</p>\n";
        echo "<p><strong>Query Result - Completed Activities:</strong> {$userStats['completed_activities']}</p>\n";
        echo "<p><strong>Query Result - Total Available:</strong> {$userStats['total_available_activities']}</p>\n";
    } else {
        echo "<p>No results from userStatsStmt query</p>\n";
    }
    
    // Test a simpler query
    echo "<h3>Simple Direct Query:</h3>\n";
    $simpleStmt = $pdo->prepare("
        SELECT 
            SUM(ss.total_score) as simple_total
        FROM student_submissions ss
        WHERE ss.student_id = ? AND ss.submission_status IN ('submitted', 'graded')
    ");
    $simpleStmt->execute([$jasmine['user_id']]);
    $simpleResult = $simpleStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($simpleResult) {
        echo "<p><strong>Simple query total:</strong> {$simpleResult['simple_total']}</p>\n";
    }
    
} else {
    echo "<p>Jasmine Bautista not found in users table</p>\n";
    
    // List all Grade 7 students
    $allGrade7Stmt = $pdo->prepare("SELECT user_id, first_name, last_name FROM users WHERE role_id = 4 AND grade_level = 7 ORDER BY first_name, last_name");
    $allGrade7Stmt->execute();
    $grade7Students = $allGrade7Stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Grade 7 Students:</h3>\n";
    foreach ($grade7Students as $student) {
        echo "<p>ID: {$student['user_id']} - {$student['first_name']} {$student['last_name']}</p>\n";
    }
}
?>