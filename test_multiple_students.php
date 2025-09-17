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

echo "<h1>Testing Multiple Students for Points Display</h1>\n";

// Get all students with submissions
$studentsWithSubmissions = $pdo->query("
    SELECT DISTINCT 
        u.user_id, 
        u.first_name, 
        u.last_name, 
        u.grade_level,
        COUNT(ss.submission_id) as submission_count,
        SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') THEN ss.total_score ELSE 0 END) as total_points
    FROM users u
    JOIN student_submissions ss ON u.user_id = ss.student_id
    WHERE u.role_id = 4
    GROUP BY u.user_id, u.first_name, u.last_name, u.grade_level
    HAVING total_points > 0
    ORDER BY total_points DESC
")->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Students with Points (Database Check):</h2>\n";
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>User ID</th><th>Name</th><th>Grade</th><th>Submissions</th><th>Total Points</th><th>Profile Test</th></tr>\n";

foreach ($studentsWithSubmissions as $student) {
    session_start();
    $_SESSION['user_id'] = $student['user_id'];
    $_SESSION['role_id'] = 4;
    
    // Test the profile display for this student
    ob_start();
    include 'students/student-home.php';
    $content = ob_get_clean();
    
    // Extract points from profile
    preg_match('/<span class="points" id="totalPoints">([^<]+)<\/span>/', $content, $pointsMatch);
    $profilePoints = isset($pointsMatch[1]) ? $pointsMatch[1] : 'NOT FOUND';
    
    $status = ($profilePoints == $student['total_points']) ? '✅ Match' : '❌ Mismatch';
    
    echo "<tr>\n";
    echo "<td>{$student['user_id']}</td>\n";
    echo "<td>{$student['first_name']} {$student['last_name']}</td>\n";
    echo "<td>{$student['grade_level']}</td>\n";
    echo "<td>{$student['submission_count']}</td>\n";
    echo "<td>{$student['total_points']}</td>\n";
    echo "<td>Profile: {$profilePoints} - {$status}</td>\n";
    echo "</tr>\n";
    
    session_destroy();
}

echo "</table>\n";

// Check if there are any data type issues
echo "<h2>Data Type Analysis:</h2>\n";
$dataTypeStmt = $pdo->query("
    SELECT 
        ss.total_score, 
        TYPEOF(ss.total_score) as score_type,
        ss.submission_status
    FROM student_submissions ss 
    WHERE ss.student_id = 16 
    AND ss.submission_status IN ('submitted', 'graded')
");
$dataTypes = $dataTypeStmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Total Score</th><th>Type</th><th>Status</th></tr>\n";
foreach ($dataTypes as $row) {
    echo "<tr>\n";
    echo "<td>{$row['total_score']}</td>\n";
    echo "<td>{$row['score_type']}</td>\n";
    echo "<td>{$row['submission_status']}</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";
?>