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
          WHERE mg.matching_game_id = ? AND mg.status = 'active'
          AND (mg.due_date IS NULL OR mg.due_date >= NOW())";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: available-games.php?error=game_not_found");
    exit();
}

// Check if student has already completed this game (one attempt only)
$checkAttemptQuery = "SELECT session_id FROM matching_sessions 
                      WHERE matching_game_id = ? AND student_id = ? AND completed_at IS NOT NULL
                      LIMIT 1";
$checkStmt = $pdo->prepare($checkAttemptQuery);
$checkStmt->execute([$matching_game_id, $student_id]);
$existingAttempt = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existingAttempt) {
    header("Location: view-matching-results.php?matching_game_id=" . $matching_game_id . "&already_played=1");
    exit();
}

if (!empty($game['due_date'])) {
    $due_date_obj = new DateTime($game['due_date']);
    if ($due_date_obj <= new DateTime()) {
        header("Location: available-games.php?error=game_expired");
        exit();
    }
}

// Get matching pairs
$query = "SELECT * FROM matching_pairs WHERE matching_game_id = ? ORDER BY pair_order";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id]);
$pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($pairs) < 3) {
    die("This game doesn't have enough pairs to play. Please contact your teacher.");
}

// Create session
$query = "INSERT INTO matching_sessions (matching_game_id, student_id, total_pairs) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id, $student_id, count($pairs)]);
$session_id = $pdo->lastInsertId();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($game['title']); ?> - Matching Game</title>
    <link rel="icon" type="image/png" href="../../assets/tablogo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .game-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .game-header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .game-title {
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .game-title i {
            color: #00d4ff;
        }
        
        .back-btn {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        .game-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .info-item i {
            color: #00d4ff;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .stat-box {
            background: rgba(0,0,0,0.3);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #00d4ff;
        }
        
        .stat-value.timer {
            color: #ff6b6b;
        }
        
        .stat-value.score {
            color: #00ff88;
        }
        
        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .progress-container {
            margin-top: 20px;
        }
        
        .progress-bar {
            background: rgba(0,0,0,0.3);
            height: 12px;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00d4ff, #00ff88);
            transition: width 0.5s ease;
            border-radius: 6px;
        }
        
        .progress-text {
            text-align: center;
            margin-top: 8px;
            font-size: 14px;
            opacity: 0.8;
        }
        
        .matching-board {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .column {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .column-title {
            text-align: center;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(0,212,255,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .column-title i {
            color: #00d4ff;
        }
        
        .item {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 12px;
            cursor: grab;
            transition: all 0.3s;
            border: 2px solid transparent;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 16px;
            font-weight: 500;
            position: relative;
        }
        
        .item:active {
            cursor: grabbing;
        }
        
        .item:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            border-color: rgba(0,212,255,0.5);
        }
        
        .item.dragging {
            opacity: 0.5;
            cursor: grabbing;
            transform: scale(1.05);
        }
        
        .item.matched {
            background: rgba(0,255,136,0.2);
            border-color: #00ff88;
            cursor: default;
            pointer-events: none;
        }
        
        .item.matched::after {
            content: '✓';
            position: absolute;
            top: 8px;
            right: 12px;
            font-size: 20px;
            color: #00ff88;
        }
        
        .item.incorrect {
            background: rgba(255,107,107,0.2);
            border-color: #ff6b6b;
            cursor: default;
            pointer-events: none;
        }
        
        .item.incorrect::after {
            content: '✗';
            position: absolute;
            top: 8px;
            right: 12px;
            font-size: 20px;
            color: #ff6b6b;
        }
        
        .item img {
            max-width: 100%;
            max-height: 120px;
            border-radius: 8px;
        }
        
        .drop-zone {
            min-height: 80px;
            border: 2px dashed rgba(255,255,255,0.3);
            border-radius: 12px;
            margin-bottom: 15px;
            padding: 15px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.2);
        }
        
        .drop-zone .target-item {
            text-align: center;
            opacity: 0.9;
        }
        
        .drop-zone .target-item img {
            max-width: 100%;
            max-height: 120px;
            border-radius: 8px;
        }
        
        .drop-zone.drag-over {
            border-color: #00d4ff;
            background: rgba(0,212,255,0.1);
            box-shadow: 0 0 20px rgba(0,212,255,0.3);
        }
        
        .drop-zone.has-item {
            border-style: solid;
            border-color: rgba(0,212,255,0.5);
        }
        
        .game-controls {
            text-align: center;
            margin: 25px 0;
        }
        
        .btn {
            padding: 15px 40px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: white;
            box-shadow: 0 4px 15px rgba(0,212,255,0.4);
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,212,255,0.5);
        }
        
        .btn-primary:disabled {
            background: rgba(255,255,255,0.2);
            cursor: not-allowed;
            box-shadow: none;
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
        }
        
        /* Results Overlay */
        .results-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            backdrop-filter: blur(10px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .results-modal {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            padding: 50px;
            border-radius: 24px;
            text-align: center;
            max-width: 550px;
            width: 90%;
            border: 1px solid rgba(255,255,255,0.2);
            animation: modalSlide 0.5s ease;
        }
        
        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .results-title {
            font-size: 42px;
            margin-bottom: 30px;
        }
        
        .results-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .result-stat {
            background: rgba(0,0,0,0.3);
            padding: 25px;
            border-radius: 16px;
        }
        
        .result-stat .value {
            font-size: 36px;
            font-weight: 700;
            color: #00d4ff;
        }
        
        .result-stat .value.correct {
            color: #00ff88;
        }
        
        .result-stat .value.time {
            color: #ffc107;
        }
        
        .result-stat .value.score {
            color: #00d4ff;
        }
        
        .result-stat .label {
            font-size: 14px;
            opacity: 0.7;
            margin-top: 8px;
            text-transform: uppercase;
        }
        
        .result-message {
            font-size: 18px;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(0,255,136,0.1);
            border-radius: 12px;
            border: 1px solid rgba(0,255,136,0.3);
        }
        
        .result-message.failed {
            background: rgba(255,107,107,0.1);
            border-color: rgba(255,107,107,0.3);
        }
        
        .results-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Keyboard hints */
        .keyboard-hint {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            opacity: 0.6;
        }
        
        .keyboard-hint kbd {
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 6px;
            margin: 0 5px;
        }
        
        /* Animations */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes countPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        
        .bounce {
            animation: bounce 0.5s ease-in-out;
        }
        
        /* ============================================
           RESPONSIVE DESIGN
           ============================================ */
        
        /* Mobile Phones (up to 575px) */
        @media (max-width: 575px) {
            body {
                padding: 10px;
            }
            
            .game-header {
                padding: 15px;
                border-radius: 12px;
            }
            
            .header-top {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .game-title {
                font-size: 16px;
                justify-content: center;
            }
            
            .back-btn {
                padding: 8px 16px;
                font-size: 14px;
            }
            
            .game-info {
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }
            
            .info-item {
                font-size: 12px;
            }
            
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .stat-box {
                padding: 10px;
            }
            
            .stat-value {
                font-size: 20px;
            }
            
            .stat-label {
                font-size: 10px;
            }
            
            /* Matching Board */
            .matching-board {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .column-title {
                font-size: 14px;
                padding: 10px;
            }
            
            .definition-item,
            .term-item {
                padding: 12px;
                font-size: 13px;
                min-height: 50px;
            }
            
            .drop-zone {
                padding: 10px;
                min-height: 45px;
            }
            
            .placed-term {
                font-size: 12px;
                padding: 8px;
            }
            
            /* Start Overlay */
            .start-overlay h2 {
                font-size: 22px;
            }
            
            .start-overlay p {
                font-size: 14px;
            }
            
            .start-btn {
                padding: 14px 35px;
                font-size: 16px;
            }
            
            /* Results Modal */
            .results-modal {
                padding: 25px 15px;
                width: 95%;
            }
            
            .results-modal h2 {
                font-size: 24px;
            }
            
            .results-stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .results-stat {
                padding: 15px;
            }
            
            .results-stat .value {
                font-size: 28px;
            }
            
            .results-stat .label {
                font-size: 12px;
            }
            
            .results-btn {
                padding: 12px 25px;
                font-size: 14px;
                width: 100%;
            }
            
            .results-buttons {
                flex-direction: column;
                gap: 10px;
            }
        }
        
        /* Tablets Portrait (576px to 767px) */
        @media (min-width: 576px) and (max-width: 767px) {
            body {
                padding: 15px;
            }
            
            .game-header {
                padding: 20px;
            }
            
            .header-top {
                flex-direction: column;
                gap: 15px;
            }
            
            .game-title {
                font-size: 20px;
            }
            
            .stats-row {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .matching-board {
                grid-template-columns: 1fr;
            }
            
            .results-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Tablets Landscape and up (768px+) */
        @media (min-width: 768px) {
            .matching-board {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .results-stats {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .term-item,
            .definition-item,
            .drop-zone,
            .start-btn,
            .results-btn,
            .back-btn {
                min-height: 44px;
            }
            
            .term-item {
                cursor: grab;
            }
            
            /* Better touch targets */
            .term-item:active {
                transform: scale(1.02);
            }
        }
        
        /* Landscape Mode on Mobile */
        @media (orientation: landscape) and (max-height: 500px) {
            body {
                padding: 8px;
            }
            
            .game-header {
                padding: 10px;
                margin-bottom: 10px;
            }
            
            .header-top {
                flex-direction: row;
            }
            
            .game-info {
                display: none;
            }
            
            .stats-row {
                gap: 8px;
            }
            
            .stat-box {
                padding: 8px;
            }
            
            .stat-value {
                font-size: 16px;
            }
            
            .matching-board {
                gap: 10px;
            }
            
            .definition-item,
            .term-item {
                padding: 8px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="game-container">
        <div class="game-header">
            <div class="header-top">
                <div class="game-title">
                    <i class="fas fa-puzzle-piece"></i>
                    <?php echo htmlspecialchars($game['title']); ?>
                </div>
                <a href="my-scores.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            
            <div class="game-info">
                <span class="info-item"><i class="fas fa-book"></i> <?php echo htmlspecialchars($game['subject_name']); ?></span>
                <span class="info-item"><i class="fas fa-user"></i> <?php echo htmlspecialchars($game['teacher_name']); ?></span>
                <?php if (!empty($game['due_date'])): ?>
                    <span class="info-item"><i class="fas fa-calendar"></i> Due: <?php echo date('M d, Y g:i A', strtotime($game['due_date'])); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-value timer" id="timerDisplay"><?php echo $game['time_limit']; ?></div>
                    <div class="stat-label">Seconds Left</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" id="placedDisplay">0</div>
                    <div class="stat-label">Pairs Placed</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value correct" id="correctDisplay">0</div>
                    <div class="stat-label">Correct</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value score" id="scoreDisplay">0</div>
                    <div class="stat-label">Score</div>
                </div>
            </div>
            
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressBar" style="width: 0%"></div>
                </div>
                <div class="progress-text" id="progressText">0 / <?php echo count($pairs); ?> Placed</div>
            </div>
        </div>
        
        <div class="game-controls">
            <button class="btn btn-primary" id="checkBtn" onclick="checkAnswers()" disabled>
                <i class="fas fa-check-circle"></i> Check My Answers
            </button>
        </div>
        
        <div class="matching-board">
            <div class="column">
                <div class="column-title">
                    <i class="fas fa-hand-pointer"></i> Drag These Items
                </div>
                <div id="left-column">
                    <?php 
                    $shuffled_pairs = $pairs;
                    shuffle($shuffled_pairs);
                    foreach ($shuffled_pairs as $pair): 
                    ?>
                        <div class="item" draggable="true" data-pair-id="<?php echo $pair['pair_id']; ?>">
                            <?php if ($pair['left_item_image']): ?>
                                <img src="../../<?php echo htmlspecialchars($pair['left_item_image']); ?>" alt="Item">
                            <?php else: ?>
                                <?php echo htmlspecialchars(isset($pair['left_item_text']) && trim($pair['left_item_text']) !== '' ? $pair['left_item_text'] : ''); ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="column">
                <div class="column-title">
                    <i class="fas fa-bullseye"></i> Drop to Match
                </div>
                <div id="right-column">
                    <?php 
                    $right_items = $pairs;
                    shuffle($right_items);
                    foreach ($right_items as $pair): 
                    ?>
                        <div class="drop-zone" data-pair-id="<?php echo $pair['pair_id']; ?>">
                            <div class="target-item">
                                <?php if ($pair['right_item_image']): ?>
                                    <img src="../../<?php echo htmlspecialchars($pair['right_item_image']); ?>" alt="Target">
                                <?php else: ?>
                                    <?php echo htmlspecialchars(isset($pair['right_item_text']) && trim($pair['right_item_text']) !== '' ? $pair['right_item_text'] : ''); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="keyboard-hint">
            Press <kbd>Esc</kbd> to go back
        </div>
    </div>
    
    <!-- Results Overlay -->
    <div class="results-overlay" id="resultsOverlay">
        <div class="results-modal">
            <h2 class="results-title" id="resultsTitle">🎉 Game Complete!</h2>
            
            <div class="results-stats">
                <div class="result-stat">
                    <div class="value correct" id="resultCorrect">0</div>
                    <div class="label">Correct Matches</div>
                </div>
                <div class="result-stat">
                    <div class="value" id="resultTotal"><?php echo count($pairs); ?></div>
                    <div class="label">Total Pairs</div>
                </div>
                <div class="result-stat">
                    <div class="value time" id="resultTime">0s</div>
                    <div class="label">Time Taken</div>
                </div>
                <div class="result-stat">
                    <div class="value score" id="resultScore">0</div>
                    <div class="label">Total Score</div>
                </div>
            </div>
            
            <div class="result-message" id="resultMessage">
                Great job! Keep practicing to improve your matching skills!
            </div>
            
            <div class="results-actions">
                <a href="my-scores.php" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i> View My Scores
                </a>
                <a href="../student-home.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Game configuration
        const config = {
            sessionId: <?php echo $session_id; ?>,
            totalPairs: <?php echo count($pairs); ?>,
            timeLimit: <?php echo $game['time_limit']; ?>,
            pointsPerPair: <?php echo isset($game['points_per_pair']) ? $game['points_per_pair'] : 100; ?>
        };
        
        // Game state
        let gameState = {
            timeRemaining: config.timeLimit,
            placedCount: 0,
            correctCount: 0,
            score: 0,
            startTime: Date.now(),
            timerInterval: null,
            gameChecked: false
        };
        
        // DOM elements
        const elements = {
            timerDisplay: document.getElementById('timerDisplay'),
            placedDisplay: document.getElementById('placedDisplay'),
            correctDisplay: document.getElementById('correctDisplay'),
            scoreDisplay: document.getElementById('scoreDisplay'),
            progressBar: document.getElementById('progressBar'),
            progressText: document.getElementById('progressText'),
            checkBtn: document.getElementById('checkBtn'),
            resultsOverlay: document.getElementById('resultsOverlay'),
            resultsTitle: document.getElementById('resultsTitle'),
            resultCorrect: document.getElementById('resultCorrect'),
            resultTime: document.getElementById('resultTime'),
            resultScore: document.getElementById('resultScore'),
            resultMessage: document.getElementById('resultMessage')
        };
        
        // Start timer
        gameState.timerInterval = setInterval(() => {
            gameState.timeRemaining--;
            elements.timerDisplay.textContent = gameState.timeRemaining;
            
            if (gameState.timeRemaining <= 10) {
                elements.timerDisplay.style.animation = 'countPulse 0.5s ease-in-out infinite';
            }
            
            if (gameState.timeRemaining <= 0) {
                checkAnswers();
            }
        }, 1000);
        
        // Drag and drop functionality
        const items = document.querySelectorAll('.item');
        const dropZones = document.querySelectorAll('.drop-zone');
        
        items.forEach(item => {
            item.addEventListener('dragstart', dragStart);
            item.addEventListener('dragend', dragEnd);
            
            // Touch support
            item.addEventListener('touchstart', touchStart, { passive: false });
            item.addEventListener('touchmove', touchMove, { passive: false });
            item.addEventListener('touchend', touchEnd);
        });
        
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', dragOver);
            zone.addEventListener('dragleave', dragLeave);
            zone.addEventListener('drop', drop);
        });
        
        let draggedItem = null;
        let touchStartX, touchStartY;
        
        function dragStart(e) {
            if (gameState.gameChecked) return;
            draggedItem = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.pairId);
        }
        
        function dragEnd(e) {
            this.classList.remove('dragging');
            draggedItem = null;
        }
        
        function dragOver(e) {
            if (gameState.gameChecked) return;
            e.preventDefault();
            this.classList.add('drag-over');
        }
        
        function dragLeave(e) {
            this.classList.remove('drag-over');
        }
        
        function drop(e) {
            if (gameState.gameChecked) return;
            e.preventDefault();
            this.classList.remove('drag-over');
            
            const draggedPairId = e.dataTransfer.getData('text/plain');
            const draggedEl = document.querySelector(`.item[data-pair-id="${draggedPairId}"]`);
            
            handleDrop(this, draggedEl);
        }
        
        // Touch support functions
        function touchStart(e) {
            if (gameState.gameChecked) return;
            draggedItem = this;
            const touch = e.touches[0];
            touchStartX = touch.clientX;
            touchStartY = touch.clientY;
            this.classList.add('dragging');
        }
        
        function touchMove(e) {
            if (!draggedItem || gameState.gameChecked) return;
            e.preventDefault();
            
            const touch = e.touches[0];
            const x = touch.clientX;
            const y = touch.clientY;
            
            draggedItem.style.position = 'fixed';
            draggedItem.style.left = (x - 50) + 'px';
            draggedItem.style.top = (y - 50) + 'px';
            draggedItem.style.zIndex = '1000';
            
            // Highlight drop zones
            dropZones.forEach(zone => {
                const rect = zone.getBoundingClientRect();
                if (x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom) {
                    zone.classList.add('drag-over');
                } else {
                    zone.classList.remove('drag-over');
                }
            });
        }
        
        function touchEnd(e) {
            if (!draggedItem || gameState.gameChecked) return;
            
            draggedItem.style.position = '';
            draggedItem.style.left = '';
            draggedItem.style.top = '';
            draggedItem.style.zIndex = '';
            draggedItem.classList.remove('dragging');
            
            // Find drop zone
            const touch = e.changedTouches[0];
            const x = touch.clientX;
            const y = touch.clientY;
            
            dropZones.forEach(zone => {
                zone.classList.remove('drag-over');
                const rect = zone.getBoundingClientRect();
                if (x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom) {
                    handleDrop(zone, draggedItem);
                }
            });
            
            draggedItem = null;
        }
        
        function handleDrop(dropZone, draggedEl) {
            // Check if this drop zone already has an item
            const existingItem = dropZone.querySelector('.item');
            if (existingItem) {
                document.getElementById('left-column').appendChild(existingItem);
                gameState.placedCount--;
            }
            
            // Place the dragged item in this drop zone
            dropZone.appendChild(draggedEl);
            dropZone.classList.add('has-item');
            gameState.placedCount++;
            
            updateProgress();
        }
        
        function updateProgress() {
            elements.placedDisplay.textContent = gameState.placedCount;
            elements.progressText.textContent = `${gameState.placedCount} / ${config.totalPairs} Placed`;
            elements.progressBar.style.width = `${(gameState.placedCount / config.totalPairs) * 100}%`;
            
            // Enable check button when all items are placed
            elements.checkBtn.disabled = gameState.placedCount < config.totalPairs;
        }
        
        function checkAnswers() {
            if (gameState.gameChecked) return;
            gameState.gameChecked = true;
            clearInterval(gameState.timerInterval);
            
            const responses = [];
            
            // Check each drop zone
            dropZones.forEach(zone => {
                const correctPairId = zone.dataset.pairId;
                const placedItem = zone.querySelector('.item');
                
                if (placedItem) {
                    const placedPairId = placedItem.dataset.pairId;
                    const isCorrect = placedPairId === correctPairId;
                    
                    if (isCorrect) {
                        placedItem.classList.add('matched');
                        placedItem.classList.add('bounce');
                        gameState.correctCount++;
                        gameState.score += config.pointsPerPair;
                    } else {
                        placedItem.classList.add('incorrect');
                        placedItem.classList.add('shake');
                    }
                    
                    responses.push({
                        pair_id: correctPairId,
                        student_answer: placedPairId,
                        is_correct: isCorrect
                    });
                }
            });
            
            // Calculate final score with time bonus
            const timeTaken = Math.floor((Date.now() - gameState.startTime) / 1000);
            const timeBonus = Math.max(0, (config.timeLimit - timeTaken) * 5);
            gameState.score += timeBonus;
            
            // Update displays
            elements.correctDisplay.textContent = gameState.correctCount;
            elements.scoreDisplay.textContent = gameState.score;
            
            // Show results after animation
            setTimeout(() => {
                showResults(timeTaken, responses);
            }, 1500);
        }
        
        function showResults(timeTaken, responses) {
            // Save results
            fetch('save-matching-results.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: config.sessionId,
                    total_correct: gameState.correctCount,
                    time_taken: timeTaken,
                    total_score: gameState.score,
                    completed: true,
                    responses: responses
                })
            });
            
            // Update modal
            const percentage = Math.round((gameState.correctCount / config.totalPairs) * 100);
            
            if (percentage >= 80) {
                elements.resultsTitle.textContent = '🎉 Excellent!';
                elements.resultMessage.textContent = 'Outstanding performance! You matched most pairs correctly!';
                elements.resultMessage.classList.remove('failed');
            } else if (percentage >= 50) {
                elements.resultsTitle.textContent = '👍 Good Job!';
                elements.resultMessage.textContent = 'Nice work! Keep practicing to improve your skills!';
                elements.resultMessage.classList.remove('failed');
            } else {
                elements.resultsTitle.textContent = '💪 Keep Trying!';
                elements.resultMessage.textContent = 'Don\'t give up! Practice makes perfect!';
                elements.resultMessage.classList.add('failed');
            }
            
            elements.resultCorrect.textContent = gameState.correctCount;
            elements.resultTime.textContent = timeTaken + 's';
            elements.resultScore.textContent = gameState.score;
            
            elements.resultsOverlay.style.display = 'flex';
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = 'my-scores.php';
            }
        });
    </script>
</body>
</html>
