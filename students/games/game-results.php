<?php
session_start();
include '../../Database/database.php';

// Check if user is STUDENT (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: ../stud-login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? 0;
$game_id = $_GET['game_id'] ?? 0;

// Get session results - either by session_id or by game_id (most recent completed session)
if ($session_id > 0) {
    $query = "SELECT gs.*, ga.title, ga.show_leaderboard, ga.due_date, s.subject_name
              FROM game_sessions gs
              INNER JOIN game_activities ga ON gs.game_id = ga.game_id
              INNER JOIN subjects s ON ga.subject_id = s.subject_id
              WHERE gs.session_id = ? AND gs.student_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$session_id, $student_id]);
} else if ($game_id > 0) {
    $query = "SELECT gs.*, ga.title, ga.show_leaderboard, ga.due_date, s.subject_name
              FROM game_sessions gs
              INNER JOIN game_activities ga ON gs.game_id = ga.game_id
              INNER JOIN subjects s ON ga.subject_id = s.subject_id
              WHERE gs.game_id = ? AND gs.student_id = ? AND gs.completed_at IS NOT NULL
              ORDER BY gs.completed_at DESC
              LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$game_id, $student_id]);
} else {
    header("Location: available-games.php");
    exit();
}

$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    header("Location: available-games.php");
    exit();
}

// Calculate accuracy
$accuracy = $session['total_questions'] > 0 ? round(($session['total_correct'] / $session['total_questions']) * 100, 1) : 0;

// Get student's responses with questions and options
$responses_query = "SELECT 
                    gq.question_id,
                    gq.question_text,
                    gq.time_limit,
                    gq.points,
                    gr.response_id,
                    gr.selected_option_id,
                    gr.is_correct,
                    gr.time_taken,
                    gr.points_earned,
                    go_selected.option_text as selected_answer,
                    go_correct.option_text as correct_answer,
                    go_correct.option_id as correct_option_id
                    FROM game_questions gq
                    LEFT JOIN game_responses gr ON gq.question_id = gr.question_id 
                        AND gr.session_id = ?
                    LEFT JOIN game_options go_selected ON gr.selected_option_id = go_selected.option_id
                    LEFT JOIN game_options go_correct ON gq.question_id = go_correct.question_id AND go_correct.is_correct = 1
                    WHERE gq.game_id = ?
                    ORDER BY gq.question_order";
$stmt = $pdo->prepare($responses_query);
$stmt->execute([$session['session_id'], $session['game_id']]);
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$has_session_responses = false;
foreach ($responses as $response) {
    if (!empty($response['response_id'])) {
        $has_session_responses = true;
        break;
    }
}

if (!$has_session_responses) {
    $session_start = $session['started_at'] ?? null;
    $session_end = $session['completed_at'] ?? null;
    $fallback_query = "SELECT 
                        gq.question_id,
                        gq.question_text,
                        gq.time_limit,
                        gq.points,
                        gr.response_id,
                        gr.selected_option_id,
                        gr.is_correct,
                        gr.time_taken,
                        gr.points_earned,
                        go_selected.option_text as selected_answer,
                        go_correct.option_text as correct_answer,
                        go_correct.option_id as correct_option_id
                        FROM game_questions gq
                        LEFT JOIN (
                            SELECT gr_latest.*
                            FROM game_responses gr_latest
                            INNER JOIN (
                                SELECT question_id, MAX(answered_at) AS max_answered_at
                                FROM game_responses
                                WHERE game_id = ?
                                  AND student_id = ?
                                  AND (? IS NULL OR answered_at >= ?)
                                  AND (? IS NULL OR answered_at <= ?)
                                GROUP BY question_id
                            ) latest ON gr_latest.question_id = latest.question_id AND gr_latest.answered_at = latest.max_answered_at
                            WHERE gr_latest.game_id = ? AND gr_latest.student_id = ?
                        ) gr ON gq.question_id = gr.question_id
                        LEFT JOIN game_options go_selected ON gr.selected_option_id = go_selected.option_id
                        LEFT JOIN game_options go_correct ON gq.question_id = go_correct.question_id AND go_correct.is_correct = 1
                        WHERE gq.game_id = ?
                        ORDER BY gq.question_order";
    $stmt = $pdo->prepare($fallback_query);
    $stmt->execute([
        $session['game_id'],
        $student_id,
        $session_start,
        $session_start,
        $session_end,
        $session_end,
        $session['game_id'],
        $student_id,
        $session['game_id']
    ]);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get leaderboard if enabled
$leaderboard = [];
if ($session['show_leaderboard']) {
    $leaderboard_query = "SELECT 
                          CONCAT(u.first_name, ' ', u.last_name) as student_name,
                          gs.total_score,
                          gs.student_id
                          FROM game_sessions gs
                          INNER JOIN users u ON gs.student_id = u.user_id
                          WHERE gs.game_id = ? AND gs.completed_at IS NOT NULL
                          ORDER BY gs.total_score DESC
                          LIMIT 10";
    $stmt = $pdo->prepare($leaderboard_query);
    $stmt->execute([$session['game_id']]);
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Find user's rank
    $user_rank = 0;
    foreach ($leaderboard as $index => $player) {
        if ($player['student_id'] == $student_id) {
            $user_rank = $index + 1;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/tablogo.png">
    <title>Game Results</title>
    <link rel="stylesheet" href="../student-home.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .results-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .results-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .game-over {
            font-size: 48px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .game-title {
            font-size: 24px;
            color: #666;
            margin-bottom: 40px;
        }
        
        .score-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 40px;
        }
        
        .score-label {
            font-size: 18px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .score-value {
            font-size: 72px;
            font-weight: bold;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-item {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #26890D;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        .leaderboard-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        
        .leaderboard-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .leaderboard-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .leaderboard-item:last-child {
            border-bottom: none;
        }
        
        .leaderboard-item.current-user {
            background: #fff3cd;
            border-radius: 8px;
            margin: 5px 0;
        }
        
        .rank {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .rank-1 { background: #ffd700; color: #000; }
        .rank-2 { background: #c0c0c0; color: #000; }
        .rank-3 { background: #cd7f32; color: #fff; }
        .rank-other { background: #e9ecef; color: #666; }
        
        .player-name {
            flex: 1;
            font-weight: 500;
        }
        
        .player-score {
            font-weight: bold;
            color: #26890D;
            font-size: 18px;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn {
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            display: inline-block;
        }
        
        .btn-primary {
            background: #26890D;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e6a0a;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-replay {
            background: #007bff;
            color: white;
        }
        
        .btn-replay:hover {
            background: #0056b3;
        }
        
        .celebration {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .review-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .review-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 25px;
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        
        .review-item {
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .review-item.correct {
            background: #f0fff4;
            border-color: #48bb78;
        }
        
        .review-item.incorrect {
            background: #fff5f5;
            border-color: #f56565;
        }
        
        .question-number {
            font-weight: bold;
            color: #667eea;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .question-text {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .answer-section {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .your-answer, .correct-answer {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 8px;
        }
        
        .your-answer {
            background: rgba(0, 0, 0, 0.03);
        }
        
        .review-item.correct .your-answer {
            background: rgba(72, 187, 120, 0.1);
        }
        
        .review-item.incorrect .your-answer {
            background: rgba(245, 101, 101, 0.1);
        }
        
        .correct-answer {
            background: rgba(72, 187, 120, 0.1);
        }
        
        .answer-label {
            font-weight: 600;
            margin-right: 10px;
            min-width: 120px;
            color: #666;
        }
        
        .answer-value {
            flex: 1;
            font-weight: 500;
        }
        
        .review-item.correct .your-answer .answer-value {
            color: #48bb78;
        }
        
        .review-item.incorrect .your-answer .answer-value {
            color: #f56565;
        }
        
        .correct-answer .answer-value {
            color: #48bb78;
        }
        
        .points-earned {
            text-align: right;
            font-weight: bold;
            font-size: 16px;
            color: #667eea;
            margin-top: 5px;
        }
        
        /* ============================================
           RESPONSIVE DESIGN FOR GAME RESULTS
           ============================================ */
        
        /* Mobile Phones (up to 575px) */
        @media (max-width: 575px) {
            body {
                padding: 10px;
            }
            
            .results-container {
                max-width: 100%;
            }
            
            .results-card,
            .leaderboard-card,
            .review-card {
                padding: 20px;
                border-radius: 15px;
            }
            
            .celebration {
                font-size: 50px;
            }
            
            .game-over {
                font-size: 28px;
            }
            
            .game-title {
                font-size: 18px;
            }
            
            .score-display {
                margin: 20px 0;
            }
            
            .score-label {
                font-size: 16px;
            }
            
            .score-value {
                font-size: 42px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .stat-value {
                font-size: 28px;
            }
            
            .stat-label {
                font-size: 13px;
            }
            
            .actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .leaderboard-title,
            .review-title {
                font-size: 22px;
            }
            
            .leaderboard-item {
                padding: 10px;
                font-size: 14px;
            }
            
            .rank {
                width: 30px;
                height: 30px;
                font-size: 14px;
            }
            
            .player-name {
                font-size: 14px;
            }
            
            .player-score {
                font-size: 16px;
            }
            
            .review-item {
                padding: 15px;
            }
            
            .question-text {
                font-size: 16px;
            }
            
            .answer-label {
                min-width: 100px;
                font-size: 14px;
            }
            
            .your-answer,
            .correct-answer {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
        
        /* Tablets Portrait (576px to 767px) */
        @media (min-width: 576px) and (max-width: 767px) {
            .results-container {
                max-width: 550px;
            }
            
            .results-card,
            .leaderboard-card,
            .review-card {
                padding: 30px;
            }
            
            .celebration {
                font-size: 60px;
            }
            
            .game-over {
                font-size: 32px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .actions {
                flex-wrap: wrap;
            }
            
            .btn {
                flex: 1;
                min-width: 140px;
            }
        }
        
        /* Tablets Landscape (768px to 991px) */
        @media (min-width: 768px) and (max-width: 991px) {
            .results-container {
                max-width: 700px;
            }
            
            .results-card,
            .leaderboard-card,
            .review-card {
                padding: 40px;
            }
        }
        
        /* Laptops (992px to 1199px) */
        @media (min-width: 992px) and (max-width: 1199px) {
            .results-container {
                max-width: 750px;
            }
        }
        
        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .btn {
                min-height: 48px;
                padding: 15px 25px;
            }
            
            .leaderboard-item:hover {
                transform: none;
            }
        }
        
        /* Landscape Orientation for Mobile */
        @media (orientation: landscape) and (max-height: 600px) {
            .celebration {
                font-size: 40px;
                margin-bottom: 10px;
            }
            
            .game-over {
                font-size: 24px;
                margin-bottom: 10px;
            }
            
            .score-display {
                margin: 15px 0;
            }
            
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="results-container">
        <div class="results-card">
            <?php if ($accuracy >= 80): ?>
                <div class="celebration">🎉</div>
            <?php elseif ($accuracy >= 50): ?>
                <div class="celebration">👍</div>
            <?php else: ?>
                <div class="celebration">💪</div>
            <?php endif; ?>
            
            <div class="game-over">Game Complete!</div>
            <div class="game-title"><?php echo htmlspecialchars($session['title']); ?></div>
            <?php if (!empty($session['due_date'])): ?>
                <div style="color: #888; margin-bottom: 20px; font-size: 15px;">
                    Due: <?php echo date('M d, Y g:i A', strtotime($session['due_date'])); ?>
                </div>
            <?php endif; ?>
            
            <div class="score-display">
                <div class="score-label">Your Score</div>
                <div class="score-value"><?php echo $session['total_score']; ?></div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $session['total_correct']; ?>/<?php echo $session['total_questions']; ?></div>
                    <div class="stat-label">Correct Answers</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value"><?php echo $accuracy; ?>%</div>
                    <div class="stat-label">Accuracy</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value">
                        <?php 
                        if (isset($user_rank) && $user_rank > 0) {
                            echo '#' . $user_rank;
                        } else {
                            echo '-';
                        }
                        ?>
                    </div>
                    <div class="stat-label">Your Rank</div>
                </div>
            </div>
            
            <div class="actions">
                <a href="available-games.php" class="btn btn-primary">
                    🎮 More Games
                </a>
                <a href="../student-home.php" class="btn btn-secondary">
                    🏠 Home
                </a>
            </div>
        </div>
        
        <?php if ($session['show_leaderboard'] && count($leaderboard) > 0): ?>
            <div class="leaderboard-card">
                <div class="leaderboard-title">🏆 Leaderboard</div>
                <ul class="leaderboard-list">
                    <?php foreach ($leaderboard as $index => $player): 
                        $rank = $index + 1;
                        $rank_class = $rank <= 3 ? "rank-$rank" : "rank-other";
                        $is_current = $player['student_id'] == $student_id;
                    ?>
                        <li class="leaderboard-item <?php echo $is_current ? 'current-user' : ''; ?>">
                            <span class="rank <?php echo $rank_class; ?>"><?php echo $rank; ?></span>
                            <span class="player-name">
                                <?php echo htmlspecialchars($player['student_name']); ?>
                                <?php if ($is_current): ?><strong> (You)</strong><?php endif; ?>
                            </span>
                            <span class="player-score"><?php echo $player['total_score']; ?> pts</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (count($responses) > 0): ?>
            <div class="review-card">
                <div class="review-title">📝 Answer Review</div>
                <?php foreach ($responses as $index => $response): 
                    $answered = !empty($response['response_id']);
                    $is_correct = $answered && (bool) $response['is_correct'];
                    $earned_points = $answered ? (int) ($response['points_earned'] ?? 0) : 0;
                ?>
                    <div class="review-item <?php echo $is_correct ? 'correct' : 'incorrect'; ?>">
                        <div class="question-number">Question <?php echo $index + 1; ?></div>
                        <div class="question-text"><?php echo htmlspecialchars($response['question_text']); ?></div>
                        
                        <div class="answer-section">
                            <div class="your-answer">
                                <span class="answer-label">Your Answer:</span>
                                <span class="answer-value">
                                    <?php if ($answered): ?>
                                        <?php echo $is_correct ? '✓' : '✗'; ?>
                                        <?php echo htmlspecialchars($response['selected_answer']); ?>
                                    <?php else: ?>
                                        — No answer submitted
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if (!$is_correct && !empty($response['correct_answer'])): ?>
                                <div class="correct-answer">
                                    <span class="answer-label">Correct Answer:</span>
                                    <span class="answer-value">
                                        ✓ <?php echo htmlspecialchars($response['correct_answer']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="points-earned">
                                <?php echo $answered ? '+' . $earned_points : '0'; ?> pts
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
