<?php
session_start();
include '../../Database/database.php';

// Check if user is STUDENT (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    echo "<h2>NOT LOGGED IN</h2>";
    echo "<p>Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "</p>";
    echo "<p>Session role_id: " . (isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 'NOT SET') . "</p>";
    exit();
}

$student_id = $_SESSION['user_id'];

// Get student's info
$student_query = "SELECT user_id, first_name, last_name, grade_level, role_id FROM users WHERE user_id = ?";
$student_stmt = $pdo->prepare($student_query);
$student_stmt->execute([$student_id]);
$student = $student_stmt->fetch(PDO::FETCH_ASSOC);
$student_grade = $student['grade_level'];
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/png" href="../../assets/tablogo.png">
    <title>Game Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .yes { background-color: #90EE90; font-weight: bold; }
        .no { background-color: #FFB6C6; }
        .highlight { background-color: yellow; }
    </style>
</head>
<body>
    <h1>🔍 Game Activity Debug Information</h1>
    
    <h2>Student Information</h2>
    <table>
        <tr><th>Field</th><th>Value</th></tr>
        <tr><td>User ID</td><td><?php echo $student['user_id']; ?></td></tr>
        <tr><td>Name</td><td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td></tr>
        <tr><td>Grade Level</td><td class="highlight"><?php echo $student_grade; ?> (Type: <?php echo gettype($student_grade); ?>)</td></tr>
        <tr><td>Role ID</td><td><?php echo $student['role_id']; ?></td></tr>
    </table>

<?php
// Check all games in database
$all_games_query = "SELECT ga.game_id, ga.title, ga.status, ga.subject_id, s.subject_name, s.grade_level
                    FROM game_activities ga
                    INNER JOIN subjects s ON ga.subject_id = s.subject_id
                    ORDER BY ga.created_at DESC";
$all_stmt = $pdo->prepare($all_games_query);
$all_stmt->execute();
$all_games = $all_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <h2>All Games in Database (<?php echo count($all_games); ?> total)</h2>
    <table>
        <tr>
            <th>Game ID</th>
            <th>Title</th>
            <th>Status</th>
            <th>Subject ID</th>
            <th>Subject Name</th>
            <th>Subject Grade</th>
            <th>Grade Type</th>
            <th>Match Student?</th>
            <th>Status OK?</th>
        </tr>
        <?php foreach ($all_games as $game): 
            $grade_match = ($game['grade_level'] == $student_grade);
            $status_match = ($game['status'] == 'active');
            $both_match = $grade_match && $status_match;
        ?>
        <tr class="<?php echo $both_match ? 'yes' : 'no'; ?>">
            <td><?php echo $game['game_id']; ?></td>
            <td><?php echo htmlspecialchars($game['title']); ?></td>
            <td><strong><?php echo $game['status']; ?></strong></td>
            <td><?php echo $game['subject_id']; ?></td>
            <td><?php echo htmlspecialchars($game['subject_name']); ?></td>
            <td class="highlight"><?php echo $game['grade_level']; ?></td>
            <td><?php echo gettype($game['grade_level']); ?></td>
            <td class="<?php echo $grade_match ? 'yes' : 'no'; ?>">
                <?php echo $grade_match ? '✓ YES' : '✗ NO'; ?>
                (<?php echo $game['grade_level']; ?> == <?php echo $student_grade; ?>)
            </td>
            <td class="<?php echo $status_match ? 'yes' : 'no'; ?>">
                <?php echo $status_match ? '✓ ACTIVE' : '✗ ' . strtoupper($game['status']); ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

<?php
// Now run the actual query used in available-games.php
$query = "SELECT ga.*, s.subject_name, s.grade_level as subject_grade, CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
          (SELECT COUNT(*) FROM game_questions WHERE game_id = ga.game_id) as question_count
          FROM game_activities ga
          INNER JOIN subjects s ON ga.subject_id = s.subject_id
          INNER JOIN users u ON ga.teacher_id = u.user_id
          WHERE s.grade_level = ? AND ga.status = 'active'
          ORDER BY ga.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$student_grade]);
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <h2>Query Results (Games Student Should See)</h2>
    <p><strong>SQL Query:</strong></p>
    <pre style="background: #f4f4f4; padding: 10px; border-radius: 5px;">
WHERE s.grade_level = '<?php echo $student_grade; ?>' AND ga.status = 'active'
    </pre>
    <p><strong>Games Found:</strong> <?php echo count($games); ?></p>
    
    <?php if (count($games) > 0): ?>
        <table>
            <tr>
                <th>Game ID</th>
                <th>Title</th>
                <th>Subject</th>
                <th>Teacher</th>
                <th>Questions</th>
                <th>Status</th>
            </tr>
            <?php foreach ($games as $game): ?>
            <tr>
                <td><?php echo $game['game_id']; ?></td>
                <td><?php echo htmlspecialchars($game['title']); ?></td>
                <td><?php echo htmlspecialchars($game['subject_name']); ?></td>
                <td><?php echo htmlspecialchars($game['teacher_name']); ?></td>
                <td><?php echo $game['question_count']; ?></td>
                <td><?php echo $game['status']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p style="color: red; font-weight: bold;">❌ NO GAMES FOUND - This is why the student sees "No Games Available"</p>
    <?php endif; ?>

    <h2>Recommendations</h2>
    <ul>
        <li>Check if any games have status = 'active' (should be green in table above)</li>
        <li>Check if grade levels match exactly (compare highlighted columns)</li>
        <li>Make sure the teacher has activated the game after creation</li>
    </ul>

    <p><a href="available-games.php" style="padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">← Back to Available Games</a></p>
</body>
</html>
