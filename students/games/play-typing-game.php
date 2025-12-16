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
          WHERE tg.typing_game_id = ? AND tg.status = 'active'
          AND (tg.due_date IS NULL OR tg.due_date >= NOW())";
$stmt = $pdo->prepare($query);
$stmt->execute([$typing_game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: available-games.php?error=game_not_found");
    exit();
}

// Check if student has already completed this game (one attempt only)
$checkAttemptQuery = "SELECT session_id, wpm, accuracy, total_score, completed_at 
                      FROM typing_sessions 
                      WHERE typing_game_id = ? AND student_id = ? AND completed_at IS NOT NULL
                      LIMIT 1";
$checkStmt = $pdo->prepare($checkAttemptQuery);
$checkStmt->execute([$typing_game_id, $student_id]);
$existingAttempt = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existingAttempt) {
    // Student already played - redirect to results page
    header("Location: typing-game-results.php?typing_game_id=" . $typing_game_id . "&already_played=1");
    exit();
}

// Check due date
if (!empty($game['due_date'])) {
    $due_date_obj = new DateTime($game['due_date']);
    if ($due_date_obj <= new DateTime()) {
        header("Location: available-games.php?error=game_expired");
        exit();
    }
}

// Get random typing text for this game
$query = "SELECT * FROM typing_texts WHERE typing_game_id = ? ORDER BY RAND() LIMIT 1";
$stmt = $pdo->prepare($query);
$stmt->execute([$typing_game_id]);
$typing_text = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$typing_text) {
    die("This game doesn't have any typing texts. Please contact your teacher.");
}

// Get student's best score for this game
$query = "SELECT MAX(wpm) as best_wpm, MAX(accuracy) as best_accuracy, MAX(total_score) as best_score
          FROM typing_sessions 
          WHERE typing_game_id = ? AND student_id = ? AND completed_at IS NOT NULL";
$stmt = $pdo->prepare($query);
$stmt->execute([$typing_game_id, $student_id]);
$best_scores = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($game['title']); ?> - Speed Typing Game</title>
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
        
        .game-container {
            max-width: 1000px;
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
        
        .game-title {
            font-size: 28px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .game-title::before {
            content: '⌨️';
            font-size: 32px;
        }
        
        .game-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #00d4ff;
        }
        
        .stat-value.wpm {
            color: #00ff88;
        }
        
        .stat-value.accuracy {
            color: #ffd700;
        }
        
        .stat-value.time {
            color: #ff6b6b;
        }
        
        .stat-label {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .typing-area {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }
        
        .text-display {
            font-family: 'Courier New', monospace;
            font-size: 20px;
            line-height: 1.8;
            color: #333;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            position: relative;
            min-height: 120px;
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
            word-wrap: break-word;
            word-break: break-word;
            white-space: pre-wrap;
            overflow-wrap: break-word;
        }
        
        .text-display::-webkit-scrollbar {
            width: 8px;
        }
        
        .text-display::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        
        .text-display::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.3);
            border-radius: 4px;
        }
        
        .text-display::-webkit-scrollbar-thumb:hover {
            background: rgba(0,0,0,0.5);
        }
        
        .text-display .char {
            transition: all 0.1s;
        }
        
        .text-display .char.correct {
            color: #28a745;
        }
        
        .text-display .char.incorrect {
            color: #dc3545;
            background: rgba(220, 53, 69, 0.2);
            border-radius: 2px;
        }
        
        .text-display .char.current {
            background: #007bff;
            color: white;
            border-radius: 2px;
            animation: blink 0.5s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .text-display .char.remaining {
            color: #666;
        }
        
        .input-area {
            position: relative;
        }
        
        .typing-input {
            width: 100%;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 20px;
            border: 3px solid #e0e0e0;
            border-radius: 12px;
            transition: all 0.3s;
            background: white;
        }
        
        .typing-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 4px rgba(0,123,255,0.2);
        }
        
        .typing-input.error {
            border-color: #dc3545;
            animation: shake 0.3s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .typing-input:disabled {
            background: #f0f0f0;
            cursor: not-allowed;
        }
        
        .game-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 15px 40px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-start {
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: white;
        }
        
        .btn-start:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,212,255,0.4);
        }
        
        .btn-restart {
            background: linear-gradient(135deg, #ffd700, #ffb700);
            color: #333;
        }
        
        .btn-restart:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(255,215,0,0.4);
        }
        
        .btn-back {
            background: linear-gradient(135deg, #4a5568, #2d3748);
            color: white;
            border: 2px solid #4a5568;
        }
        
        .btn-back:hover {
            background: linear-gradient(135deg, #5a6578, #3d4758);
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(74,85,104,0.4);
        }
        
        .countdown-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .countdown-number {
            font-size: 150px;
            font-weight: bold;
            color: #00d4ff;
            text-shadow: 0 0 50px rgba(0,212,255,0.5);
            animation: pulse 1s ease-in-out;
        }
        
        @keyframes pulse {
            0% { transform: scale(0.5); opacity: 0; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .results-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .results-modal {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            border: 2px solid rgba(255,255,255,0.2);
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .results-title {
            font-size: 32px;
            margin-bottom: 30px;
        }
        
        .results-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .result-stat {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 12px;
        }
        
        .result-stat .value {
            font-size: 36px;
            font-weight: bold;
            color: #00d4ff;
        }
        
        .result-stat .label {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .result-message {
            font-size: 18px;
            margin-bottom: 25px;
            padding: 15px;
            background: rgba(0,255,136,0.2);
            border-radius: 10px;
        }
        
        .result-message.fail {
            background: rgba(255,107,107,0.2);
        }
        
        .results-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .progress-bar-container {
            margin-top: 15px;
            background: #e0e0e0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #00d4ff, #00ff88);
            width: 0%;
            transition: width 0.3s;
        }
        
        .best-score-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            color: #333;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .difficulty-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .difficulty-badge.easy {
            background: #28a745;
        }
        
        .difficulty-badge.medium {
            background: #ffc107;
            color: #333;
        }
        
        .difficulty-badge.hard {
            background: #dc3545;
        }
        
        .keyboard-hint {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .keyboard-hint kbd {
            background: #e0e0e0;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: monospace;
        }
        
        /* ============================================
           RESPONSIVE DESIGN
           ============================================ */
        
        /* Mobile Phones (up to 575px) */
        @media (max-width: 575px) {
            body {
                padding: 10px;
            }
            
            .game-container {
                padding: 0;
            }
            
            .game-header {
                padding: 15px;
                border-radius: 12px;
            }
            
            .game-title {
                font-size: 18px;
            }
            
            .game-meta {
                flex-wrap: wrap;
                gap: 8px;
                justify-content: center;
            }
            
            .meta-item {
                font-size: 12px;
            }
            
            .difficulty-badge {
                font-size: 11px;
                padding: 3px 10px;
            }
            
            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                padding: 15px;
            }
            
            .stat-card {
                padding: 12px;
            }
            
            .stat-value {
                font-size: 22px;
            }
            
            .stat-label {
                font-size: 10px;
            }
            
            .best-score-badge {
                font-size: 9px;
            }
            
            /* Text Display & Input */
            .typing-area {
                padding: 15px;
                border-radius: 12px;
            }
            
            .text-display {
                font-size: 16px;
                line-height: 1.5;
                padding: 12px;
                min-height: 120px;
            }
            
            .typing-input {
                font-size: 16px;
                padding: 12px;
                min-height: 80px;
            }
            
            .progress-info {
                flex-direction: column;
                gap: 5px;
                font-size: 12px;
            }
            
            /* Game Controls */
            .game-controls {
                flex-direction: column;
                padding: 15px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
                padding: 14px;
                font-size: 14px;
            }
            
            /* Results Modal */
            .results-overlay .results-card {
                width: 95%;
                padding: 25px 15px;
            }
            
            .results-overlay h2 {
                font-size: 24px;
            }
            
            .results-overlay .results-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .results-overlay .result-item {
                padding: 15px;
            }
            
            .results-overlay .result-value {
                font-size: 24px;
            }
            
            .results-overlay .result-label {
                font-size: 11px;
            }
            
            .results-overlay .results-actions {
                flex-direction: column;
            }
            
            .results-overlay .btn {
                width: 100%;
            }
            
            .keyboard-hint {
                display: none;
            }
        }
        
        /* Tablets Portrait (576px to 767px) */
        @media (min-width: 576px) and (max-width: 767px) {
            body {
                padding: 15px;
            }
            
            .stats-bar {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .text-display {
                font-size: 18px;
            }
            
            .typing-input {
                font-size: 16px;
            }
            
            .game-controls {
                flex-wrap: wrap;
            }
            
            .btn {
                flex: 1 1 45%;
            }
        }
        
        /* Tablets Landscape (768px to 991px) */
        @media (min-width: 768px) and (max-width: 991px) {
            .text-display {
                font-size: 20px;
            }
        }
        
        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .btn,
            .typing-input {
                min-height: 44px;
            }
            
            /* Ensure input is easily tappable */
            .typing-input {
                font-size: 16px; /* Prevents iOS zoom */
            }
            
            .keyboard-hint {
                display: none;
            }
        }
        
        /* Landscape Mode on Mobile */
        @media (orientation: landscape) and (max-height: 500px) {
            body {
                padding: 8px;
            }
            
            .game-header {
                padding: 10px;
            }
            
            .game-title {
                font-size: 16px;
            }
            
            .game-meta {
                display: none;
            }
            
            .stats-bar {
                padding: 10px;
                gap: 8px;
            }
            
            .stat-card {
                padding: 8px;
            }
            
            .stat-value {
                font-size: 18px;
            }
            
            .typing-area {
                padding: 10px;
            }
            
            .text-display {
                font-size: 14px;
                min-height: 80px;
                padding: 8px;
            }
            
            .typing-input {
                min-height: 60px;
                padding: 8px;
            }
            
            .game-controls {
                padding: 10px;
            }
            
            .btn {
                padding: 10px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="game-container">
        <!-- Game Header -->
        <div class="game-header">
            <h1 class="game-title"><?php echo htmlspecialchars($game['title']); ?></h1>
            <div class="game-meta">
                <span class="meta-item">📚 <?php echo htmlspecialchars($game['subject_name']); ?></span>
                <span class="meta-item">👨‍🏫 <?php echo htmlspecialchars($game['teacher_name']); ?></span>
                <span class="meta-item">⏱️ <?php echo $game['time_limit']; ?> seconds</span>
                <span class="difficulty-badge <?php echo $game['difficulty']; ?>">
                    <?php echo ucfirst($game['difficulty']); ?>
                </span>
                <?php if ($game['min_wpm'] > 0): ?>
                    <span class="meta-item">🎯 Min WPM: <?php echo $game['min_wpm']; ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-value time" id="timerDisplay"><?php echo $game['time_limit']; ?></div>
                <div class="stat-label">Time Left</div>
            </div>
            <div class="stat-card">
                <div class="stat-value wpm" id="wpmDisplay">0</div>
                <div class="stat-label">WPM</div>
            </div>
            <div class="stat-card">
                <div class="stat-value accuracy" id="accuracyDisplay">100%</div>
                <div class="stat-label">Accuracy</div>
            </div>
            <div class="stat-card" style="position: relative;">
                <div class="stat-value" id="scoreDisplay">0</div>
                <div class="stat-label">Score</div>
                <?php if ($best_scores['best_score']): ?>
                    <div class="best-score-badge">Best: <?php echo number_format($best_scores['best_score']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Typing Area -->
        <div class="typing-area">
            <div class="text-display" id="textDisplay">
                <?php 
                $text = isset($typing_text['text_content']) && trim($typing_text['text_content']) !== '' ? $typing_text['text_content'] : '';
                $chars = str_split($text);
                foreach ($chars as $index => $char) {
                    $class = $index === 0 ? 'current' : 'remaining';
                    $charDisplay = htmlspecialchars($char);
                    if ($char === ' ') $charDisplay = '&nbsp;';
                    echo "<span class='char $class' data-index='$index'>$charDisplay</span>";
                }
                ?>
            </div>
            
            <div class="input-area">
                <input type="text" 
                       id="typingInput" 
                       class="typing-input" 
                       placeholder="Click 'Start Game' to begin typing..."
                       autocomplete="off"
                       autocorrect="off"
                       autocapitalize="off"
                       spellcheck="false"
                       disabled>
                
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
            </div>
            
            <div class="keyboard-hint">
                Press <kbd>Esc</kbd> to go back
            </div>
            
            <div class="game-controls">
                <button class="btn btn-start" id="startBtn" onclick="startGame()">
                    ▶️ Start Game
                </button>
                <a href="available-games.php" class="btn btn-back">
                    ← Back to Available Games
                </a>
            </div>
        </div>
    </div>
    
    <!-- Countdown Overlay -->
    <div class="countdown-overlay" id="countdownOverlay">
        <div class="countdown-number" id="countdownNumber">3</div>
    </div>
    
    <!-- Results Overlay -->
    <div class="results-overlay" id="resultsOverlay">
        <div class="results-modal">
            <h2 class="results-title" id="resultsTitle">🎉 Game Complete!</h2>
            
            <div class="results-stats">
                <div class="result-stat">
                    <div class="value" id="resultWpm">0</div>
                    <div class="label">Words Per Minute</div>
                </div>
                <div class="result-stat">
                    <div class="value" id="resultAccuracy">0%</div>
                    <div class="label">Accuracy</div>
                </div>
                <div class="result-stat">
                    <div class="value" id="resultScore">0</div>
                    <div class="label">Total Score</div>
                </div>
                <div class="result-stat">
                    <div class="value" id="resultTime">0s</div>
                    <div class="label">Time Taken</div>
                </div>
            </div>
            
            <div class="result-message" id="resultMessage">
                Great job! Keep practicing to improve your speed!
            </div>
            
            <div class="results-actions">
                <a href="typing-game-results.php?typing_game_id=<?php echo $typing_game_id; ?>" class="btn btn-start">
                    📊 View Leaderboard
                </a>
                <a href="my-scores.php" class="btn btn-back">
                    📊 My Scores
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Game configuration
        const config = {
            typingGameId: <?php echo $typing_game_id; ?>,
            textId: <?php echo $typing_text['text_id']; ?>,
            timeLimit: <?php echo $game['time_limit']; ?>,
            minWpm: <?php echo $game['min_wpm']; ?>,
            originalText: <?php echo json_encode($typing_text['text_content']); ?>
        };
        
        // Game state
        let gameState = {
            isRunning: false,
            startTime: null,
            timeRemaining: config.timeLimit,
            currentIndex: 0,
            correctChars: 0,
            incorrectChars: 0,
            totalTyped: 0,
            timerInterval: null
        };
        
        // DOM elements
        const elements = {
            textDisplay: document.getElementById('textDisplay'),
            typingInput: document.getElementById('typingInput'),
            timerDisplay: document.getElementById('timerDisplay'),
            wpmDisplay: document.getElementById('wpmDisplay'),
            accuracyDisplay: document.getElementById('accuracyDisplay'),
            scoreDisplay: document.getElementById('scoreDisplay'),
            progressBar: document.getElementById('progressBar'),
            startBtn: document.getElementById('startBtn'),
            countdownOverlay: document.getElementById('countdownOverlay'),
            countdownNumber: document.getElementById('countdownNumber'),
            resultsOverlay: document.getElementById('resultsOverlay')
        };
        
        // Start game with countdown
        function startGame() {
            elements.startBtn.style.display = 'none';
            elements.countdownOverlay.style.display = 'flex';
            
            let count = 3;
            elements.countdownNumber.textContent = count;
            
            const countdownInterval = setInterval(() => {
                count--;
                if (count > 0) {
                    elements.countdownNumber.textContent = count;
                    elements.countdownNumber.style.animation = 'none';
                    setTimeout(() => {
                        elements.countdownNumber.style.animation = 'pulse 1s ease-in-out';
                    }, 10);
                } else if (count === 0) {
                    elements.countdownNumber.textContent = 'GO!';
                    elements.countdownNumber.style.color = '#00ff88';
                } else {
                    clearInterval(countdownInterval);
                    elements.countdownOverlay.style.display = 'none';
                    elements.countdownNumber.style.color = '#00d4ff';
                    beginTyping();
                }
            }, 1000);
        }
        
        // Begin actual typing
        function beginTyping() {
            gameState.isRunning = true;
            gameState.startTime = Date.now();
            gameState.timeRemaining = config.timeLimit;
            
            elements.typingInput.disabled = false;
            elements.typingInput.placeholder = 'Start typing...';
            elements.typingInput.focus();
            
            // Start timer
            gameState.timerInterval = setInterval(updateTimer, 100);
        }
        
        // Update timer
        function updateTimer() {
            if (!gameState.isRunning) return;
            
            const elapsed = (Date.now() - gameState.startTime) / 1000;
            gameState.timeRemaining = Math.max(0, config.timeLimit - elapsed);
            
            elements.timerDisplay.textContent = Math.ceil(gameState.timeRemaining);
            
            // Change color when time is low
            if (gameState.timeRemaining <= 10) {
                elements.timerDisplay.style.animation = 'blink 0.5s infinite';
            }
            
            // Update WPM in real-time
            updateStats();
            
            // Time's up
            if (gameState.timeRemaining <= 0) {
                endGame(false);
            }
        }
        
        // Handle typing input
        elements.typingInput.addEventListener('input', function(e) {
            if (!gameState.isRunning) return;
            
            const inputValue = this.value;
            const inputLength = inputValue.length;
            const expectedText = config.originalText.substring(0, inputLength);
            
            // Reset all characters
            const chars = elements.textDisplay.querySelectorAll('.char');
            chars.forEach((char, index) => {
                char.classList.remove('correct', 'incorrect', 'current');
                if (index >= inputLength) {
                    char.classList.add('remaining');
                }
            });
            
            // Check each character
            let correctCount = 0;
            let incorrectCount = 0;
            
            for (let i = 0; i < inputLength; i++) {
                const char = chars[i];
                if (inputValue[i] === config.originalText[i]) {
                    char.classList.add('correct');
                    char.classList.remove('remaining');
                    correctCount++;
                } else {
                    char.classList.add('incorrect');
                    char.classList.remove('remaining');
                    incorrectCount++;
                }
            }
            
            // Mark current character
            if (inputLength < chars.length) {
                chars[inputLength].classList.add('current');
                chars[inputLength].classList.remove('remaining');
            }
            
            // Update state
            gameState.currentIndex = inputLength;
            gameState.correctChars = correctCount;
            gameState.incorrectChars = incorrectCount;
            gameState.totalTyped = inputLength;
            
            // Update progress bar
            const progress = (inputLength / config.originalText.length) * 100;
            elements.progressBar.style.width = progress + '%';
            
            // Shake on error
            if (incorrectCount > 0 && inputValue[inputLength - 1] !== config.originalText[inputLength - 1]) {
                elements.typingInput.classList.add('error');
                setTimeout(() => elements.typingInput.classList.remove('error'), 300);
            }
            
            // Check if completed
            if (inputLength >= config.originalText.length && correctCount === config.originalText.length) {
                endGame(true);
            }
        });
        
        // Update stats display
        function updateStats() {
            const elapsed = (Date.now() - gameState.startTime) / 1000;
            const minutes = elapsed / 60;
            
            // Calculate WPM (assuming average word length of 5 characters)
            const words = gameState.correctChars / 5;
            const wpm = minutes > 0 ? Math.round(words / minutes) : 0;
            
            // Calculate accuracy
            const accuracy = gameState.totalTyped > 0 
                ? Math.round((gameState.correctChars / gameState.totalTyped) * 100) 
                : 100;
            
            // Calculate score
            const score = calculateScore(wpm, accuracy, elapsed);
            
            elements.wpmDisplay.textContent = wpm;
            elements.accuracyDisplay.textContent = accuracy + '%';
            elements.scoreDisplay.textContent = score;
        }
        
        // Calculate score
        function calculateScore(wpm, accuracy, timeInSeconds) {
            // Base score from WPM
            let score = wpm * 10;
            
            // Accuracy bonus (up to 50% bonus for 100% accuracy)
            score *= (accuracy / 100) * 1.5;
            
            // Speed bonus for completing before time limit
            if (gameState.currentIndex >= config.originalText.length) {
                const timeBonus = Math.max(0, (config.timeLimit - timeInSeconds) * 5);
                score += timeBonus;
            }
            
            return Math.round(score);
        }
        
        // End game
        function endGame(completed) {
            gameState.isRunning = false;
            clearInterval(gameState.timerInterval);
            
            elements.typingInput.disabled = true;
            
            const elapsed = (Date.now() - gameState.startTime) / 1000;
            const minutes = elapsed / 60;
            const words = gameState.correctChars / 5;
            const wpm = minutes > 0 ? Math.round(words / minutes) : 0;
            const accuracy = gameState.totalTyped > 0 
                ? Math.round((gameState.correctChars / gameState.totalTyped) * 100) 
                : 100;
            const score = calculateScore(wpm, accuracy, elapsed);
            
            // Show results
            showResults(wpm, accuracy, score, elapsed, completed);
            
            // Save results to database
            saveResults(wpm, accuracy, score, elapsed, completed);
        }
        
        // Show results modal
        function showResults(wpm, accuracy, score, time, completed) {
            const passed = config.minWpm === 0 || wpm >= config.minWpm;
            
            document.getElementById('resultWpm').textContent = wpm;
            document.getElementById('resultAccuracy').textContent = accuracy + '%';
            document.getElementById('resultScore').textContent = score;
            document.getElementById('resultTime').textContent = Math.round(time) + 's';
            
            const titleEl = document.getElementById('resultsTitle');
            const messageEl = document.getElementById('resultMessage');
            
            if (completed && passed) {
                titleEl.textContent = '🎉 Excellent Work!';
                messageEl.textContent = `You completed the text with ${wpm} WPM! Keep up the great work!`;
                messageEl.classList.remove('fail');
            } else if (completed && !passed) {
                titleEl.textContent = '⚡ Almost There!';
                messageEl.textContent = `You completed it but need ${config.minWpm} WPM to pass. Your speed: ${wpm} WPM. Try again!`;
                messageEl.classList.add('fail');
            } else {
                titleEl.textContent = '⏱️ Time\'s Up!';
                messageEl.textContent = `You typed ${gameState.correctChars} characters correctly. Practice to improve your speed!`;
                messageEl.classList.add('fail');
            }
            
            elements.resultsOverlay.style.display = 'flex';
        }
        
        // Save results to database
        function saveResults(wpm, accuracy, score, time, completed) {
            fetch('save-typing-results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    typing_game_id: config.typingGameId,
                    text_id: config.textId,
                    total_characters: config.originalText.length,
                    correct_characters: gameState.correctChars,
                    wrong_characters: gameState.incorrectChars,
                    wpm: wpm,
                    accuracy: accuracy,
                    total_score: score,
                    time_taken: Math.round(time),
                    completed: completed ? 1 : 0
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Failed to save results:', data.error);
                }
            })
            .catch(error => {
                console.error('Error saving results:', error);
            });
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !gameState.isRunning) {
                if (elements.startBtn.style.display !== 'none') {
                    startGame();
                }
            } else if (e.key === 'Escape') {
                window.location.href = 'my-scores.php';
            }
        });
        
        // Prevent paste
        elements.typingInput.addEventListener('paste', function(e) {
            e.preventDefault();
        });
    </script>
</body>
</html>
