<?php
$pdo = new PDO("mysql:host=localhost;dbname=lars_db", "root", "");

$result = $pdo->query("SELECT NOW() as mysql_time");
$mysql_time = $result->fetchColumn();

echo "MySQL NOW(): $mysql_time\n";
echo "PHP time: " . date('Y-m-d H:i:s') . "\n";

// Test the due date comparison
echo "\nTesting due date comparison:\n";
$stmt = $pdo->query("
    SELECT a.activity_id, a.title, a.due_date, 
           CASE WHEN a.due_date > NOW() THEN 'FUTURE' ELSE 'PAST' END as status
    FROM activities a 
    JOIN subjects s ON a.subject_id = s.subject_id 
    WHERE s.grade_level = '7'
");
while($row = $stmt->fetch()) {
    echo "Activity: {$row['title']}, Due: {$row['due_date']}, Status: {$row['status']}\n";
}
?>