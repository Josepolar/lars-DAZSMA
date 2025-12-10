<?php
session_start();
include '../../Database/database.php';

// Check if user is STUDENT (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: ../stud-login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get typing game ID
if (!isset($_GET['typing_game_id'])) {
    header("Location: available-games.php");
    exit();
}

$typing_game_id = $_GET['typing_game_id'];

// Get game details
$query = "SELECT tg.*, s.subject_name, CONCAT(u.first_name, ' ', u.last_name) as teacher_name
          FROM typing_games tg
          INNER JOIN subjects s ON tg.subject_id = s.subject_id
          INNER JOIN users u ON tg.teacher_id = u.user_id
          WHERE tg.typing_game_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$typing_game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: available-games.php");
    exit();
}

// Get leaderboard
$leaderboardQuery = "
    SELECT 
        ts.student_id,
        CONCAT(u.first_name, ' ', u.last_name) as student_name,
        MAX(ts.wpm) as best_wpm,
        MAX(ts.accuracy) as best_accuracy,
        MAX(ts.total_score) as best_score,
        MIN(ts.time_taken) as best_time,
        COUNT(*) as attempts
    FROM typing_sessions ts
    INNER JOIN users u ON ts.student_id = u.user_id
    WHERE ts.typing_game_id = ? AND ts.completed_at IS NOT NULL
    GROUP BY ts.student_id, u.first_name, u.last_name
    ORDER BY best_score DESC, best_wpm DESC
    LIMIT 50
";
$stmt = $pdo->prepare($leaderboardQuery);
$stmt->execute([$typing_game_id]);
$leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current student's stats
$myStatsQuery = "
    SELECT 
        MAX(wpm) as best_wpm,
        MAX(accuracy) as best_accuracy,
        MAX(total_score) as best_score,
        MIN(time_taken) as best_time,
        AVG(wpm) as avg_wpm,
        AVG(accuracy) as avg_accuracy,
        COUNT(*) as total_attempts
    FROM typing_sessions
    WHERE typing_game_id = ? AND student_id = ? AND completed_at IS NOT NULL
";
$stmt = $pdo->prepare($myStatsQuery);
$stmt->execute([$typing_game_id, $student_id]);
$myStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get my rank
$myRank = 0;
foreach ($leaderboard as $index => $entry) {
    if ($entry['student_id'] == $student_id) {
        $myRank = $index + 1;
        break;
    }
}

// Get recent attempts
$recentQuery = "
    SELECT wpm, accuracy, total_score, time_taken, completed_at
    FROM typing_sessions
    WHERE typing_game_id = ? AND student_id = ? AND completed_at IS NOT NULL
    ORDER BY completed_at DESC
    LIMIT 10
";
$stmt = $pdo->prepare($recentQuery);
$stmt->execute([$typing_game_id, $student_id]);
$recentAttempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['title']); ?> - Results</title>
    <link rel="icon" type="image/png" href="../../assets/tablogo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            padding: 20px;
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header h1::before {
            content: '⌨️';
            font-size: 32px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 25px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,212,255,0.3);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #00d4ff, #00ff88);
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #00d4ff;
        }
        
        .stat-value.wpm { color: #00ff88; }
        .stat-value.accuracy { color: #ffd700; }
        .stat-value.rank { color: #ff6b6b; }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 900px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .panel {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .panel-title {
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .leaderboard-table th,
        .leaderboard-table td {
            padding: 12px 15px;
            text-align: left;
        }
        
        .leaderboard-table th {
            background: rgba(255,255,255,0.1);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }
        
        .leaderboard-table tr {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .leaderboard-table tr:hover {
            background: rgba(255,255,255,0.05);
        }
        
        .leaderboard-table tr.current-user {
            background: rgba(0,212,255,0.2);
        }
        
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 14px;
        }
        
        .rank-badge.gold {
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            color: #333;
        }
        
        .rank-badge.silver {
            background: linear-gradient(135deg, #c0c0c0, #a0a0a0);
            color: #333;
        }
        
        .rank-badge.bronze {
            background: linear-gradient(135deg, #cd7f32, #b8860b);
            color: white;
        }
        
        .rank-badge.regular {
            background: rgba(255,255,255,0.2);
        }
        
        .attempts-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .attempt-item {
            padding: 15px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .attempt-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .attempt-date {
            font-size: 12px;
            opacity: 0.7;
        }
        
        .attempt-score {
            font-weight: bold;
            color: #00d4ff;
        }
        
        .attempt-stats {
            display: flex;
            gap: 20px;
            font-size: 13px;
        }
        
        .attempt-stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            opacity: 0.6;
        }
        
        .game-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
        }
        
        .info-item {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .difficulty-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .difficulty-badge.easy { background: #28a745; }
        .difficulty-badge.medium { background: #ffc107; color: #333; }
        .difficulty-badge.hard { background: #dc3545; }
        
        .alert-info {
            background: rgba(0,212,255,0.2);
            border: 1px solid rgba(0,212,255,0.4);
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-info .icon {
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['already_played'])): ?>
        <div class="alert-info">
            <span class="icon">ℹ️</span>
            <span>You have already completed this typing game. This is a one-time attempt game - your score has been recorded on the leaderboard below.</span>
        </div>
        <?php endif; ?>
        
        <div class="header">
            <h1><?php echo htmlspecialchars($game['title']); ?> - Results</h1>
            <div class="header-actions">
                <a href="my-scores.php" class="btn btn-secondary">
                    <i class="fas fa-chart-bar"></i> 📊 My Scores
                </a>
            </div>
        </div>
        
        <div class="game-info">
            <span class="info-item">📚 <?php echo htmlspecialchars($game['subject_name']); ?></span>
            <span class="info-item">👨‍🏫 <?php echo htmlspecialchars($game['teacher_name']); ?></span>
            <span class="info-item">⏱️ <?php echo $game['time_limit']; ?>s time limit</span>
            <span class="difficulty-badge <?php echo $game['difficulty']; ?>">
                <?php echo ucfirst($game['difficulty']); ?>
            </span>
            <?php if ($game['min_wpm'] > 0): ?>
                <span class="info-item">🎯 Min WPM: <?php echo $game['min_wpm']; ?></span>
            <?php endif; ?>
        </div>
        
        <!-- My Stats -->
        <?php if ($myStats['total_attempts'] > 0): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value rank">#<?php echo $myRank ?: '-'; ?></div>
                <div class="stat-label">Your Rank</div>
            </div>
            <div class="stat-card">
                <div class="stat-value wpm"><?php echo round($myStats['best_wpm']); ?></div>
                <div class="stat-label">Best WPM</div>
            </div>
            <div class="stat-card">
                <div class="stat-value accuracy"><?php echo round($myStats['best_accuracy']); ?>%</div>
                <div class="stat-label">Best Accuracy</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($myStats['best_score']); ?></div>
                <div class="stat-label">Best Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo round($myStats['avg_wpm']); ?></div>
                <div class="stat-label">Average WPM</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $myStats['total_attempts']; ?></div>
                <div class="stat-label">Total Attempts</div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="content-grid">
            <!-- Leaderboard -->
            <div class="panel">
                <h2 class="panel-title">🏆 Leaderboard</h2>
                
                <?php if (empty($leaderboard)): ?>
                    <div class="no-data">
                        <p>No results yet. Be the first to play!</p>
                    </div>
                <?php else: ?>
                    <table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Student</th>
                                <th>WPM</th>
                                <th>Accuracy</th>
                                <th>Score</th>
                                <th>Attempts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaderboard as $index => $entry): 
                                $rank = $index + 1;
                                $isCurrentUser = $entry['student_id'] == $student_id;
                                $rankClass = '';
                                if ($rank === 1) $rankClass = 'gold';
                                elseif ($rank === 2) $rankClass = 'silver';
                                elseif ($rank === 3) $rankClass = 'bronze';
                                else $rankClass = 'regular';
                            ?>
                                <tr class="<?php echo $isCurrentUser ? 'current-user' : ''; ?>">
                                    <td>
                                        <span class="rank-badge <?php echo $rankClass; ?>">
                                            <?php echo $rank; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($entry['student_name']); ?>
                                        <?php if ($isCurrentUser): ?>
                                            <span style="opacity: 0.7;">(You)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo round($entry['best_wpm']); ?></td>
                                    <td><?php echo round($entry['best_accuracy']); ?>%</td>
                                    <td><?php echo number_format($entry['best_score']); ?></td>
                                    <td><?php echo $entry['attempts']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Recent Attempts -->
            <div class="panel">
                <h2 class="panel-title">📊 Your Recent Attempts</h2>
                
                <?php if (empty($recentAttempts)): ?>
                    <div class="no-data">
                        <p>You haven't played this game yet.</p>
                        <a href="play-typing-game.php?typing_game_id=<?php echo $typing_game_id; ?>" class="btn btn-primary" style="margin-top: 15px;">
                            ▶️ Play Now
                        </a>
                    </div>
                <?php else: ?>
                    <div class="attempts-list">
                        <?php foreach ($recentAttempts as $attempt): ?>
                            <div class="attempt-item">
                                <div class="attempt-header">
                                    <span class="attempt-date">
                                        <?php echo date('M d, Y H:i', strtotime($attempt['completed_at'])); ?>
                                    </span>
                                    <span class="attempt-score">
                                        <?php echo number_format($attempt['total_score']); ?> pts
                                    </span>
                                </div>
                                <div class="attempt-stats">
                                    <span class="attempt-stat">
                                        ⚡ <?php echo round($attempt['wpm']); ?> WPM
                                    </span>
                                    <span class="attempt-stat">
                                        🎯 <?php echo round($attempt['accuracy']); ?>%
                                    </span>
                                    <span class="attempt-stat">
                                        ⏱️ <?php echo $attempt['time_taken']; ?>s
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
