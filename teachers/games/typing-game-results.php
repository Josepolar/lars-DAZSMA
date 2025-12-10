<?php
session_start();
include '../../Database/database.php';

// Check if user is TEACHER (role_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../teacher-login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get typing game ID
if (!isset($_GET['typing_game_id'])) {
    header("Location: manage-games.php");
    exit();
}

$typing_game_id = $_GET['typing_game_id'];

// Verify this game belongs to the teacher
$query = "SELECT tg.*, s.subject_name 
          FROM typing_games tg
          INNER JOIN subjects s ON tg.subject_id = s.subject_id
          WHERE tg.typing_game_id = ? AND tg.teacher_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$typing_game_id, $teacher_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: manage-games.php");
    exit();
}

// Get total texts
$text_query = "SELECT COUNT(*) as total_texts FROM typing_texts WHERE typing_game_id = ?";
$text_stmt = $pdo->prepare($text_query);
$text_stmt->execute([$typing_game_id]);
$text_data = $text_stmt->fetch(PDO::FETCH_ASSOC);
$total_texts = $text_data['total_texts'];

// Get student results
$query = "SELECT ts.*, 
          CONCAT(u.first_name, ' ', u.last_name) as student_name,
          u.user_id as student_number
          FROM typing_sessions ts
          INNER JOIN users u ON ts.student_id = u.user_id
          WHERE ts.typing_game_id = ? AND ts.completed_at IS NOT NULL
          ORDER BY ts.total_score DESC, ts.wpm DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$typing_game_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_players = count($results);
$avg_score = $total_players > 0 ? array_sum(array_column($results, 'total_score')) / $total_players : 0;
$avg_wpm = $total_players > 0 ? array_sum(array_column($results, 'wpm')) / $total_players : 0;
$avg_accuracy = $total_players > 0 ? array_sum(array_column($results, 'accuracy')) / $total_players : 0;
$highest_wpm = $total_players > 0 ? max(array_column($results, 'wpm')) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/tablogo.png">
    <title>Typing Game Results - <?php echo htmlspecialchars($game['title']); ?></title>
    <link rel="stylesheet" href="../teacher-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .results-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #545b62;
            transform: translateY(-2px);
        }
        
        .game-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .game-header h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .game-header h1 i {
            color: #00d4ff;
        }
        
        .game-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .game-info span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .game-info i {
            color: #26890D;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #26890D;
            margin-bottom: 5px;
        }
        
        .stat-value.wpm {
            color: #00d4ff;
        }
        
        .stat-value.accuracy {
            color: #ffc107;
        }
        
        .stat-value.score {
            color: #00ff88;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .results-table-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .results-table-container h2 {
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .results-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        .results-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .results-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 14px;
        }
        
        .rank-1 {
            background: linear-gradient(135deg, #ffd700, #ffb300);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.4);
        }
        
        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0, #a0a0a0);
            color: white;
        }
        
        .rank-3 {
            background: linear-gradient(135deg, #cd7f32, #b87333);
            color: white;
        }
        
        .rank-other {
            background: #e9ecef;
            color: #666;
        }
        
        .wpm-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .accuracy-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .accuracy-excellent {
            background: #d4edda;
            color: #155724;
        }
        
        .accuracy-good {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .accuracy-average {
            background: #fff3cd;
            color: #856404;
        }
        
        .accuracy-poor {
            background: #f8d7da;
            color: #721c24;
        }
        
        .score-badge {
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-results i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .export-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .export-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header-actions {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="results-container">
        <a href="manage-games.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Manage Games
        </a>
        
        <div class="game-header">
            <h1><i class="fas fa-keyboard"></i> <?php echo htmlspecialchars($game['title']); ?></h1>
            <div class="game-info">
                <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($game['subject_name']); ?></span>
                <span><i class="fas fa-clock"></i> <?php echo $game['time_limit']; ?> seconds</span>
                <span><i class="fas fa-tachometer-alt"></i> Min WPM: <?php echo $game['min_wpm']; ?></span>
                <span><i class="fas fa-layer-group"></i> Difficulty: <?php echo ucfirst($game['difficulty']); ?></span>
                <span><i class="fas fa-file-alt"></i> <?php echo $total_texts; ?> typing text(s)</span>
                <span><i class="fas fa-info-circle"></i> Status: <?php echo ucfirst($game['status']); ?></span>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo $total_players; ?></div>
                <div class="stat-label">Total Players</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⚡</div>
                <div class="stat-value wpm"><?php echo number_format($avg_wpm, 1); ?></div>
                <div class="stat-label">Average WPM</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-value accuracy"><?php echo number_format($avg_accuracy, 1); ?>%</div>
                <div class="stat-label">Average Accuracy</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-value score"><?php echo number_format($highest_wpm, 0); ?></div>
                <div class="stat-label">Highest WPM</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-value"><?php echo number_format($avg_score, 0); ?></div>
                <div class="stat-label">Average Score</div>
            </div>
        </div>
        
        <div class="results-table-container">
            <div class="header-actions">
                <h2><i class="fas fa-trophy"></i> Leaderboard</h2>
                <?php if ($total_players > 0): ?>
                    <button class="export-btn" onclick="exportResults()">
                        <i class="fas fa-file-export"></i> Export Results
                    </button>
                <?php endif; ?>
            </div>
            
            <?php if ($total_players > 0): ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Student</th>
                            <th>WPM</th>
                            <th>Accuracy</th>
                            <th>Score</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $index => $result): 
                            $rank = $index + 1;
                            $rank_class = $rank <= 3 ? "rank-$rank" : "rank-other";
                            
                            // Determine accuracy class
                            $accuracy_class = 'accuracy-poor';
                            if ($result['accuracy'] >= 95) {
                                $accuracy_class = 'accuracy-excellent';
                            } elseif ($result['accuracy'] >= 85) {
                                $accuracy_class = 'accuracy-good';
                            } elseif ($result['accuracy'] >= 70) {
                                $accuracy_class = 'accuracy-average';
                            }
                        ?>
                            <tr>
                                <td>
                                    <span class="rank-badge <?php echo $rank_class; ?>"><?php echo $rank; ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($result['student_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="wpm-badge"><?php echo $result['wpm']; ?> WPM</span>
                                </td>
                                <td>
                                    <span class="accuracy-badge <?php echo $accuracy_class; ?>">
                                        <?php echo number_format($result['accuracy'], 1); ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="score-badge"><?php echo number_format($result['total_score']); ?></span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y g:i A', strtotime($result['completed_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-inbox"></i>
                    <p>No students have completed this typing game yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function exportResults() {
            // Create CSV content
            let csv = 'Rank,Student Name,WPM,Accuracy,Score,Completed At\n';
            
            <?php foreach ($results as $index => $result): ?>
            csv += '<?php echo ($index + 1); ?>,"<?php echo addslashes($result['student_name']); ?>",<?php echo $result['wpm']; ?>,<?php echo $result['accuracy']; ?>%,<?php echo $result['total_score']; ?>,"<?php echo date('Y-m-d H:i:s', strtotime($result['completed_at'])); ?>"\n';
            <?php endforeach; ?>
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'typing_game_results_<?php echo $typing_game_id; ?>.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
