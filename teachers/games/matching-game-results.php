<?php
session_start();
include '../../Database/database.php';

// Check if user is TEACHER (role_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../teacher-login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get matching game ID
if (!isset($_GET['matching_game_id'])) {
    header("Location: manage-games.php");
    exit();
}

$matching_game_id = $_GET['matching_game_id'];

// Verify this game belongs to the teacher
$query = "SELECT mg.*, s.subject_name 
          FROM matching_games mg
          INNER JOIN subjects s ON mg.subject_id = s.subject_id
          WHERE mg.matching_game_id = ? AND mg.teacher_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id, $teacher_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: manage-games.php");
    exit();
}

// Get total pairs
$pair_query = "SELECT COUNT(*) as total_pairs FROM matching_pairs WHERE matching_game_id = ?";
$pair_stmt = $pdo->prepare($pair_query);
$pair_stmt->execute([$matching_game_id]);
$pair_data = $pair_stmt->fetch(PDO::FETCH_ASSOC);
$total_pairs = $pair_data['total_pairs'];

// Get student results
$query = "SELECT ms.*, 
          CONCAT(u.first_name, ' ', u.last_name) as student_name,
          u.user_id as student_number
          FROM matching_sessions ms
          INNER JOIN users u ON ms.student_id = u.user_id
          WHERE ms.matching_game_id = ? AND ms.completed_at IS NOT NULL
          ORDER BY ms.total_score DESC, ms.time_taken ASC";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_players = count($results);
$avg_score = $total_players > 0 ? array_sum(array_column($results, 'total_score')) / $total_players : 0;
$avg_time = $total_players > 0 ? array_sum(array_column($results, 'time_taken')) / $total_players : 0;
$avg_correct = $total_players > 0 ? array_sum(array_column($results, 'total_correct')) / $total_players : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/tablogo.png">
    <title>Matching Game Results</title>
    <link rel="stylesheet" href="../teacher-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <style>
        .results-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
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
        }
        
        .game-info {
            color: #666;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        
        .results-table tr:hover {
            background: #f8f9fa;
        }
        
        .rank-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            font-weight: bold;
            color: white;
        }
        
        .rank-1 { background: #ffd700; color: #000; }
        .rank-2 { background: #c0c0c0; color: #000; }
        .rank-3 { background: #cd7f32; color: #fff; }
        .rank-other { background: #6c757d; }
        
        .score-bar {
            background: #e9ecef;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .score-fill {
            height: 100%;
            background: linear-gradient(90deg, #26890D, #34a853);
            border-radius: 4px;
        }
        
        .accuracy {
            font-weight: bold;
        }
        
        .accuracy.high { color: #28a745; }
        .accuracy.medium { color: #ffc107; }
        .accuracy.low { color: #dc3545; }
        
        .back-btn {
            background: #666;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            background: #555;
        }
        
        .export-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        
        .export-btn:hover {
            background: #218838;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        
        .no-results h2 {
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .results-table {
                font-size: 14px;
            }
            
            .results-table th,
            .results-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="results-container">
        <div>
            <a href="manage-games.php" class="back-btn">← Back to Games</a>
            <a href="export-matching-results.php?matching_game_id=<?php echo $matching_game_id; ?>" class="export-btn">📥 Export Results</a>
        </div>
        
        <div class="game-header">
            <h1>🧩 <?php echo htmlspecialchars($game['title']); ?> - Results</h1>
            <div class="game-info">
                <?php echo htmlspecialchars($game['subject_name']); ?> | 
                Total Pairs: <?php echo $total_pairs; ?> | 
                Status: <?php echo strtoupper($game['status']); ?>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo $total_players; ?></div>
                <div class="stat-label">Total Players</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-value"><?php echo number_format($avg_score, 0); ?></div>
                <div class="stat-label">Average Score</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo number_format($avg_correct, 1); ?> / <?php echo $total_pairs; ?></div>
                <div class="stat-label">Average Pairs Matched</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value"><?php echo number_format($avg_time, 0); ?>s</div>
                <div class="stat-label">Average Time</div>
            </div>
        </div>
        
        <div class="results-table-container">
            <h2 style="margin-bottom: 20px;">Student Results</h2>
            
            <?php if (count($results) > 0): ?>
                <div class="table-wrapper">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Pairs Matched</th>
                            <th>Accuracy</th>
                            <th>Time Taken</th>
                            <th>Score</th>
                            <th>Completed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $index => $result): 
                            $rank = $index + 1;
                            $accuracy = ($result['total_correct'] / $total_pairs) * 100;
                            $accuracy_class = $accuracy >= 80 ? 'high' : ($accuracy >= 50 ? 'medium' : 'low');
                            $rank_class = $rank <= 3 ? "rank-$rank" : "rank-other";
                        ?>
                            <tr>
                                <td data-label="Rank">
                                    <span class="rank-badge <?php echo $rank_class; ?>">
                                        <?php echo $rank; ?>
                                    </span>
                                </td>
                                <td data-label="Student ID"><?php echo htmlspecialchars($result['student_number']); ?></td>
                                <td data-label="Student Name"><?php echo htmlspecialchars($result['student_name']); ?></td>
                                <td data-label="Pairs Matched">
                                    <?php echo $result['total_correct']; ?> / <?php echo $total_pairs; ?>
                                    <div class="score-bar">
                                        <div class="score-fill" style="width: <?php echo $accuracy; ?>%;"></div>
                                    </div>
                                </td>
                                <td data-label="Accuracy">
                                    <span class="accuracy <?php echo $accuracy_class; ?>">
                                        <?php echo number_format($accuracy, 1); ?>%
                                    </span>
                                </td>
                                <td data-label="Time Taken"><?php echo number_format($result['time_taken'], 0); ?>s</td>
                                <td data-label="Score"><strong><?php echo number_format($result['total_score'], 0); ?></strong></td>
                                <td data-label="Completed At"><?php echo date('M d, Y H:i', strtotime($result['completed_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <h2>No Results Yet</h2>
                    <p>No students have completed this matching game yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
