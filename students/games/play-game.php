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
          WHERE ga.game_id = ? AND s.grade_level = ? AND ga.status = 'active'";
$stmt = $pdo->prepare($query);
$stmt->execute([$game_id, $student_grade]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: available-games.php");
    exit();
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play Game - <?php echo htmlspecialchars($game['title']); ?></title>
    <link rel="stylesheet" href="../../assets/css/game-interface.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .game-header {
            background: rgba(255,255,255,0.95);
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .game-title-bar h2 {
            margin: 0;
            color: #333;
            font-size: 20px;
        }
        
        .game-stats {
            display: flex;
            gap: 30px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .game-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .question-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
            display: none;
        }
        
        .question-card.active {
            display: block;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
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
            font-size: 18px;
            color: #666;
            font-weight: bold;
        }
        
        .timer {
            font-size: 32px;
            font-weight: bold;
            color: #e21b3c;
            min-width: 80px;
            text-align: center;
            padding: 10px 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .timer.warning {
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .question-text {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 40px;
            text-align: center;
            line-height: 1.4;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .option-btn {
            padding: 30px;
            border: 4px solid;
            border-radius: 15px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
            text-align: left;
            position: relative;
        }
        
        .option-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .option-btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .option-red {
            border-color: #4a90e2;
            color: #4a90e2;
            background: #e3f2fd;
        }
        
        .option-blue {
            border-color: #2196f3;
            color: #2196f3;
            background: #bbdefb;
        }
        
        .option-yellow {
            border-color: #1976d2;
            color: #1976d2;
            background: #90caf9;
        }
        
        .option-green {
            border-color: #0d47a1;
            color: #0d47a1;
            background: #64b5f6;
        }
        
        .option-btn.correct {
            background: #26890D;
            color: white;
            border-color: #26890D;
        }
        
        .option-btn.incorrect {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }
        
        .result-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .result-overlay.active {
            display: flex;
        }
        
        .result-card {
            background: white;
            padding: 50px;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
        }
        
        .result-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .result-title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .result-points {
            font-size: 48px;
            font-weight: bold;
            color: #26890D;
            margin-bottom: 30px;
        }
        
        .next-btn {
            background: #26890D;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .next-btn:hover {
            background: #1e6a0a;
        }
        
        .loading-screen {
            text-align: center;
            color: white;
            padding: 100px 20px;
        }
        
        .loading-screen h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .loading-screen p {
            font-size: 20px;
            margin-bottom: 30px;
        }
        
        .start-btn {
            background: #26890D;
            color: white;
            padding: 20px 50px;
            border: none;
            border-radius: 15px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        .start-btn:hover {
            background: #1e6a0a;
            transform: scale(1.05);
        }
        
        /* ============================================
           RESPONSIVE DESIGN FOR GAME PLAY
           ============================================ */
        
        /* Mobile Phones (up to 575px) */
        @media (max-width: 575px) {
            body {
                padding: 0;
            }
            
            .game-header {
                padding: 10px 15px;
                flex-direction: column;
                gap: 10px;
            }
            
            .game-title-bar h2 {
                font-size: 16px;
            }
            
            .game-stats {
                gap: 15px;
                font-size: 14px;
                width: 100%;
                justify-content: space-around;
            }
            
            .game-container {
                margin: 20px auto;
                padding: 10px;
            }
            
            .question-card {
                padding: 20px;
                border-radius: 15px;
            }
            
            .question-number {
                font-size: 12px;
                padding: 6px 12px;
            }
            
            .question-text {
                font-size: 18px;
                margin-bottom: 20px;
            }
            
            .timer {
                font-size: 14px;
                padding: 8px 16px;
                top: 10px;
                right: 10px;
            }
            
            .options-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .option-btn {
                padding: 20px;
                font-size: 16px;
                border-width: 3px;
            }
            
            .loading-screen h1 {
                font-size: 28px;
            }
            
            .loading-screen p {
                font-size: 16px;
            }
            
            .start-btn {
                padding: 15px 35px;
                font-size: 18px;
            }
            
            .result-title {
                font-size: 24px;
            }
            
            .result-points {
                font-size: 36px;
            }
            
            .next-btn {
                padding: 12px 30px;
                font-size: 16px;
            }
        }
        
        /* Tablets Portrait (576px to 767px) */
        @media (min-width: 576px) and (max-width: 767px) {
            .game-header {
                padding: 12px 20px;
            }
            
            .game-title-bar h2 {
                font-size: 18px;
            }
            
            .game-container {
                margin: 30px auto;
                padding: 15px;
            }
            
            .question-card {
                padding: 30px;
            }
            
            .options-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .option-btn {
                padding: 25px;
                font-size: 17px;
            }
        }
        
        /* Tablets Landscape (768px to 991px) */
        @media (min-width: 768px) and (max-width: 991px) {
            .game-container {
                max-width: 750px;
            }
            
            .question-card {
                padding: 35px;
            }
            
            .options-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 18px;
            }
        }
        
        /* Laptops (992px to 1199px) */
        @media (min-width: 992px) and (max-width: 1199px) {
            .game-container {
                max-width: 850px;
            }
        }
        
        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .option-btn {
                min-height: 60px;
            }
            
            .start-btn,
            .next-btn {
                min-height: 50px;
                min-width: 120px;
            }
            
            .option-btn:hover:not(:disabled) {
                transform: none;
            }
            
            .start-btn:hover {
                transform: none;
            }
        }
        
        /* Landscape Orientation for Mobile */
        @media (orientation: landscape) and (max-height: 600px) {
            .game-header {
                padding: 8px 15px;
            }
            
            .question-card {
                padding: 20px;
            }
            
            .options-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .loading-screen {
                padding: 50px 20px;
            }
            
            .loading-screen h1 {
                font-size: 32px;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="game-header">
        <div class="game-title-bar">
            <h2><?php echo htmlspecialchars($game['title']); ?></h2>
        </div>
        <div class="game-stats">
            <div class="stat-item">
                <span>Question:</span>
                <span id="current-question">0</span>/<span id="total-questions"><?php echo $game['question_count']; ?></span>
            </div>
            <div class="stat-item">
                <span>Score:</span>
                <span id="current-score">0</span>
            </div>
        </div>
    </div>
    
    <div class="game-container">
        <div class="loading-screen" id="loading-screen">
            <h1>🎮 Get Ready!</h1>
            <p><?php echo htmlspecialchars($game['title']); ?></p>
            <p><?php echo $game['question_count']; ?> Questions | <?php echo $game['time_limit']; ?>s per question</p>
            <button class="start-btn" onclick="gamePlayer.startGame()">Start Game</button>
        </div>
        
        <div id="questions-container"></div>
    </div>
    
    <div class="result-overlay" id="result-overlay">
        <div class="result-card">
            <div class="result-icon" id="result-icon"></div>
            <div class="result-title" id="result-title"></div>
            <div class="result-points" id="result-points"></div>
            <button class="next-btn" onclick="gamePlayer.nextQuestion()">Next Question</button>
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
            questions: [],
            timer: null,
            timeRemaining: 0,
            startTime: null,
            
            async startGame() {
                document.getElementById('loading-screen').style.display = 'none';
                await this.loadQuestions();
                this.loadQuestion(0);
            },
            
            async loadQuestions() {
                const response = await fetch(`../../api/games/get-game.php?game_id=${this.gameId}`);
                const data = await response.json();
                this.questions = data.questions;
            },
            
            loadQuestion(index) {
                if (index >= this.questions.length) {
                    this.endGame();
                    return;
                }
                
                this.currentQuestionIndex = index;
                const question = this.questions[index];
                
                document.getElementById('current-question').textContent = index + 1;
                
                const container = document.getElementById('questions-container');
                container.innerHTML = `
                    <div class="question-card active">
                        <div class="question-header">
                            <div class="question-number">Question ${index + 1} of ${this.totalQuestions}</div>
                            <div class="timer" id="timer">${this.timeLimit}</div>
                        </div>
                        <div class="question-text">${question.question_text}</div>
                        <div class="options-grid" id="options-grid">
                            ${question.options.map((opt, i) => `
                                <button class="option-btn option-${opt.color_code}" 
                                        onclick="gamePlayer.selectAnswer(${opt.option_id}, ${opt.is_correct}, ${question.question_id})">
                                    ${opt.option_text}
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
                
                // Disable all buttons
                document.querySelectorAll('.option-btn').forEach(btn => {
                    btn.disabled = true;
                });
                
                // Submit answer
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
                
                const result = await response.json();
                
                // Show result
                this.showResult(result.is_correct, result.points_earned);
                
                if (result.is_correct) {
                    this.currentScore += result.points_earned;
                    document.getElementById('current-score').textContent = this.currentScore;
                }
            },
            
            timeUp() {
                this.showResult(false, 0);
            },
            
            showResult(isCorrect, points) {
                const overlay = document.getElementById('result-overlay');
                const icon = document.getElementById('result-icon');
                const title = document.getElementById('result-title');
                const pointsEl = document.getElementById('result-points');
                
                if (isCorrect) {
                    icon.textContent = '✓';
                    icon.style.color = '#26890D';
                    title.textContent = 'Correct!';
                    title.style.color = '#26890D';
                    pointsEl.textContent = `+${points} points`;
                } else {
                    icon.textContent = '✗';
                    icon.style.color = '#dc3545';
                    title.textContent = 'Incorrect';
                    title.style.color = '#dc3545';
                    pointsEl.textContent = '0 points';
                }
                
                overlay.classList.add('active');
            },
            
            nextQuestion() {
                document.getElementById('result-overlay').classList.remove('active');
                this.loadQuestion(this.currentQuestionIndex + 1);
            },
            
            async endGame() {
                // Update session as completed
                const formData = new FormData();
                formData.append('session_id', this.sessionId);
                formData.append('total_score', this.currentScore);
                
                await fetch('../../api/games/complete-session.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Redirect to results
                window.location.href = `game-results.php?session_id=${this.sessionId}`;
            }
        };
    </script>
</body>
</html>
