<?php
session_start();
include '../../Database/database.php';

// Check if user is STUDENT (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: ../stud-login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get matching game ID
if (!isset($_GET['matching_game_id'])) {
    header("Location: available-games.php");
    exit();
}

$matching_game_id = $_GET['matching_game_id'];

// Get game details
$query = "SELECT mg.*, s.subject_name, CONCAT(u.first_name, ' ', u.last_name) as teacher_name
          FROM matching_games mg
          INNER JOIN subjects s ON mg.subject_id = s.subject_id
          INNER JOIN users u ON mg.teacher_id = u.user_id
          WHERE mg.matching_game_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: available-games.php");
    exit();
}

// Get student's sessions for this game
$query = "SELECT * FROM matching_sessions 
          WHERE matching_game_id = ? AND student_id = ? 
          ORDER BY completed_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id, $student_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalAttempts = count($sessions);
$completedAttempts = count(array_filter($sessions, function($s) { return $s['completed_at'] !== null; }));
$bestScore = 0;
$bestPercentage = 0;
$averageScore = 0;
$totalScore = 0;

if ($completedAttempts > 0) {
    foreach ($sessions as $session) {
        if ($session['completed_at'] !== null) {
            $totalScore += $session['total_score'];
            if ($session['total_score'] > $bestScore) {
                $bestScore = $session['total_score'];
            }
            $percentage = ($session['total_pairs'] > 0) ? ($session['total_correct'] / $session['total_pairs']) * 100 : 0;
            if ($percentage > $bestPercentage) {
                $bestPercentage = $percentage;
            }
        }
    }
    $averageScore = $totalScore / $completedAttempts;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/tablogo.png">
    <title>Matching Game Results - <?php echo htmlspecialchars($game['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .back-btn {
            background: #fff;
            color: #667eea;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .back-btn:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .header-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .game-title {
            font-size: 32px;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .game-info {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            color: #666;
            font-size: 16px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .sessions-table {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .table-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .score-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
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
        
        .no-sessions {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .play-again-btn {
            background: #26890D;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .play-again-btn:hover {
            background: #1e6a0a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .game-title {
                font-size: 24px;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../student-home.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="header-card">
            <div class="game-title">
                <i class="fas fa-puzzle-piece" style="color: #9c27b0;"></i>
                <?php echo htmlspecialchars($game['title']); ?>
            </div>
            <div class="game-info">
                <div class="info-item">
                    <i class="fas fa-book" style="color: #667eea;"></i>
                    <span><?php echo htmlspecialchars($game['subject_name']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-user-tie" style="color: #667eea;"></i>
                    <span><?php echo htmlspecialchars($game['teacher_name']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock" style="color: #667eea;"></i>
                    <span><?php echo $game['time_limit']; ?> seconds</span>
                </div>
            </div>
        </div>
        
        <?php if ($completedAttempts > 0): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-value"><?php echo $totalAttempts; ?></div>
                    <div class="stat-label">Total Attempts</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-value"><?php echo number_format($bestScore); ?></div>
                    <div class="stat-label">Best Score</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-value"><?php echo number_format($averageScore, 1); ?></div>
                    <div class="stat-label">Average Score</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✓</div>
                    <div class="stat-value"><?php echo number_format($bestPercentage, 1); ?>%</div>
                    <div class="stat-label">Best Accuracy</div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="sessions-table">
            <div class="table-title">
                <i class="fas fa-history"></i>
                Attempt History
            </div>
            
            <?php if (count($sessions) > 0): ?>
                <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date & Time</th>
                            <th>Correct Matches</th>
                            <th>Accuracy</th>
                            <th>Score</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $index => $session): 
                            $percentage = ($session['total_pairs'] > 0) ? ($session['total_correct'] / $session['total_pairs']) * 100 : 0;
                            $scoreClass = 'score-poor';
                            if ($percentage >= 90) $scoreClass = 'score-excellent';
                            elseif ($percentage >= 75) $scoreClass = 'score-good';
                            elseif ($percentage >= 60) $scoreClass = 'score-average';
                            
                            $isCompleted = $session['completed_at'] !== null;
                        ?>
                            <tr>
                                <td data-label="#"><?php echo $index + 1; ?></td>
                                <td data-label="Date & Time">
                                    <?php 
                                    if ($isCompleted) {
                                        echo date('M d, Y - g:i A', strtotime($session['completed_at']));
                                    } else {
                                        echo date('M d, Y - g:i A', strtotime($session['started_at']));
                                    }
                                    ?>
                                </td>
                                <td data-label="Correct Matches">
                                    <?php echo $session['total_correct']; ?> / <?php echo $session['total_pairs']; ?>
                                </td>
                                <td data-label="Accuracy">
                                    <?php echo number_format($percentage, 1); ?>%
                                </td>
                                <td data-label="Score">
                                    <strong style="color: #26890D; font-size: 18px;">
                                        <?php echo number_format($session['total_score']); ?>
                                    </strong>
                                </td>
                                <td data-label="Status">
                                    <?php if ($isCompleted): ?>
                                        <span class="score-badge <?php echo $scoreClass; ?>">
                                            Completed
                                        </span>
                                    <?php else: ?>
                                        <span class="score-badge score-poor">
                                            Incomplete
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                
           
                
            <?php else: ?>
                <div class="no-sessions">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                    <p>You haven't attempted this game yet.</p>
                    
                    <?php if ($game['status'] === 'active'): ?>
                        <a href="play-matching-game.php?matching_game_id=<?php echo $matching_game_id; ?>" class="play-again-btn">
                            <i class="fas fa-play"></i> Start Game
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
