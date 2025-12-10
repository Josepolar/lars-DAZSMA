<?php
session_start();
include '../Database/database.php';

// Check if user is STUDENT (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: stud-login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get matching game ID
if (!isset($_GET['matching_game_id'])) {
    header("Location: student-home.php");
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
    header("Location: student-home.php");
    exit();
}

// Get student's most recent completed session for this game
$query = "SELECT * FROM matching_sessions 
          WHERE matching_game_id = ? AND student_id = ? AND completed_at IS NOT NULL
          ORDER BY completed_at DESC
          LIMIT 1";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id, $student_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    echo "<script>alert('No completed session found for this game.'); window.location.href='student-home.php';</script>";
    exit();
}

// Get all matching pairs for this game (the correct answers)
$query = "SELECT * FROM matching_pairs WHERE matching_game_id = ? ORDER BY pair_order";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id]);
$pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get student's responses for this session with all pair data
$query = "SELECT mr.*, 
          mp_correct.left_item_text as correct_left_text,
          mp_correct.left_item_image as correct_left_image,
          mp_correct.right_item_text as correct_right_text,
          mp_correct.right_item_image as correct_right_image,
          mp_student.left_item_text as student_left_text,
          mp_student.left_item_image as student_left_image,
          mp_student.right_item_text as student_right_text,
          mp_student.right_item_image as student_right_image
          FROM matching_responses mr
          LEFT JOIN matching_pairs mp_correct ON mr.pair_id = mp_correct.pair_id
          LEFT JOIN matching_pairs mp_student ON mr.student_answer_pair_id = mp_student.pair_id
          WHERE mr.session_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$session['session_id']]);
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create a map of responses by pair_id for easy lookup
$responseMap = [];
foreach ($responses as $response) {
    $responseMap[$response['pair_id']] = $response;
}

// Debug: Check if we have responses
$hasResponses = count($responses) > 0;

// Calculate percentage
$percentage = ($session['total_pairs'] > 0) ? ($session['total_correct'] / $session['total_pairs']) * 100 : 0;

// Determine grade colors for dark theme
$gradeColor = '#ff6b6b';
$gradeText = 'Needs Improvement';

if ($percentage >= 90) {
    $gradeColor = '#00ff88';
    $gradeText = 'Excellent';
} elseif ($percentage >= 75) {
    $gradeColor = '#00d4ff';
    $gradeText = 'Good';
} elseif ($percentage >= 60) {
    $gradeColor = '#ffc107';
    $gradeText = 'Fair';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/tablogo.png">
    <title>Matching Game Results - <?php echo htmlspecialchars($game['title']); ?></title>
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
            color: #ffffff;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        /* Alert for already played */
        .alert-info {
            background: rgba(0, 212, 255, 0.1);
            border: 2px solid #00d4ff;
            border-radius: 12px;
            padding: 16px 24px;
            margin-bottom: 20px;
            color: #00d4ff;
            text-align: center;
            font-size: 15px;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.2);
            border-color: #00d4ff;
        }
        
        .results-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .score-header {
            background: rgba(0, 0, 0, 0.3);
            padding: 40px 30px;
            text-align: center;
            border-bottom: 2px solid <?php echo $gradeColor; ?>;
        }
        
        .score-value {
            font-size: 72px;
            font-weight: bold;
            color: <?php echo $gradeColor; ?>;
            margin-bottom: 10px;
            text-shadow: 0 0 30px <?php echo $gradeColor; ?>40;
        }
        
        .score-label {
            font-size: 24px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .grade-badge {
            display: inline-block;
            background: <?php echo $gradeColor; ?>;
            color: #1a1a2e;
            padding: 12px 35px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: bold;
            margin-top: 15px;
            box-shadow: 0 4px 15px <?php echo $gradeColor; ?>40;
        }
        
        .game-info {
            padding: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .game-title {
            font-size: 28px;
            color: #ffffff;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .game-title i {
            color: #9c27b0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 15px;
            background: rgba(0, 0, 0, 0.2);
            padding: 12px 16px;
            border-radius: 10px;
        }
        
        .info-item i {
            color: #00d4ff;
            width: 20px;
        }
        
        .info-item strong {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .answers-section {
            padding: 30px;
        }
        
        .section-title {
            font-size: 24px;
            color: #ffffff;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title i {
            color: #00d4ff;
        }
        
        .pair-card {
            background: rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .pair-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }
        
        .pair-card.correct {
            border-color: rgba(0, 255, 136, 0.5);
            background: rgba(0, 255, 136, 0.05);
        }
        
        .pair-card.incorrect {
            border-color: rgba(255, 107, 107, 0.5);
            background: rgba(255, 107, 107, 0.05);
        }
        
        .pair-number {
            font-weight: bold;
            color: #00d4ff;
            margin-bottom: 15px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .pair-content {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 20px;
            align-items: center;
        }
        
        .pair-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 12px;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .pair-item img {
            max-width: 100%;
            max-height: 150px;
            border-radius: 8px;
        }
        
        .pair-item-text {
            font-size: 16px;
            color: #ffffff;
            font-weight: 500;
        }
        
        .match-arrow {
            font-size: 32px;
            color: #00d4ff;
            font-weight: bold;
        }
        
        .correct-label {
            color: #00ff88;
            font-weight: bold;
            font-size: 14px;
        }
        
        .incorrect-label {
            color: #ff6b6b;
            font-weight: bold;
            font-size: 14px;
        }
        
        .no-response-label {
            color: #ffc107;
            font-weight: bold;
            font-size: 14px;
        }
        
        .answer-wrapper {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 12px;
            margin-top: 15px;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .answer-wrapper.correct {
            border-color: rgba(0, 255, 136, 0.5);
            background: rgba(0, 255, 136, 0.1);
        }
        
        .answer-wrapper.incorrect {
            border-color: rgba(255, 107, 107, 0.5);
            background: rgba(255, 107, 107, 0.1);
        }
        
        .answer-badge {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .answer-badge.correct {
            background: #00ff88;
            color: #1a1a2e;
        }
        
        .answer-badge.incorrect {
            background: #ff6b6b;
            color: #ffffff;
        }
        
        .stats-footer {
            background: rgba(0, 0, 0, 0.3);
            padding: 25px 30px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-item {
            text-align: center;
            background: rgba(255, 255, 255, 0.05);
            padding: 15px 25px;
            border-radius: 12px;
            min-width: 120px;
        }
        
        .stat-item .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #ffffff;
        }
        
        .stat-item .stat-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }
        
        .warning-box {
            background: rgba(255, 193, 7, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 2px solid rgba(255, 193, 7, 0.5);
            text-align: center;
            color: #ffc107;
        }
        
        .no-response-box {
            background: rgba(255, 193, 7, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            border: 2px solid rgba(255, 193, 7, 0.5);
            text-align: center;
            color: #ffc107;
        }
        
        /* Correct answers section styling */
        .correct-answer-card {
            background: rgba(0, 255, 136, 0.05);
            border: 2px solid rgba(0, 255, 136, 0.3);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .correct-answer-card .pair-number {
            color: #00ff88;
        }
        
        .correct-answer-card .match-arrow {
            color: #00ff88;
        }
        
        @media (max-width: 768px) {
            .pair-content {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .match-arrow {
                transform: rotate(90deg);
                font-size: 24px;
            }
            
            .score-value {
                font-size: 48px;
            }
            
            .score-label {
                font-size: 18px;
            }
            
            .game-title {
                font-size: 22px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-footer {
                padding: 20px;
            }
            
            .stat-item {
                min-width: 100px;
                padding: 12px 16px;
            }
        }
        
        @media print {
            .back-btn {
                display: none;
            }
            
            body {
                background: white;
                color: #333;
            }
            
            .results-card {
                background: white;
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['already_played'])): ?>
            <div class="alert-info">
                <strong>⚠️ Note:</strong> You have already completed this game. Only your first attempt counts toward the leaderboard.
            </div>
        <?php endif; ?>
        
        <a href="student-home.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="results-card">
            <!-- Score Header -->
            <div class="score-header">
                <div class="score-value"><?php echo number_format($session['total_score']); ?></div>
                <div class="score-label">Points Earned</div>
                <div style="font-size: 18px; color: rgba(255,255,255,0.7); margin: 10px 0;">
                    <?php echo $session['total_correct']; ?> out of <?php echo $session['total_pairs']; ?> Correct (<?php echo number_format($percentage, 1); ?>%)
                </div>
                <div class="grade-badge"><?php echo $gradeText; ?></div>
            </div>
            
            <!-- Game Info -->
            <div class="game-info">
                <div class="game-title">
                    <i class="fas fa-puzzle-piece"></i>
                    <?php echo htmlspecialchars($game['title']); ?>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-book"></i>
                        <span><strong>Subject:</strong> <?php echo htmlspecialchars($game['subject_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-user-tie"></i>
                        <span><strong>Teacher:</strong> <?php echo htmlspecialchars($game['teacher_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar-check"></i>
                        <span><strong>Completed:</strong> <?php echo date('M d, Y - g:i A', strtotime($session['completed_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span><strong>Time Limit:</strong> <?php echo $game['time_limit']; ?> seconds</span>
                    </div>
                </div>
            </div>
            
            <!-- Answers Sections -->
            <div class="answers-section">
                <!-- Your Answers Section -->
                <div class="section-title">
                    <i class="fas fa-user-edit"></i>
                    Your Answers
                </div>
                
                <?php if (!$hasResponses): ?>
                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 10px;"></i>
                        <p><strong>No detailed responses found for this session.</strong></p>
                        <p style="font-size: 14px; margin-top: 5px;">Session ID: <?php echo $session['session_id']; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php foreach ($pairs as $index => $pair): 
                    $response = isset($responseMap[$pair['pair_id']]) ? $responseMap[$pair['pair_id']] : null;
                    $isCorrect = $response && (int)$response['is_correct'] === 1;
                ?>
                    <div class="pair-card <?php echo $isCorrect ? 'correct' : 'incorrect'; ?>">
                        <div class="pair-number">
                            <span><i class="fas fa-link"></i> PAIR <?php echo $index + 1; ?></span>
                            <?php if ($response): ?>
                                <?php if ($isCorrect): ?>
                                    <span class="correct-label">
                                        <i class="fas fa-check-circle"></i> CORRECT
                                    </span>
                                <?php else: ?>
                                    <span class="incorrect-label">
                                        <i class="fas fa-times-circle"></i> INCORRECT
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="no-response-label">
                                    <i class="fas fa-exclamation-triangle"></i> NO RESPONSE
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($response): ?>
                            <div class="answer-wrapper <?php echo $isCorrect ? 'correct' : 'incorrect'; ?>">
                                <div class="pair-content">
                                    <!-- Left Item (Question/Prompt) -->
                                    <div class="pair-item" style="min-height: 150px;">
                                        <?php if (!empty($pair['left_item_image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($pair['left_item_image']); ?>" alt="Question" style="max-width: 100%; max-height: 180px; object-fit: contain;">
                                        <?php elseif (!empty($pair['left_item_text'])): ?>
                                            <div class="pair-item-text"><?php echo htmlspecialchars($pair['left_item_text']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Arrow -->
                                    <div class="match-arrow" style="color: <?php echo $isCorrect ? '#00ff88' : '#ff6b6b'; ?>;">→</div>
                                    
                                    <!-- Right Item (Student's Answer) -->
                                    <div class="pair-item" style="border-color: <?php echo $isCorrect ? 'rgba(0,255,136,0.5)' : 'rgba(255,107,107,0.5)'; ?>; position: relative; min-height: 150px;">
                                        <?php if (!empty($response['student_right_image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($response['student_right_image']); ?>" alt="Your Answer" style="max-width: 100%; max-height: 180px; object-fit: contain;">
                                        <?php elseif (!empty($response['student_right_text'])): ?>
                                            <div class="pair-item-text" style="font-weight: 600;"><?php echo htmlspecialchars($response['student_right_text']); ?></div>
                                        <?php else: ?>
                                            <div class="pair-item-text" style="color: rgba(255,255,255,0.5); font-style: italic;">
                                                No answer data<br>
                                                <small style="font-size: 12px;">Response ID: <?php echo $response['response_id'] ?? 'N/A'; ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Label Badge -->
                                        <div class="answer-badge <?php echo $isCorrect ? 'correct' : 'incorrect'; ?>">
                                            <i class="fas fa-<?php echo $isCorrect ? 'check' : 'times'; ?>"></i> Your Answer
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-response-box">
                                <i class="fas fa-exclamation-triangle"></i> No response recorded for this pair
                                <br><small style="font-size: 12px;">Pair ID: <?php echo $pair['pair_id']; ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <!-- Correct Answers Section -->
                <div class="section-title" style="margin-top: 40px;">
                    <i class="fas fa-check-double"></i>
                    Correct Answers
                </div>
                
                <?php foreach ($pairs as $index => $pair): ?>
                    <div class="correct-answer-card">
                        <div class="pair-number">
                            <i class="fas fa-link"></i> Pair <?php echo $index + 1; ?> - Correct Match
                        </div>
                        
                        <div class="answer-wrapper correct">
                            <div class="pair-content">
                                <!-- Left Item -->
                                <div class="pair-item">
                                    <?php if (!empty($pair['left_item_image'])): ?>
                                        <img src="../<?php echo htmlspecialchars($pair['left_item_image']); ?>" alt="Left Item">
                                    <?php else: ?>
                                        <div class="pair-item-text"><?php echo htmlspecialchars($pair['left_item_text']); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Arrow -->
                                <div class="match-arrow">↔</div>
                                
                                <!-- Right Item (correct answer) -->
                                <div class="pair-item" style="border-color: rgba(0,255,136,0.5);">
                                    <?php if (!empty($pair['right_item_image'])): ?>
                                        <img src="../<?php echo htmlspecialchars($pair['right_item_image']); ?>" alt="Correct Answer">
                                    <?php else: ?>
                                        <div class="pair-item-text"><?php echo htmlspecialchars($pair['right_item_text']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Stats Footer -->
            <div class="stats-footer">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $session['total_pairs']; ?></div>
                    <div class="stat-label">Total Pairs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #00ff88;"><?php echo $session['total_correct']; ?></div>
                    <div class="stat-label">Correct Matches</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: <?php echo $gradeColor; ?>;"><?php echo number_format($percentage, 1); ?>%</div>
                    <div class="stat-label">Accuracy</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #00d4ff;"><?php echo number_format($session['total_score']); ?></div>
                    <div class="stat-label">Points</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
