<?php
// Database connection
include 'Database/database.php';

echo "<h1>Database Table Structure Analysis</h1>\n";

// Check activities table structure
echo "<h2>Activities Table Structure:</h2>\n";
$stmt = $pdo->query("DESCRIBE activities");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<ul>\n";
foreach ($columns as $col) {
    echo "<li>{$col['Field']} - {$col['Type']}</li>\n";
}
echo "</ul>\n";

// Check student_submissions table structure
echo "<h2>Student Submissions Table Structure:</h2>\n";
$stmt = $pdo->query("DESCRIBE student_submissions");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<ul>\n";
foreach ($columns as $col) {
    echo "<li>{$col['Field']} - {$col['Type']}</li>\n";
}
echo "</ul>\n";

// Check subjects table structure
echo "<h2>Subjects Table Structure:</h2>\n";
$stmt = $pdo->query("DESCRIBE subjects");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<ul>\n";
foreach ($columns as $col) {
    echo "<li>{$col['Field']} - {$col['Type']}</li>\n";
}
echo "</ul>\n";

// Find Jasmine Bautista
echo "<h2>Jasmine Bautista's Data:</h2>\n";
$userStmt = $pdo->prepare("SELECT user_id, first_name, last_name, grade_level FROM users WHERE first_name = 'Jasmine' AND last_name = 'Bautista' AND role_id = 4");
$userStmt->execute();
$jasmine = $userStmt->fetch(PDO::FETCH_ASSOC);

if ($jasmine) {
    echo "<p><strong>User ID:</strong> {$jasmine['user_id']}</p>\n";
    echo "<p><strong>Grade Level:</strong> {$jasmine['grade_level']}</p>\n";
    
    // Check her submissions with corrected column names
    echo "<h3>Jasmine's Submissions (simplified):</h3>\n";
    $submissionsStmt = $pdo->prepare("
        SELECT 
            ss.submission_id,
            ss.activity_id,
            ss.student_id,
            ss.submission_status,
            ss.total_score,
            ss.percentage
        FROM student_submissions ss
        WHERE ss.student_id = ?
        ORDER BY ss.submission_id
    ");
    $submissionsStmt->execute([$jasmine['user_id']]);
    $submissions = $submissionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($submissions) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Submission ID</th><th>Activity ID</th><th>Status</th><th>Total Score</th><th>Percentage</th></tr>\n";
        
        $totalPoints = 0;
        foreach ($submissions as $sub) {
            echo "<tr>\n";
            echo "<td>{$sub['submission_id']}</td>\n";
            echo "<td>{$sub['activity_id']}</td>\n";
            echo "<td>{$sub['submission_status']}</td>\n";
            echo "<td>{$sub['total_score']}</td>\n";
            echo "<td>{$sub['percentage']}%</td>\n";
            echo "</tr>\n";
            
            if (in_array($sub['submission_status'], ['submitted', 'graded'])) {
                $totalPoints += $sub['total_score'];
            }
        }
        echo "</table>\n";
        echo "<p><strong>Total Points from all submissions:</strong> {$totalPoints}</p>\n";
    } else {
        echo "<p>No submissions found for Jasmine</p>\n";
    }
}
?>