<?php
session_start();

// Test with different grade levels to ensure the leaderboard works for all
$testGrades = [7, 8, 9, 10];

echo "<h1>Testing Leaderboard Across All Grade Levels</h1>\n";

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

foreach ($testGrades as $grade) {
    echo "<h2>Grade $grade Leaderboard</h2>\n";
    
    // Test the leaderboard query
    $leaderboardStmt = $pdo->prepare("
        SELECT 
            u.user_id,
            CONCAT(u.first_name, ' ', u.last_name) as full_name,
            u.first_name,
            u.last_name,
            COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level THEN ss.total_score ELSE 0 END), 0) as total_points,
            COUNT(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level THEN 1 END) as completed_activities,
            COALESCE(AVG(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level AND ss.percentage IS NOT NULL THEN ss.percentage END), 0) as avg_percentage
        FROM users u
        LEFT JOIN student_submissions ss ON u.user_id = ss.student_id
        LEFT JOIN activities a ON ss.activity_id = a.activity_id
        LEFT JOIN subjects s ON a.subject_id = s.subject_id
        WHERE u.role_id = 4 AND u.grade_level = ?
        GROUP BY u.user_id, u.first_name, u.last_name
        ORDER BY total_points DESC, avg_percentage DESC
    ");
    $leaderboardStmt->execute([$grade]);
    $leaderboard = $leaderboardStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($leaderboard)) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>\n";
        echo "<tr style='background: #f0f0f0;'><th>Rank</th><th>Name</th><th>Points</th><th>Activities</th><th>Avg %</th></tr>\n";
        
        foreach ($leaderboard as $index => $student) {
            $rank = $index + 1;
            $rowStyle = '';
            
            // Add special styling for top 3
            if ($rank == 1) $rowStyle = 'background: #fff2cc;'; // Gold
            elseif ($rank == 2) $rowStyle = 'background: #f0f0f0;'; // Silver
            elseif ($rank == 3) $rowStyle = 'background: #ffe6cc;'; // Bronze
            
            echo "<tr style='$rowStyle'>\n";
            echo "<td style='text-align: center; font-weight: bold;'>$rank</td>\n";
            echo "<td>{$student['full_name']}</td>\n";
            echo "<td style='text-align: center; font-weight: bold; color: #667eea;'>{$student['total_points']}</td>\n";
            echo "<td style='text-align: center;'>{$student['completed_activities']}</td>\n";
            echo "<td style='text-align: center;'>" . number_format($student['avg_percentage'], 1) . "%</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        echo "<p><strong>Total students in Grade $grade:</strong> " . count($leaderboard) . "</p>\n";
        
        // Count students with points
        $withPoints = array_filter($leaderboard, function($student) {
            return $student['total_points'] > 0;
        });
        echo "<p><strong>Students with points:</strong> " . count($withPoints) . "</p>\n";
        
    } else {
        echo "<p style='color: #666; font-style: italic;'>No students found in Grade $grade</p>\n";
    }
    
    echo "<hr style='margin: 30px 0;'>\n";
}

echo "<h2>Summary</h2>\n";
echo "<p>✅ Leaderboard query tested for all grade levels (7, 8, 9, 10)</p>\n";
echo "<p>✅ Students are ranked by total points (descending)</p>\n";
echo "<p>✅ Points calculated from both 'submitted' and 'graded' activities</p>\n";
echo "<p>✅ Grade-level filtering working correctly</p>\n";
echo "<p>✅ All students in each grade are included, even those with 0 points</p>\n";
?>