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

// Determine grade
$gradeColor = '#721c24';
$gradeBg = '#f8d7da';
$gradeText = 'Needs Improvement';

if ($percentage >= 90) {
    $gradeColor = '#155724';
    $gradeBg = '#d4edda';
    $gradeText = 'Excellent';
} elseif ($percentage >= 75) {
    $gradeColor = '#0c5460';
    $gradeBg = '#d1ecf1';
    $gradeText = 'Good';
} elseif ($percentage >= 60) {
    $gradeColor = '#856404';
    $gradeBg = '#fff3cd';
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .back-btn {
            background: white;
            color: #667eea;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .results-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .score-header {
            background: <?php echo $gradeBg; ?>;
            padding: 40px 30px;
            text-align: center;
            border-bottom: 4px solid <?php echo $gradeColor; ?>;
        }
        
        .score-value {
            font-size: 64px;
            font-weight: bold;
            color: <?php echo $gradeColor; ?>;
            margin-bottom: 10px;
        }
        
        .score-label {
            font-size: 28px;
            color: <?php echo $gradeColor; ?>;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .grade-badge {
            display: inline-block;
            background: <?php echo $gradeColor; ?>;
            color: white;
            padding: 10px 30px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .game-info {
            padding: 30px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .game-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
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
            gap: 8px;
            color: #666;
            font-size: 15px;
        }
        
        .info-item i {
            color: #667eea;
            width: 20px;
        }
        
        .answers-section {
            padding: 30px;
        }
        
        .section-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .pair-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .pair-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .pair-number {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .pair-content {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 20px;
            align-items: center;
        }
        
        .pair-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            border: 2px solid #dee2e6;
        }
        
        .pair-item img {
            max-width: 100%;
            max-height: 150px;
            border-radius: 6px;
        }
        
        .pair-item-text {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        
        .match-arrow {
            font-size: 32px;
            color: #26890D;
            font-weight: bold;
        }
        
        .correct-label {
            text-align: center;
            margin-top: 10px;
            font-weight: 600;
            color: #26890D;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .stats-footer {
            background: #f8f9fa;
            padding: 20px 30px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
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
                font-size: 20px;
            }
            
            .game-title {
                font-size: 22px;
            }
        }
        
        @media print {
            .back-btn {
                display: none;
            }
            
            body {
                background: white;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="student-home.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="results-card">
            <!-- Score Header -->
            <div class="score-header">
                <div class="score-value"><?php echo number_format($session['total_score']); ?></div>
                <div class="score-label">Points Earned</div>
                <div style="font-size: 18px; color: <?php echo $gradeColor; ?>; margin: 10px 0;">
                    <?php echo $session['total_correct']; ?> out of <?php echo $session['total_pairs']; ?> Correct (<?php echo number_format($percentage, 1); ?>%)
                </div>
                <div class="grade-badge"><?php echo $gradeText; ?></div>
            </div>
            
            <!-- Game Info -->
            <div class="game-info">
                <div class="game-title">
                    <i class="fas fa-puzzle-piece" style="color: #9c27b0;"></i>
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
                    <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 2px solid #ffc107; text-align: center; color: #856404;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 10px;"></i>
                        <p><strong>No detailed responses found for this session.</strong></p>
                        <p style="font-size: 14px; margin-top: 5px;">Session ID: <?php echo $session['session_id']; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php foreach ($pairs as $index => $pair): 
                    $response = isset($responseMap[$pair['pair_id']]) ? $responseMap[$pair['pair_id']] : null;
                    $isCorrect = $response && (int)$response['is_correct'] === 1;
                    $borderColor = $isCorrect ? '#28a745' : '#dc3545';
                    $bgColor = $isCorrect ? '#d4edda' : '#f8d7da';
                ?>
                    <div class="pair-card" style="border-color: <?php echo $borderColor; ?>; background: <?php echo $bgColor; ?>;">
                        <div class="pair-number" style="display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="fas fa-link"></i> PAIR <?php echo $index + 1; ?></span>
                            <?php if ($response): ?>
                                <?php if ($isCorrect): ?>
                                    <span style="color: #28a745; font-weight: bold; font-size: 14px;">
                                        <i class="fas fa-check-circle"></i> CORRECT
                                    </span>
                                <?php else: ?>
                                    <span style="color: #dc3545; font-weight: bold; font-size: 14px;">
                                        <i class="fas fa-times-circle"></i> INCORRECT
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #ffc107; font-weight: bold; font-size: 14px;">
                                    <i class="fas fa-exclamation-triangle"></i> NO RESPONSE
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($response): ?>
                            <div style="background: white; padding: 20px; border-radius: 8px; margin-top: 15px; border: 2px solid <?php echo $borderColor; ?>;">
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
                                    <div class="match-arrow" style="color: <?php echo $borderColor; ?>;">→</div>
                                    
                                    <!-- Right Item (Student's Answer) -->
                                    <div class="pair-item" style="border-color: <?php echo $borderColor; ?>; border-width: 3px; position: relative; min-height: 150px; background: <?php echo $isCorrect ? '#e8f5e9' : '#ffebee'; ?>;">
                                        <?php if (!empty($response['student_right_image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($response['student_right_image']); ?>" alt="Your Answer" style="max-width: 100%; max-height: 180px; object-fit: contain;">
                                        <?php elseif (!empty($response['student_right_text'])): ?>
                                            <div class="pair-item-text" style="font-weight: 600;"><?php echo htmlspecialchars($response['student_right_text']); ?></div>
                                        <?php else: ?>
                                            <div class="pair-item-text" style="color: #999; font-style: italic;">
                                                No answer data<br>
                                                <small style="font-size: 12px;">Response ID: <?php echo $response['response_id'] ?? 'N/A'; ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Label Badge -->
                                        <div style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); background: <?php echo $borderColor; ?>; color: white; padding: 6px 16px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                            <i class="fas fa-<?php echo $isCorrect ? 'check' : 'times'; ?>"></i> Your Answer
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 15px; border: 2px solid #ffc107; text-align: center; color: #856404;">
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
                    <div class="pair-card" style="border-color: #28a745; background: #d4edda;">
                        <div class="pair-number" style="color: #155724;">
                            <i class="fas fa-link"></i> Pair <?php echo $index + 1; ?> - Correct Match
                        </div>
                        
                        <div style="background: white; padding: 15px; border-radius: 8px; margin-top: 15px; border: 2px solid #28a745;">
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
                                <div class="match-arrow" style="color: #28a745;">↔</div>
                                
                                <!-- Right Item (correct answer) -->
                                <div class="pair-item" style="border-color: #28a745; border-width: 3px;">
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
                    <div class="stat-value" style="color: #26890D;"><?php echo $session['total_correct']; ?></div>
                    <div class="stat-label">Correct Matches</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: <?php echo $gradeColor; ?>;"><?php echo number_format($percentage, 1); ?>%</div>
                    <div class="stat-label">Accuracy</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #667eea;"><?php echo number_format($session['total_score']); ?></div>
                    <div class="stat-label">Points</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
