<?php
require_once __DIR__ . '/Database/database.php';

try {
    echo "<h2>Activities System Diagnostic</h2>";

    // 1. Check if activities table exists and its structure
    $stmt = $pdo->query("SHOW TABLES LIKE 'activities'");
    echo "<h3>1. Activities Table Check:</h3>";
    if ($stmt->fetch()) {
        echo "✓ Activities table exists<br>";
        $stmt = $pdo->query("SHOW COLUMNS FROM activities");
        echo "Columns:<br><pre>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['Field']} - {$row['Type']}\n";
        }
        echo "</pre>";
    } else {
        echo "❌ Activities table does not exist<br>";
    }

    // 2. Check active activities
    echo "<h3>2. Active Activities:</h3>";
    $stmt = $pdo->query("
        SELECT 
            a.activity_id,
            a.title,
            a.is_active,
            s.subject_name,
            CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
            COUNT(ss.submission_id) as submission_count
        FROM activities a
        JOIN subjects s ON a.subject_id = s.subject_id
        JOIN users u ON a.teacher_id = u.user_id
        LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id
        WHERE a.is_active = 1
        GROUP BY a.activity_id
    ");
    
    echo "<table border='1'>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Active</th>
            <th>Teacher</th>
            <th>Subject</th>
            <th>Submissions</th>
        </tr>";
    
    $hasActivities = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hasActivities = true;
        echo "<tr>
            <td>{$row['activity_id']}</td>
            <td>{$row['title']}</td>
            <td>{$row['is_active']}</td>
            <td>{$row['teacher_name']}</td>
            <td>{$row['subject_name']}</td>
            <td>{$row['submission_count']}</td>
        </tr>";
    }
    
    if (!$hasActivities) {
        echo "<tr><td colspan='6'>No active activities found</td></tr>";
    }
    echo "</table>";

    // 3. Test the student activities API endpoint
    echo "<h3>3. Testing Student Activities API:</h3>";
    $testStudentId = 14; // Use an actual student ID from your database
    
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            s.subject_name,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            COALESCE(ss.submission_status, 'not_started') as submission_status
        FROM activities a
        JOIN subjects s ON a.subject_id = s.subject_id
        JOIN users t ON a.teacher_id = t.user_id
        LEFT JOIN student_submissions ss ON (a.activity_id = ss.activity_id AND ss.student_id = ?)
        WHERE a.is_active = 1
    ");
    
    $stmt->execute([$testStudentId]);
    $testActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Activities available for student ID $testStudentId:<br><pre>";
    print_r($testActivities);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>