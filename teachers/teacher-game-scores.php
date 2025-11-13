<?php
session_start();
include '../Database/database.php';

// Check if user is TEACHER (role_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header('Location: teacher-login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get filter parameters
$subject_filter = $_GET['subject'] ?? '';
$student_filter = $_GET['student'] ?? '';
$activity_type = $_GET['type'] ?? 'all'; // all, quiz_game, matching_game, regular

// Get all subjects taught by this teacher
$subjects_query = "SELECT DISTINCT s.subject_id, s.subject_name 
                   FROM subjects s
                   INNER JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
                   WHERE ts.teacher_id = ?
                   ORDER BY s.subject_name";
$stmt = $pdo->prepare($subjects_query);
$stmt->execute([$teacher_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all students who have submitted work in teacher's subjects
$students_query = "SELECT DISTINCT u.user_id, 
                   CONCAT(u.first_name, ' ', u.last_name) as student_name
                   FROM users u
                   WHERE u.role_id = 4
                   AND (
                       EXISTS (
                           SELECT 1 FROM student_submissions ss
                           INNER JOIN activities a ON ss.activity_id = a.activity_id
                           INNER JOIN teacher_subjects ts ON a.subject_id = ts.subject_id
                           WHERE ss.student_id = u.user_id AND ts.teacher_id = ?
                       )
                       OR EXISTS (
                           SELECT 1 FROM game_sessions gs
                           INNER JOIN game_activities ga ON gs.game_id = ga.game_id
                           INNER JOIN teacher_subjects ts ON ga.subject_id = ts.subject_id
                           WHERE gs.student_id = u.user_id AND ts.teacher_id = ?
                       )
                       OR EXISTS (
                           SELECT 1 FROM matching_sessions ms
                           INNER JOIN matching_games mg ON ms.matching_game_id = mg.matching_game_id
                           INNER JOIN teacher_subjects ts ON mg.subject_id = ts.subject_id
                           WHERE ms.student_id = u.user_id AND ts.teacher_id = ?
                       )
                   )
                   ORDER BY u.last_name, u.first_name";
$stmt = $pdo->prepare($students_query);
$stmt->execute([$teacher_id, $teacher_id, $teacher_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build comprehensive query for all student scores
$where_conditions = ["ts.teacher_id = ?"];
$params = [$teacher_id];

if ($subject_filter) {
    $where_conditions[] = "s.subject_id = ?";
    $params[] = $subject_filter;
}

if ($student_filter) {
    $where_conditions[] = "u.user_id = ?";
    $params[] = $student_filter;
}

$where_clause = implode(" AND ", $where_conditions);

// Query for Quiz Games
$quiz_games_query = "SELECT 
    u.user_id,
    CONCAT(u.first_name, ' ', u.last_name) as student_name,
    s.subject_name,
    ga.title as activity_title,
    'Quiz Game' as activity_type,
    gs.total_score as score,
    gs.total_questions,
    gs.total_correct,
    CASE 
        WHEN gs.total_questions > 0 THEN ROUND((gs.total_correct / gs.total_questions * 100), 1)
        ELSE 0 
    END as percentage,
    gs.completed_at,
    ga.game_id as activity_id
    FROM users u
    INNER JOIN game_sessions gs ON u.user_id = gs.student_id
    INNER JOIN game_activities ga ON gs.game_id = ga.game_id
    INNER JOIN subjects s ON ga.subject_id = s.subject_id
    INNER JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
    WHERE {$where_clause} 
    AND gs.completed_at IS NOT NULL
    AND gs.total_questions > 0
    AND u.role_id = 4";

// Query for Matching Games
$matching_games_query = "SELECT 
    u.user_id,
    CONCAT(u.first_name, ' ', u.last_name) as student_name,
    s.subject_name,
    mg.title as activity_title,
    'Matching Game' as activity_type,
    ms.total_score as score,
    ms.total_pairs as total_questions,
    ms.total_correct as total_correct,
    CASE 
        WHEN ms.total_pairs > 0 THEN ROUND((ms.total_correct / ms.total_pairs * 100), 1)
        ELSE 0 
    END as percentage,
    ms.completed_at,
    mg.matching_game_id as activity_id
    FROM users u
    INNER JOIN matching_sessions ms ON u.user_id = ms.student_id
    INNER JOIN matching_games mg ON ms.matching_game_id = mg.matching_game_id
    INNER JOIN subjects s ON mg.subject_id = s.subject_id
    INNER JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
    WHERE {$where_clause}
    AND ms.completed_at IS NOT NULL
    AND ms.total_pairs > 0
    AND u.role_id = 4";

// Query for Regular Activities
$regular_activities_query = "SELECT 
    u.user_id,
    CONCAT(u.first_name, ' ', u.last_name) as student_name,
    s.subject_name,
    a.title as activity_title,
    a.activity_type as activity_type,
    ss.total_score as score,
    ss.max_score as total_questions,
    ss.total_score as total_correct,
    ROUND(ss.percentage, 1) as percentage,
    ss.submitted_at as completed_at,
    a.activity_id
    FROM users u
    INNER JOIN student_submissions ss ON u.user_id = ss.student_id
    INNER JOIN activities a ON ss.activity_id = a.activity_id
    INNER JOIN subjects s ON a.subject_id = s.subject_id
    INNER JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
    WHERE {$where_clause}
    AND ss.submission_status IN ('submitted', 'graded')
    AND u.role_id = 4";

// Combine queries based on filter
$union_queries = [];
if ($activity_type == 'all' || $activity_type == 'quiz_game') {
    $union_queries[] = $quiz_games_query;
}
if ($activity_type == 'all' || $activity_type == 'matching_game') {
    $union_queries[] = $matching_games_query;
}
if ($activity_type == 'all' || $activity_type == 'regular') {
    $union_queries[] = $regular_activities_query;
}

$final_query = implode(" UNION ALL ", $union_queries);
$final_query .= " ORDER BY completed_at DESC LIMIT 500";

$stmt = $pdo->prepare($final_query);
// Execute with repeated parameters for each UNION query
$execute_params = [];
foreach ($union_queries as $query) {
    $execute_params = array_merge($execute_params, $params);
}
$stmt->execute($execute_params);
$all_scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$total_submissions = count($all_scores);
$avg_score = $total_submissions > 0 ? array_sum(array_column($all_scores, 'score')) / $total_submissions : 0;
$avg_percentage = $total_submissions > 0 ? array_sum(array_column($all_scores, 'percentage')) / $total_submissions : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="teacher-acts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Game & Activity Scores</title>
    <style>
        .filters-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-btn.primary {
            background: #26890D;
            color: white;
        }
        
        .filter-btn.primary:hover {
            background: #1e6a0a;
        }
        
        .filter-btn.secondary {
            background: #6c757d;
            color: white;
        }
        
        .filter-btn.secondary:hover {
            background: #545b62;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .summary-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .summary-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #26890D;
        }
        
        .scores-table-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .scores-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .scores-table thead {
            background: #f8f9fa;
        }
        
        .scores-table th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        .scores-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .scores-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .activity-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-quiz-game {
            background: #ff6b6b;
            color: white;
        }
        
        .badge-matching-game {
            background: #9b59b6;
            color: white;
        }
        
        .badge-quiz {
            background: #3498db;
            color: white;
        }
        
        .badge-assignment {
            background: #2ecc71;
            color: white;
        }
        
        .badge-recitation {
            background: #f39c12;
            color: white;
        }
        
        .badge-exam {
            background: #e74c3c;
            color: white;
        }
        
        .score-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .score-excellent {
            background: #d4edda;
            color: #155724;
        }
        
        .score-good {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .score-average {
            background: #fff3cd;
            color: #856404;
        }
        
        .score-poor {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .export-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-left: auto;
        }
        
        .export-btn:hover {
            background: #218838;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .back-link {
            display: inline-block;
            color: #26890D;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="../assets/larslogo.png" alt="logo">
                </span>

                <div class="text header-text">
                    <span class="profession">Teacher Dashboard</span>
                </div>
            </div>
            <hr>
        </header>

        <div class="menu-bar">
            <div class="menu">
                <ul class="menu-links">
                    <li class="nav-link">
                        <button class="tablinks"><a href="teacher-dashboard.php" class="tablinks">Dashboard</a></button>
                    </li>

                    <li class="nav-link">
                        <button class="tablinks"><a href="teacher-acts.php" class="tablinks">Activities</a></button>
                    </li>
                    
                    <li class="nav-link">
                        <button class="tablinks"><a href="teacher-studs.php" class="tablinks">Students</a></button>
                    </li>
                    
                    <li class="nav-link">
                        <button class="tablinks active"><a href="teacher-game-scores.php" class="tablinks">Game Scores</a></button>
                    </li>
                    
                </ul>
            </div>

            <div class="bottom-content">
                <li class="nav-link">
                    <button class="tablinks"><a href="../logout.php" class="tablinks">Logout</a></button>
                </li>
            </div>
        </div>
    </nav>

    <section class="home" id="home-section">
        <div class="page-header">
            <h1><i class="fas fa-trophy"></i> Game & Activity Scores</h1>
            <button class="export-btn" onclick="exportToCSV()">
                <i class="fas fa-download"></i> Export to CSV
            </button>
        </div>
        
        <!-- Filters -->
        <div class="filters-container">
            <form method="GET" action="teacher-game-scores.php" id="filterForm">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="subject">Subject</label>
                        <select name="subject" id="subject">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['subject_id']; ?>" 
                                        <?php echo $subject_filter == $subject['subject_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="student">Student</label>
                        <select name="student" id="student">
                            <option value="">All Students</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['user_id']; ?>"
                                        <?php echo $student_filter == $student['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['student_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="type">Activity Type</label>
                        <select name="type" id="type">
                            <option value="all" <?php echo $activity_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="quiz_game" <?php echo $activity_type == 'quiz_game' ? 'selected' : ''; ?>>Quiz Games</option>
                            <option value="matching_game" <?php echo $activity_type == 'matching_game' ? 'selected' : ''; ?>>Matching Games</option>
                            <option value="regular" <?php echo $activity_type == 'regular' ? 'selected' : ''; ?>>Regular Activities</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="filter-btn primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button type="button" class="filter-btn secondary" onclick="resetFilters()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Summary Statistics -->
        <div class="summary-stats">
            <div class="summary-card">
                <h3>Total Submissions</h3>
                <div class="value"><?php echo number_format($total_submissions); ?></div>
            </div>
            <div class="summary-card">
                <h3>Average Score</h3>
                <div class="value"><?php echo number_format($avg_score, 1); ?></div>
            </div>
            <div class="summary-card">
                <h3>Average Percentage</h3>
                <div class="value"><?php echo number_format($avg_percentage, 1); ?>%</div>
            </div>
            <div class="summary-card">
                <h3>Unique Students</h3>
                <div class="value"><?php echo count(array_unique(array_column($all_scores, 'user_id'))); ?></div>
            </div>
        </div>
        
        <!-- Scores Table -->
        <div class="scores-table-container">
            <h2 style="margin-bottom: 20px;">Student Scores</h2>
            <?php if (count($all_scores) > 0): ?>
                <table class="scores-table" id="scoresTable">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Subject</th>
                            <th>Activity</th>
                            <th>Type</th>
                            <th>Score</th>
                            <th>Correct/Total</th>
                            <th>Percentage</th>
                            <th>Completed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_scores as $score): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($score['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($score['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($score['activity_title']); ?></td>
                                <td>
                                    <?php
                                    $type_class = '';
                                    $type_display = $score['activity_type'];
                                    
                                    if ($score['activity_type'] == 'Quiz Game') {
                                        $type_class = 'badge-quiz-game';
                                        $type_display = '🎯 Quiz Game';
                                    } elseif ($score['activity_type'] == 'Matching Game') {
                                        $type_class = 'badge-matching-game';
                                        $type_display = '🧩 Matching';
                                    } elseif ($score['activity_type'] == 'quiz') {
                                        $type_class = 'badge-quiz';
                                        $type_display = '📝 Quiz';
                                    } elseif ($score['activity_type'] == 'assignment') {
                                        $type_class = 'badge-assignment';
                                        $type_display = '📚 Assignment';
                                    } elseif ($score['activity_type'] == 'recitation') {
                                        $type_class = 'badge-recitation';
                                        $type_display = '🎤 Recitation';
                                    } elseif ($score['activity_type'] == 'exam') {
                                        $type_class = 'badge-exam';
                                        $type_display = '📋 Exam';
                                    }
                                    ?>
                                    <span class="activity-badge <?php echo $type_class; ?>">
                                        <?php echo $type_display; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo number_format($score['score'], 1); ?></strong></td>
                                <td><?php echo $score['total_correct']; ?> / <?php echo $score['total_questions']; ?></td>
                                <td>
                                    <?php
                                    $percentage = $score['percentage'];
                                    $score_class = '';
                                    
                                    if ($percentage >= 90) {
                                        $score_class = 'score-excellent';
                                    } elseif ($percentage >= 75) {
                                        $score_class = 'score-good';
                                    } elseif ($percentage >= 60) {
                                        $score_class = 'score-average';
                                    } else {
                                        $score_class = 'score-poor';
                                    }
                                    ?>
                                    <span class="score-badge <?php echo $score_class; ?>">
                                        <?php echo number_format($percentage, 1); ?>%
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($score['completed_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No scores found</h3>
                    <p>Try adjusting your filters to see more results.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <script>
        function resetFilters() {
            window.location.href = 'teacher-game-scores.php';
        }
        
        function exportToCSV() {
            const table = document.getElementById('scoresTable');
            if (!table) {
                alert('No data to export');
                return;
            }
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let text = cols[j].innerText.replace(/"/g, '""');
                    row.push('"' + text + '"');
                }
                
                csv.push(row.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'student_scores_' + new Date().getTime() + '.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
