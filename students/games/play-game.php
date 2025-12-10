<?php
session_start();
include '../../Database/database.php';

// Check if user is STUDENT (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: ../stud-login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$game_id = $_GET['game_id'] ?? 0;

// Get student's grade level
$student_query = "SELECT grade_level FROM users WHERE user_id = ?";
$student_stmt = $pdo->prepare($student_query);
$student_stmt->execute([$student_id]);
$student = $student_stmt->fetch(PDO::FETCH_ASSOC);
$student_grade = $student['grade_level'];

// Verify student has access to this game (by grade level)
$query = "SELECT ga.*, s.subject_name, CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
          (SELECT COUNT(*) FROM game_questions WHERE game_id = ga.game_id) as question_count
          FROM game_activities ga
          INNER JOIN subjects s ON ga.subject_id = s.subject_id
          INNER JOIN users u ON ga.teacher_id = u.user_id
          WHERE ga.game_id = ? AND s.grade_level = ? AND ga.status = 'active'
          AND (ga.due_date IS NULL OR ga.due_date >= NOW())";
$stmt = $pdo->prepare($query);
$stmt->execute([$game_id, $student_grade]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: available-games.php?error=game_not_found");
    exit();
}

// Check if student has already completed this game (one attempt only)
$checkAttemptQuery = "SELECT session_id FROM game_sessions 
                      WHERE game_id = ? AND student_id = ? AND completed_at IS NOT NULL
                      LIMIT 1";
$checkStmt = $pdo->prepare($checkAttemptQuery);
$checkStmt->execute([$game_id, $student_id]);
$existingAttempt = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existingAttempt) {
    header("Location: game-results.php?session_id=" . $existingAttempt['session_id'] . "&already_played=1");
    exit();
}

if (!empty($game['due_date'])) {
    $due_date_obj = new DateTime($game['due_date']);
    if ($due_date_obj <= new DateTime()) {
        header("Location: available-games.php?error=game_expired");
        exit();
    }
}

// Create new game session
$session_query = "INSERT INTO game_sessions (game_id, student_id, total_questions) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($session_query);
$stmt->execute([$game_id, $student_id, $game['question_count']]);
$session_id = $pdo->lastInsertId();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($game['title']); ?> - Quiz Game</title>
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
            max-width: 900px;
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
            font-size: 24px;
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
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
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
        
        /* Loading Screen */
        .loading-screen {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 60px 40px;
            border-radius: 24px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .loading-screen h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .loading-screen p {
            font-size: 18px;
            opacity: 0.8;
            margin-bottom: 10px;
        }
        
        .loading-screen .game-details {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .loading-screen .detail {
            background: rgba(0,0,0,0.3);
            padding: 15px 25px;
            border-radius: 12px;
        }
        
        .loading-screen .detail-value {
            font-size: 24px;
            font-weight: 700;
            color: #00d4ff;
        }
        
        .loading-screen .detail-label {
            font-size: 12px;
            text-transform: uppercase;
            opacity: 0.7;
        }
        
        .btn {
            padding: 18px 50px;
            border-radius: 12px;
            font-size: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-start {
            background: linear-gradient(135deg, #00ff88, #00cc6a);
            color: #1a1a2e;
            box-shadow: 0 4px 20px rgba(0,255,136,0.4);
        }
        
        .btn-start:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,255,136,0.5);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: white;
            box-shadow: 0 4px 15px rgba(0,212,255,0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,212,255,0.5);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
        }
        
        /* Question Card */
        .question-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.2);
            display: none;
            animation: slideIn 0.5s ease;
        }
        
        .question-card.active {
            display: block;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .question-number {
            font-size: 16px;
            opacity: 0.8;
            background: rgba(0,0,0,0.3);
            padding: 8px 16px;
            border-radius: 20px;
        }
        
        .question-timer {
            font-size: 32px;
            font-weight: 700;
            color: #ff6b6b;
            min-width: 70px;
            text-align: center;
            background: rgba(0,0,0,0.3);
            padding: 10px 20px;
            border-radius: 12px;
        }
        
        .question-timer.warning {
            animation: pulse 0.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .question-text {
            font-size: 26px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 40px;
            line-height: 1.5;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .option-btn {
            padding: 25px;
            border: 3px solid;
            border-radius: 16px;
            font-size: 18px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(255,255,255,0.05);
            text-align: left;
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .option-btn .option-letter {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .option-btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .option-btn:disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .option-btn.option-a {
            border-color: #ff6b6b;
        }
        .option-btn.option-a .option-letter {
            background: #ff6b6b;
            color: white;
        }
        
        .option-btn.option-b {
            border-color: #00d4ff;
        }
        .option-btn.option-b .option-letter {
            background: #00d4ff;
            color: #1a1a2e;
        }
        
        .option-btn.option-c {
            border-color: #ffc107;
        }
        .option-btn.option-c .option-letter {
            background: #ffc107;
            color: #1a1a2e;
        }
        
        .option-btn.option-d {
            border-color: #00ff88;
        }
        .option-btn.option-d .option-letter {
            background: #00ff88;
            color: #1a1a2e;
        }
        
        .option-btn.correct {
            background: rgba(0,255,136,0.3) !important;
            border-color: #00ff88 !important;
        }
        
        .option-btn.incorrect {
            background: rgba(255,107,107,0.3) !important;
            border-color: #ff6b6b !important;
        }
        
        /* Result Overlay */
        .result-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            backdrop-filter: blur(10px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .result-overlay.active {
            display: flex;
        }
        
        .result-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            padding: 50px;
            border-radius: 24px;
            text-align: center;
            max-width: 450px;
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
        
        .result-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .result-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .result-points {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 30px;
        }
        
        .result-points.correct {
            color: #00ff88;
        }
        
        .result-points.incorrect {
            color: #ff6b6b;
        }
        
        .btn-next {
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,212,255,0.4);
            transition: all 0.3s;
        }
        
        .btn-next:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,212,255,0.5);
        }
        
        /* Final Results */
        .final-results {
            display: none;
        }
        
        .final-results.active {
            display: block;
        }
        
        .final-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 50px;
            border-radius: 24px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .final-title {
            font-size: 42px;
            margin-bottom: 30px;
        }
        
        .final-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .final-stat {
            background: rgba(0,0,0,0.3);
            padding: 25px;
            border-radius: 16px;
        }
        
        .final-stat .value {
            font-size: 36px;
            font-weight: 700;
            color: #00d4ff;
        }
        
        .final-stat .value.score {
            color: #00ff88;
        }
        
        .final-stat .value.percentage {
            color: #ffc107;
        }
        
        .final-stat .label {
            font-size: 14px;
            opacity: 0.7;
            margin-top: 8px;
            text-transform: uppercase;
        }
        
        .final-message {
            font-size: 18px;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(0,255,136,0.1);
            border-radius: 12px;
            border: 1px solid rgba(0,255,136,0.3);
        }
        
        .final-message.failed {
            background: rgba(255,107,107,0.1);
            border-color: rgba(255,107,107,0.3);
        }
        
        .final-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Keyboard hints */
        .keyboard-hint {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            opacity: 0.6;
        }
        
        .keyboard-hint kbd {
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 6px;
            margin: 0 3px;
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
                font-size: 22px;
            }
            
            .stat-label {
                font-size: 10px;
            }
            
            .progress-text {
                font-size: 12px;
            }
            
            /* Loading Screen */
            .loading-screen {
                padding: 30px 15px;
            }
            
            .loading-screen h1 {
                font-size: 28px;
            }
            
            .loading-screen p {
                font-size: 14px;
            }
            
            .loading-screen .game-details {
                flex-direction: column;
                gap: 10px;
            }
            
            .detail {
                padding: 12px;
            }
            
            .btn-start {
                padding: 14px 30px;
                font-size: 16px;
            }
            
            /* Question Card */
            .question-card {
                padding: 20px;
            }
            
            .question-number {
                font-size: 14px;
                padding: 6px 14px;
            }
            
            .timer-circle {
                width: 50px;
                height: 50px;
                font-size: 16px;
            }
            
            .question-text {
                font-size: 18px;
                line-height: 1.4;
            }
            
            .options-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .option-btn {
                padding: 12px;
                font-size: 14px;
                min-height: 50px;
            }
            
            /* Result Cards */
            .result-card {
                padding: 30px 20px;
            }
            
            .result-icon {
                font-size: 50px;
            }
            
            .result-title {
                font-size: 22px;
            }
            
            .result-points {
                font-size: 32px;
            }
            
            .btn-next {
                padding: 12px 25px;
                font-size: 16px;
                width: 100%;
            }
            
            /* Final Results */
            .final-card {
                padding: 30px 15px;
            }
            
            .final-title {
                font-size: 28px;
            }
            
            .final-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .final-stat {
                padding: 15px;
            }
            
            .final-stat .value {
                font-size: 24px;
            }
            
            .final-stat .label {
                font-size: 11px;
            }
            
            .final-message {
                font-size: 14px;
                padding: 15px;
            }
            
            .final-actions {
                flex-direction: column;
            }
            
            .final-actions .btn {
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
            
            .options-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .loading-screen .game-details {
                flex-direction: row;
            }
            
            .final-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Tablets Landscape (768px to 991px) */
        @media (min-width: 768px) and (max-width: 991px) {
            .options-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .final-stats {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .option-btn,
            .btn,
            .btn-start,
            .btn-next,
            .back-btn {
                min-height: 44px;
            }
            
            .option-btn:hover:not(:disabled) {
                transform: none;
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
                font-size: 18px;
            }
            
            .progress-container {
                margin-top: 8px;
            }
            
            .question-card {
                padding: 15px;
            }
            
            .question-text {
                font-size: 18px;
                margin-bottom: 15px;
            }
            
            .options-grid {
                gap: 8px;
            }
            
            .option-btn {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="game-container">
        <div class="game-header">
            <div class="header-top">
                <div class="game-title">
                    <i class="fas fa-gamepad"></i>
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
                    <div class="stat-value" id="questionNum">0</div>
                    <div class="stat-label">Question</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $game['question_count']; ?></div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" id="correctCount">0</div>
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
                <div class="progress-text" id="progressText">0 / <?php echo $game['question_count']; ?> Completed</div>
            </div>
        </div>
        
        <!-- Loading Screen -->
        <div class="loading-screen" id="loadingScreen">
            <h1>🎮 Get Ready!</h1>
            <p><?php echo htmlspecialchars($game['title']); ?></p>
            
            <div class="game-details">
                <div class="detail">
                    <div class="detail-value"><?php echo $game['question_count']; ?></div>
                    <div class="detail-label">Questions</div>
                </div>
                <div class="detail">
                    <div class="detail-value"><?php echo $game['time_limit']; ?>s</div>
                    <div class="detail-label">Per Question</div>
                </div>
            </div>
            
            <button class="btn btn-start" onclick="gamePlayer.startGame()">
                <i class="fas fa-play"></i> Start Game
            </button>
        </div>
        
        <!-- Questions Container -->
        <div id="questionsContainer"></div>
        
        <!-- Final Results -->
        <div class="final-results" id="finalResults">
            <div class="final-card">
                <h2 class="final-title" id="finalTitle">🎉 Quiz Complete!</h2>
                
                <div class="final-stats">
                    <div class="final-stat">
                        <div class="value" id="finalCorrect">0</div>
                        <div class="label">Correct</div>
                    </div>
                    <div class="final-stat">
                        <div class="value"><?php echo $game['question_count']; ?></div>
                        <div class="label">Questions</div>
                    </div>
                    <div class="final-stat">
                        <div class="value percentage" id="finalPercentage">0%</div>
                        <div class="label">Accuracy</div>
                    </div>
                    <div class="final-stat">
                        <div class="value score" id="finalScore">0</div>
                        <div class="label">Total Score</div>
                    </div>
                </div>
                
                <div class="final-message" id="finalMessage">
                    Great job completing the quiz!
                </div>
                
                <div class="final-actions">
                    <a href="my-scores.php" class="btn btn-primary">
                        <i class="fas fa-chart-bar"></i> View My Scores
                    </a>
                    <a href="../student-home.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div class="keyboard-hint" id="keyboardHint">
            Press <kbd>1</kbd><kbd>2</kbd><kbd>3</kbd><kbd>4</kbd> to select answers • <kbd>Esc</kbd> to go back
        </div>
    </div>
    
    <!-- Result Overlay -->
    <div class="result-overlay" id="resultOverlay">
        <div class="result-card">
            <div class="result-icon" id="resultIcon"></div>
            <div class="result-title" id="resultTitle"></div>
            <div class="result-points" id="resultPoints"></div>
            <button class="btn-next" onclick="gamePlayer.nextQuestion()">
                Next Question <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
    
    <script>
        const gamePlayer = {
            gameId: <?php echo $game_id; ?>,
            sessionId: <?php echo $session_id; ?>,
            timeLimit: <?php echo $game['time_limit']; ?>,
            totalQuestions: <?php echo $game['question_count']; ?>,
            currentQuestionIndex: 0,
            currentScore: 0,
            correctAnswers: 0,
            questions: [],
            timer: null,
            timeRemaining: 0,
            startTime: null,
            
            async startGame() {
                document.getElementById('loadingScreen').style.display = 'none';
                await this.loadQuestions();
                this.loadQuestion(0);
            },
            
            async loadQuestions() {
                try {
                    const response = await fetch(`../../api/games/get-game.php?game_id=${this.gameId}`);
                    const data = await response.json();
                    this.questions = data.questions;
                } catch (error) {
                    console.error('Error loading questions:', error);
                    alert('Failed to load questions. Please refresh the page.');
                }
            },
            
            loadQuestion(index) {
                if (index >= this.questions.length) {
                    this.endGame();
                    return;
                }
                
                this.currentQuestionIndex = index;
                const question = this.questions[index];
                const optionLetters = ['A', 'B', 'C', 'D'];
                const optionClasses = ['option-a', 'option-b', 'option-c', 'option-d'];
                
                // Update progress
                document.getElementById('questionNum').textContent = index + 1;
                document.getElementById('progressText').textContent = `${index} / ${this.totalQuestions} Completed`;
                document.getElementById('progressBar').style.width = `${(index / this.totalQuestions) * 100}%`;
                
                const container = document.getElementById('questionsContainer');
                container.innerHTML = `
                    <div class="question-card active">
                        <div class="question-header">
                            <div class="question-number">Question ${index + 1} of ${this.totalQuestions}</div>
                            <div class="question-timer" id="timer">${this.timeLimit}</div>
                        </div>
                        <div class="question-text">${question.question_text}</div>
                        <div class="options-grid" id="optionsGrid">
                            ${question.options.map((opt, i) => `
                                <button class="option-btn ${optionClasses[i]}" 
                                        data-option-id="${opt.option_id}"
                                        data-is-correct="${opt.is_correct}"
                                        onclick="gamePlayer.selectAnswer(${opt.option_id}, ${opt.is_correct}, ${question.question_id})">
                                    <span class="option-letter">${optionLetters[i]}</span>
                                    <span class="option-text">${opt.option_text}</span>
                                </button>
                            `).join('')}
                        </div>
                    </div>
                `;
                
                this.startTimer();
            },
            
            startTimer() {
                this.timeRemaining = this.timeLimit;
                this.startTime = Date.now();
                const timerEl = document.getElementById('timer');
                
                this.timer = setInterval(() => {
                    this.timeRemaining--;
                    timerEl.textContent = this.timeRemaining;
                    
                    if (this.timeRemaining <= 5) {
                        timerEl.classList.add('warning');
                    }
                    
                    if (this.timeRemaining <= 0) {
                        clearInterval(this.timer);
                        this.timeUp();
                    }
                }, 1000);
            },
            
            async selectAnswer(optionId, isCorrect, questionId) {
                clearInterval(this.timer);
                const timeTaken = Math.floor((Date.now() - this.startTime) / 1000);
                
                // Disable all buttons and show correct/incorrect
                const buttons = document.querySelectorAll('.option-btn');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    if (btn.dataset.isCorrect === '1') {
                        btn.classList.add('correct');
                    }
                    if (btn.dataset.optionId == optionId && !isCorrect) {
                        btn.classList.add('incorrect');
                    }
                });
                
                try {
                    const formData = new FormData();
                    formData.append('game_id', this.gameId);
                    formData.append('session_id', this.sessionId);
                    formData.append('question_id', questionId);
                    formData.append('option_id', optionId);
                    formData.append('time_taken', timeTaken);
                    
                    const response = await fetch('../../api/games/submit-answer.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error('Server error');
                    }
                    
                    const result = await response.json();
                    
                    if (!result.success) {
                        console.error('Answer submission failed:', result.error);
                    }
                    
                    // Show result
                    setTimeout(() => {
                        this.showResult(result.is_correct, result.points_earned || 0);
                    }, 800);
                    
                    if (result.is_correct) {
                        this.correctAnswers++;
                        this.currentScore += result.points_earned || 0;
                        document.getElementById('correctCount').textContent = this.correctAnswers;
                        document.getElementById('scoreDisplay').textContent = this.currentScore;
                    }
                } catch (error) {
                    console.error('Failed to submit answer:', error);
                    setTimeout(() => {
                        this.showResult(isCorrect, isCorrect ? 100 : 0);
                        if (isCorrect) {
                            this.correctAnswers++;
                            this.currentScore += 100;
                            document.getElementById('correctCount').textContent = this.correctAnswers;
                            document.getElementById('scoreDisplay').textContent = this.currentScore;
                        }
                    }, 800);
                }
            },
            
            timeUp() {
                // Disable all buttons
                document.querySelectorAll('.option-btn').forEach(btn => {
                    btn.disabled = true;
                    if (btn.dataset.isCorrect === '1') {
                        btn.classList.add('correct');
                    }
                });
                
                setTimeout(() => {
                    this.showResult(false, 0);
                }, 800);
            },
            
            showResult(isCorrect, points) {
                const overlay = document.getElementById('resultOverlay');
                const icon = document.getElementById('resultIcon');
                const title = document.getElementById('resultTitle');
                const pointsEl = document.getElementById('resultPoints');
                
                if (isCorrect) {
                    icon.textContent = '✓';
                    icon.style.color = '#00ff88';
                    title.textContent = 'Correct!';
                    title.style.color = '#00ff88';
                    pointsEl.textContent = `+${points} points`;
                    pointsEl.className = 'result-points correct';
                } else {
                    icon.textContent = '✗';
                    icon.style.color = '#ff6b6b';
                    title.textContent = 'Incorrect';
                    title.style.color = '#ff6b6b';
                    pointsEl.textContent = '0 points';
                    pointsEl.className = 'result-points incorrect';
                }
                
                overlay.classList.add('active');
            },
            
            nextQuestion() {
                document.getElementById('resultOverlay').classList.remove('active');
                this.loadQuestion(this.currentQuestionIndex + 1);
            },
            
            async endGame() {
                // Update progress to 100%
                document.getElementById('progressText').textContent = `${this.totalQuestions} / ${this.totalQuestions} Completed`;
                document.getElementById('progressBar').style.width = '100%';
                
                // Complete session
                try {
                    const formData = new FormData();
                    formData.append('session_id', this.sessionId);
                    formData.append('total_score', this.currentScore);
                    
                    await fetch('../../api/games/complete-session.php', {
                        method: 'POST',
                        body: formData
                    });
                } catch (error) {
                    console.error('Failed to complete session:', error);
                }
                
                // Calculate percentage
                const percentage = Math.round((this.correctAnswers / this.totalQuestions) * 100);
                
                // Update final results
                document.getElementById('finalCorrect').textContent = this.correctAnswers;
                document.getElementById('finalPercentage').textContent = percentage + '%';
                document.getElementById('finalScore').textContent = this.currentScore;
                
                // Set message based on performance
                const messageEl = document.getElementById('finalMessage');
                const titleEl = document.getElementById('finalTitle');
                
                if (percentage >= 80) {
                    titleEl.textContent = '🎉 Excellent!';
                    messageEl.textContent = 'Outstanding performance! You really know your stuff!';
                    messageEl.classList.remove('failed');
                } else if (percentage >= 50) {
                    titleEl.textContent = '👍 Good Job!';
                    messageEl.textContent = 'Nice work! Keep studying to improve even more!';
                    messageEl.classList.remove('failed');
                } else {
                    titleEl.textContent = '💪 Keep Learning!';
                    messageEl.textContent = 'Don\'t give up! Review the material and try other quizzes!';
                    messageEl.classList.add('failed');
                }
                
                // Hide questions, show results
                document.getElementById('questionsContainer').style.display = 'none';
                document.getElementById('keyboardHint').style.display = 'none';
                document.getElementById('finalResults').classList.add('active');
            }
        };
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = 'my-scores.php';
            }
            
            // Number keys for answers
            if (gamePlayer.timer && !document.getElementById('resultOverlay').classList.contains('active')) {
                const keyNum = parseInt(e.key);
                if (keyNum >= 1 && keyNum <= 4) {
                    const buttons = document.querySelectorAll('.option-btn');
                    if (buttons[keyNum - 1] && !buttons[keyNum - 1].disabled) {
                        buttons[keyNum - 1].click();
                    }
                }
            }
            
            // Enter/Space for next question
            if ((e.key === 'Enter' || e.key === ' ') && document.getElementById('resultOverlay').classList.contains('active')) {
                gamePlayer.nextQuestion();
            }
        });
    </script>
</body>
</html>
