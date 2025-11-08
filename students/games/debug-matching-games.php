<?php
session_start();
include '../../Database/database.php';

// Check if user is STUDENT (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    die("Error: Not logged in as student");
}

$student_id = $_SESSION['user_id'];

// Get student's grade level
$student_query = "SELECT grade_level, first_name, last_name FROM users WHERE user_id = ?";
$student_stmt = $pdo->prepare($student_query);
$student_stmt->execute([$student_id]);
$student = $student_stmt->fetch(PDO::FETCH_ASSOC);
$student_grade = $student['grade_level'];

echo "<h1>Matching Game Debug Information</h1>";
echo "<p><strong>Student:</strong> " . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "</p>";
echo "<p><strong>Grade Level:</strong> " . $student_grade . "</p>";

// Check if matching_games table exists
try {
    $check = $pdo->query("SELECT COUNT(*) as count FROM matching_games");
    $result = $check->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>✅ matching_games table exists:</strong> " . $result['count'] . " total games</p>";
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> matching_games table doesn't exist or error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Solution:</strong> Run install_matching_games.sql</p>";
    exit;
}

// Get all active matching games (regardless of grade)
$all_query = "SELECT mg.*, s.subject_name, s.grade_level,
              (SELECT COUNT(*) FROM matching_pairs WHERE matching_game_id = mg.matching_game_id) as pair_count
              FROM matching_games mg
              INNER JOIN subjects s ON mg.subject_id = s.subject_id
              WHERE mg.status = 'active'";
$stmt = $pdo->prepare($all_query);
$stmt->execute();
$all_games = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>All Active Matching Games (" . count($all_games) . ")</h2>";
if (count($all_games) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Subject</th><th>Grade</th><th>Pairs</th><th>Match?</th></tr>";
    foreach ($all_games as $game) {
        $match = ($game['grade_level'] == $student_grade) ? "✅ YES" : "❌ NO";
        echo "<tr>";
        echo "<td>" . $game['matching_game_id'] . "</td>";
        echo "<td>" . htmlspecialchars($game['title']) . "</td>";
        echo "<td>" . htmlspecialchars($game['subject_name']) . "</td>";
        echo "<td>" . $game['grade_level'] . "</td>";
        echo "<td>" . $game['pair_count'] . "</td>";
        echo "<td>" . $match . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ No active matching games found. Teachers need to create and publish games.</p>";
}

// Get matching games for student's grade
$grade_query = "SELECT mg.*, s.subject_name, s.grade_level,
                (SELECT COUNT(*) FROM matching_pairs WHERE matching_game_id = mg.matching_game_id) as pair_count
                FROM matching_games mg
                INNER JOIN subjects s ON mg.subject_id = s.subject_id
                WHERE mg.status = 'active' AND s.grade_level = ?";
$stmt = $pdo->prepare($grade_query);
$stmt->execute([$student_grade]);
$grade_games = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Matching Games for Grade " . $student_grade . " (" . count($grade_games) . ")</h2>";
if (count($grade_games) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Subject</th><th>Pairs</th><th>Action</th></tr>";
    foreach ($grade_games as $game) {
        echo "<tr>";
        echo "<td>" . $game['matching_game_id'] . "</td>";
        echo "<td>" . htmlspecialchars($game['title']) . "</td>";
        echo "<td>" . htmlspecialchars($game['subject_name']) . "</td>";
        echo "<td>" . $game['pair_count'] . "</td>";
        echo "<td><a href='play-matching-game.php?matching_game_id=" . $game['matching_game_id'] . "' target='_blank'>Play</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ No matching games available for grade " . $student_grade . "</p>";
}

// Check completed sessions
$completed_query = "SELECT ms.*, mg.title
                    FROM matching_sessions ms
                    INNER JOIN matching_games mg ON ms.matching_game_id = mg.matching_game_id
                    WHERE ms.student_id = ? AND ms.completed_at IS NOT NULL";
$stmt = $pdo->prepare($completed_query);
$stmt->execute([$student_id]);
$completed = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Completed Games (" . count($completed) . ")</h2>";
if (count($completed) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Game</th><th>Score</th><th>Correct</th><th>Time</th><th>Completed</th></tr>";
    foreach ($completed as $session) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($session['title']) . "</td>";
        echo "<td>" . $session['total_score'] . "</td>";
        echo "<td>" . $session['total_correct'] . "/" . $session['total_pairs'] . "</td>";
        echo "<td>" . $session['time_taken'] . "s</td>";
        echo "<td>" . $session['completed_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No completed games yet.</p>";
}

echo "<hr>";
echo "<p><a href='available-games.php'>← Back to Available Games</a></p>";

// Test the UNION query
echo "<h2>UNION Query Test</h2>";
try {
    $union_query = "SELECT ga.game_id, ga.title, ga.description, ga.time_limit, ga.show_leaderboard, 
          ga.status, ga.created_at, ga.updated_at, ga.teacher_id, ga.subject_id,
          s.subject_name, s.grade_level as subject_grade, 
          CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
          'quiz' as game_type_flag,
          (SELECT COUNT(*) FROM game_questions WHERE game_id = ga.game_id) as question_count,
          (SELECT COUNT(*) FROM game_sessions WHERE game_id = ga.game_id AND student_id = ? AND completed_at IS NOT NULL) as played,
          (SELECT total_score FROM game_sessions WHERE game_id = ga.game_id AND student_id = ? AND completed_at IS NOT NULL ORDER BY total_score DESC LIMIT 1) as best_score
          FROM game_activities ga
          INNER JOIN subjects s ON ga.subject_id = s.subject_id
          INNER JOIN users u ON ga.teacher_id = u.user_id
          WHERE s.grade_level = ? 
          AND ga.status = 'active'
          AND NOT EXISTS (
              SELECT 1 FROM game_sessions gs 
              WHERE gs.game_id = ga.game_id 
              AND gs.student_id = ? 
              AND gs.completed_at IS NOT NULL
          )
          UNION ALL
          SELECT mg.matching_game_id as game_id, mg.title, mg.description, mg.time_limit, 
                 mg.show_leaderboard, mg.status, mg.created_at, mg.updated_at, 
                 mg.teacher_id, mg.subject_id,
                 s.subject_name, s.grade_level as subject_grade, 
                 CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
                 'matching' as game_type_flag,
          (SELECT COUNT(*) FROM matching_pairs WHERE matching_game_id = mg.matching_game_id) as question_count,
          (SELECT COUNT(*) FROM matching_sessions WHERE matching_game_id = mg.matching_game_id AND student_id = ? AND completed_at IS NOT NULL) as played,
          (SELECT total_score FROM matching_sessions WHERE matching_game_id = mg.matching_game_id AND student_id = ? AND completed_at IS NOT NULL ORDER BY total_score DESC LIMIT 1) as best_score
          FROM matching_games mg
          INNER JOIN subjects s ON mg.subject_id = s.subject_id
          INNER JOIN users u ON mg.teacher_id = u.user_id
          WHERE s.grade_level = ? 
          AND mg.status = 'active'
          AND NOT EXISTS (
              SELECT 1 FROM matching_sessions ms 
              WHERE ms.matching_game_id = mg.matching_game_id 
              AND ms.student_id = ? 
              AND ms.completed_at IS NOT NULL
          )
          ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($union_query);
    $stmt->execute([$student_id, $student_id, $student_grade, $student_id, $student_id, $student_id, $student_grade, $student_id]);
    $union_games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>✅ UNION Query Success:</strong> Found " . count($union_games) . " games (both quiz and matching)</p>";
    
    if (count($union_games) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>Type</th><th>Title</th><th>Subject</th><th>Teacher</th><th>Items</th></tr>";
        foreach ($union_games as $game) {
            $type_icon = $game['game_type_flag'] == 'matching' ? '🧩' : '🎯';
            echo "<tr>";
            echo "<td>" . $type_icon . " " . $game['game_type_flag'] . "</td>";
            echo "<td>" . htmlspecialchars($game['title']) . "</td>";
            echo "<td>" . htmlspecialchars($game['subject_name']) . "</td>";
            echo "<td>" . htmlspecialchars($game['teacher_name']) . "</td>";
            echo "<td>" . $game['question_count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p><strong>❌ UNION Query Error:</strong> " . $e->getMessage() . "</p>";
}
?>
