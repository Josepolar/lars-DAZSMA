<?php
session_start();
include '../../Database/database.php';

// Check if user is STUDENT (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: ../stud-login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get all completed game sessions for this student
$query = "SELECT gs.*, ga.title, s.subject_name,
          ROUND((gs.total_correct / gs.total_questions * 100), 1) as accuracy
          FROM game_sessions gs
          INNER JOIN game_activities ga ON gs.game_id = ga.game_id
          INNER JOIN subjects s ON ga.subject_id = s.subject_id
          WHERE gs.student_id = ? AND gs.completed_at IS NOT NULL
          ORDER BY gs.completed_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$student_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get overall stats
$stats_query = "SELECT 
                COUNT(*) as total_games,
                SUM(total_score) as total_points,
                AVG(total_score) as avg_score,
                SUM(total_correct) as total_correct,
                SUM(total_questions) as total_questions
                FROM game_sessions
                WHERE student_id = ? AND completed_at IS NOT NULL";
$stmt = $pdo->prepare($stats_query);
$stmt->execute([$student_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$overall_accuracy = $stats['total_questions'] > 0 ? round(($stats['total_correct'] / $stats['total_questions']) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Game Scores</title>
    <link rel="stylesheet" href="../student-home.css">
    <style>
        .scores-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #26890D;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .sessions-list {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .sessions-list h3 {
            margin-top: 0;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .score-badge {
            background: #26890D;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .accuracy-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .accuracy-high {
            background: #d4edda;
            color: #155724;
        }
        
        .accuracy-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .accuracy-low {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-scores {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
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
        
        .play-games-btn {
            background: #26890D;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="scores-container">
        <a href="available-games.php" class="back-btn">← Back to Games</a>
        
        <div class="page-header">
            <h1>📊 My Game Scores</h1>
            <p>Track your performance across all game activities</p>
        </div>
        
        <?php if ($stats['total_games'] > 0): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_games']; ?></div>
                    <div class="stat-label">Games Played</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_points']); ?></div>
                    <div class="stat-label">Total Points</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['avg_score']); ?></div>
                    <div class="stat-label">Average Score</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $overall_accuracy; ?>%</div>
                    <div class="stat-label">Overall Accuracy</div>
                </div>
            </div>
            
            <div class="sessions-list">
                <h3>Game History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Game</th>
                            <th>Subject</th>
                            <th>Score</th>
                            <th>Correct</th>
                            <th>Accuracy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): 
                            $accuracy_class = $session['accuracy'] >= 80 ? 'accuracy-high' : 
                                            ($session['accuracy'] >= 50 ? 'accuracy-medium' : 'accuracy-low');
                        ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($session['completed_at'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($session['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($session['subject_name']); ?></td>
                                <td>
                                    <span class="score-badge"><?php echo $session['total_score']; ?> pts</span>
                                </td>
                                <td><?php echo $session['total_correct']; ?> / <?php echo $session['total_questions']; ?></td>
                                <td>
                                    <span class="accuracy-badge <?php echo $accuracy_class; ?>">
                                        <?php echo $session['accuracy']; ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="sessions-list no-scores">
                <h2>No Game History Yet</h2>
                <p>You haven't completed any games yet. Start playing to see your scores here!</p>
                <a href="available-games.php" class="play-games-btn">🎮 Play Games</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
