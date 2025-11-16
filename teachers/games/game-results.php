<?php
session_start();
include '../../Database/database.php';

// Check if user is TEACHER (role_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../teacher-login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$game_id = $_GET['game_id'] ?? 0;

// Verify game ownership
$query = "SELECT ga.*, s.subject_name FROM game_activities ga
          INNER JOIN subjects s ON ga.subject_id = s.subject_id
          WHERE ga.game_id = ? AND ga.teacher_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$game_id, $teacher_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: manage-games.php");
    exit();
}

// Get game statistics
$stats_query = "SELECT 
                COUNT(DISTINCT gs.student_id) as total_players,
                AVG(gs.total_score) as avg_score,
                MAX(gs.total_score) as highest_score,
                AVG(gs.total_correct) as avg_correct
                FROM game_sessions gs
                WHERE gs.game_id = ? AND gs.completed_at IS NOT NULL";
$stmt = $pdo->prepare($stats_query);
$stmt->execute([$game_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get leaderboard
$leaderboard_query = "SELECT 
                      u.user_id,
                      CONCAT(u.first_name, ' ', u.last_name) as student_name,
                      gs.total_score,
                      gs.total_correct,
                      gs.total_questions,
                      gs.completed_at,
                      ROUND((gs.total_correct / gs.total_questions * 100), 1) as accuracy
                      FROM game_sessions gs
                      INNER JOIN users u ON gs.student_id = u.user_id
                      WHERE gs.game_id = ? AND gs.completed_at IS NOT NULL
                      ORDER BY gs.total_score DESC, gs.completed_at ASC
                      LIMIT 50";
$stmt = $pdo->prepare($leaderboard_query);
$stmt->execute([$game_id]);
$leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get question-by-question analysis
$questions_query = "SELECT 
                    gq.question_id,
                    gq.question_text,
                    gq.question_order,
                    COUNT(DISTINCT gr.student_id) as total_answers,
                    SUM(CASE WHEN gr.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                    ROUND(AVG(gr.time_taken), 1) as avg_time
                    FROM game_questions gq
                    LEFT JOIN game_responses gr ON gq.question_id = gr.question_id
                    WHERE gq.game_id = ?
                    GROUP BY gq.question_id
                    ORDER BY gq.question_order";
$stmt = $pdo->prepare($questions_query);
$stmt->execute([$game_id]);
$question_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Results - <?php echo htmlspecialchars($game['title']); ?></title>
    <link rel="stylesheet" href="../teacher-dashboard.css">
    <style>
        .results-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .game-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #26890D;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
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
        
        .accuracy-bar {
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .accuracy-fill {
            height: 100%;
            background: #26890D;
            transition: width 0.3s;
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
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="results-container">
        <a href="../teacher-acts.php" class="back-btn">← Back to Games</a>
        
        <div class="game-header">
            <h2><?php echo htmlspecialchars($game['title']); ?></h2>
            <p><?php echo htmlspecialchars($game['subject_name']); ?></p>
            <?php if (!empty($game['due_date'])): ?>
                <p><strong>Due:</strong> <?php echo date('M d, Y g:i A', strtotime($game['due_date'])); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ($stats['total_players'] > 0): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Players</div>
                    <div class="stat-value"><?php echo $stats['total_players']; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Average Score</div>
                    <div class="stat-value"><?php echo round($stats['avg_score']); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Highest Score</div>
                    <div class="stat-value"><?php echo $stats['highest_score']; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Avg. Correct</div>
                    <div class="stat-value"><?php echo round($stats['avg_correct'], 1); ?></div>
                </div>
            </div>
            
            <div class="section">
                <h3>🏆 Leaderboard</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Student</th>
                            <th>Score</th>
                            <th>Correct Answers</th>
                            <th>Accuracy</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard as $index => $player): 
                            $rank = $index + 1;
                            $rank_class = $rank <= 3 ? "rank-$rank" : "rank-other";
                        ?>
                            <tr>
                                <td>
                                    <span class="rank-badge <?php echo $rank_class; ?>">
                                        <?php echo $rank; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($player['student_name']); ?></td>
                                <td><strong><?php echo $player['total_score']; ?></strong></td>
                                <td><?php echo $player['total_correct']; ?> / <?php echo $player['total_questions']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="accuracy-bar" style="flex: 1;">
                                            <div class="accuracy-fill" style="width: <?php echo $player['accuracy']; ?>%"></div>
                                        </div>
                                        <span style="font-size: 12px; color: #666;"><?php echo $player['accuracy']; ?>%</span>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($player['completed_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <h3>📊 Question Analysis</h3>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Answered By</th>
                            <th>Correct</th>
                            <th>Success Rate</th>
                            <th>Avg. Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($question_stats as $q): 
                            $success_rate = $q['total_answers'] > 0 ? round(($q['correct_answers'] / $q['total_answers']) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?php echo $q['question_order']; ?></td>
                                <td><?php echo htmlspecialchars($q['question_text']); ?></td>
                                <td><?php echo $q['total_answers']; ?></td>
                                <td><?php echo $q['correct_answers']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="accuracy-bar" style="flex: 1;">
                                            <div class="accuracy-fill" style="width: <?php echo $success_rate; ?>%"></div>
                                        </div>
                                        <span style="font-size: 12px; color: #666;"><?php echo $success_rate; ?>%</span>
                                    </div>
                                </td>
                                <td><?php echo $q['avg_time'] ? $q['avg_time'] . 's' : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="section no-data">
                <h3>No Results Yet</h3>
                <p>No students have completed this game yet.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
