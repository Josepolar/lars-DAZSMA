<?php
session_start();
include '../Database/database.php';

// Check if user is logged in as teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    die('Not authorized');
}

$teacher_id = $_SESSION['user_id'];

// Get teacher's subjects
$subjectsStmt = $pdo->prepare("
    SELECT s.subject_id, s.subject_name 
    FROM subjects s
    INNER JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
    WHERE ts.teacher_id = ?
    ORDER BY s.subject_name
");
$subjectsStmt->execute([$teacher_id]);
$subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Your Subjects:</h2>";
echo "<pre>";
print_r($subjects);
echo "</pre>";

// Test creating a game
if (isset($_POST['test_create'])) {
    $title = "Test Game " . time();
    $description = "Test Description";
    $subject_id = $_POST['subject_id'];
    $time_limit = 30;
    $default_points = 100;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $show_leaderboard = 1;
    
    try {
        $query = "INSERT INTO game_activities (subject_id, teacher_id, title, description, time_limit, default_points, due_date, show_leaderboard, status, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$subject_id, $teacher_id, $title, $description, $time_limit, $default_points, $due_date, $show_leaderboard]);
        
        $game_id = $pdo->lastInsertId();
        
        echo "<h2 style='color: green;'>SUCCESS! Game created with ID: $game_id</h2>";
        
        // Show the created game
        $checkStmt = $pdo->prepare("SELECT * FROM game_activities WHERE game_id = ?");
        $checkStmt->execute([$game_id]);
        $game = $checkStmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($game);
        echo "</pre>";
        
    } catch (Exception $e) {
        echo "<h2 style='color: red;'>ERROR: " . $e->getMessage() . "</h2>";
    }
}
?>

<h2>Test Game Creation</h2>
<form method="POST">
    <label>Select Subject:</label><br>
    <select name="subject_id" required>
        <option value="">Select a subject</option>
        <?php foreach ($subjects as $subject): ?>
            <option value="<?php echo $subject['subject_id']; ?>">
                <?php echo htmlspecialchars($subject['subject_name']); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>
    
    <label>Due Date (optional):</label><br>
    <input type="datetime-local" name="due_date"><br><br>
    
    <button type="submit" name="test_create">Test Create Game</button>
</form>

<br><br>
<a href="teacher-acts.php">Back to Activities</a>
