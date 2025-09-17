<?php
// Add some test submissions for other grade levels to demonstrate the leaderboard system
$pdo = new PDO("mysql:host=localhost;dbname=lars_db", "root", "");

// Create a test activity for Grade 8 and add some submissions
echo "Creating test data for Grade 8...\n";

// First check if there are activities for other grades
$stmt = $pdo->query("
    SELECT a.activity_id, a.title, s.subject_name, s.grade_level 
    FROM activities a 
    JOIN subjects s ON a.subject_id = s.subject_id 
    WHERE s.grade_level != '7' AND a.is_active = 1
");
$activities = $stmt->fetchAll();

if (count($activities) > 0) {
    echo "Found activities for other grades:\n";
    foreach($activities as $activity) {
        echo "- {$activity['title']} in {$activity['subject_name']} (Grade {$activity['grade_level']})\n";
        
        // Add some test submissions for the first activity of each grade
        if ($activity['grade_level'] == '8') {
            // Add submissions for Grade 8 students
            $grade8_students = [26, 27, 28]; // First few Grade 8 student IDs
            foreach($grade8_students as $i => $student_id) {
                $score = ($i + 1) * 15; // Give different scores
                $percentage = ($score / 100) * 100;
                
                // Check if submission already exists
                $checkStmt = $pdo->prepare("SELECT submission_id FROM student_submissions WHERE student_id = ? AND activity_id = ?");
                $checkStmt->execute([$student_id, $activity['activity_id']]);
                
                if ($checkStmt->rowCount() == 0) {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO student_submissions (student_id, activity_id, submission_status, total_score, max_score, percentage, submitted_at) 
                        VALUES (?, ?, 'submitted', ?, 100, ?, NOW())
                    ");
                    $insertStmt->execute([$student_id, $activity['activity_id'], $score, $percentage]);
                    echo "  Added submission for student $student_id: $score points\n";
                }
            }
        }
    }
} else {
    echo "No activities found for other grades. Creating a test activity for Grade 8...\n";
    
    // Get Grade 8 subject (ENGLISH 8)
    $stmt = $pdo->query("SELECT subject_id FROM subjects WHERE grade_level = '8' LIMIT 1");
    $subject = $stmt->fetch();
    
    if ($subject) {
        // Create a test activity for Grade 8
        $stmt = $pdo->prepare("
            INSERT INTO activities (title, description, teacher_id, subject_id, activity_type, total_points, is_active) 
            VALUES ('Grade 8 Test Activity', 'Test activity for Grade 8', 8, ?, 'quiz', 100, 1)
        ");
        $stmt->execute([$subject['subject_id']]);
        $new_activity_id = $pdo->lastInsertId();
        
        echo "Created new activity for Grade 8 (ID: $new_activity_id)\n";
        
        // Add test submissions
        $grade8_students = [26, 27, 28];
        foreach($grade8_students as $i => $student_id) {
            $score = ($i + 1) * 20;
            $percentage = ($score / 100) * 100;
            
            $insertStmt = $pdo->prepare("
                INSERT INTO student_submissions (student_id, activity_id, submission_status, total_score, max_score, percentage, submitted_at) 
                VALUES (?, ?, 'submitted', ?, 100, ?, NOW())
            ");
            $insertStmt->execute([$student_id, $new_activity_id, $score, $percentage]);
            echo "  Added submission for student $student_id: $score points\n";
        }
    }
}

echo "\nTesting leaderboard for all grades:\n";

foreach(['7', '8', '9', '10'] as $grade) {
    echo "\nGrade $grade Leaderboard:\n";
    $leaderboardStmt = $pdo->prepare("
        SELECT 
            CONCAT(u.first_name, ' ', u.last_name) as full_name,
            COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level THEN ss.total_score ELSE 0 END), 0) as total_points,
            COUNT(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level THEN 1 END) as completed_activities
        FROM users u
        LEFT JOIN student_submissions ss ON u.user_id = ss.student_id
        LEFT JOIN activities a ON ss.activity_id = a.activity_id
        LEFT JOIN subjects s ON a.subject_id = s.subject_id
        WHERE u.role_id = 4 AND u.grade_level = ?
        GROUP BY u.user_id, u.first_name, u.last_name
        ORDER BY total_points DESC
        LIMIT 5
    ");
    $leaderboardStmt->execute([$grade]);
    $leaderboard = $leaderboardStmt->fetchAll();
    
    foreach($leaderboard as $index => $student) {
        $rank = $index + 1;
        echo "  $rank. {$student['full_name']} - {$student['total_points']} points ({$student['completed_activities']} activities)\n";
    }
}
?>