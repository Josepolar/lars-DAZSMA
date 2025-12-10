<?php
session_start();
include '../../Database/database.php';

// Check if user is STUDENT (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: ../stud-login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get student's grade level
$student_query = "SELECT grade_level FROM users WHERE user_id = ?";
$student_stmt = $pdo->prepare($student_query);
$student_stmt->execute([$student_id]);
$student = $student_stmt->fetch(PDO::FETCH_ASSOC);
$student_grade = $student['grade_level'];

// DEBUG: Check what we're searching for
// echo "Student Grade: " . $student_grade . "<br>";

// Get games from subjects matching student's grade level (both quiz and matching games)
$query = "SELECT ga.game_id, ga.title, ga.description, ga.time_limit, ga.show_leaderboard, 
          ga.status, ga.created_at, ga.updated_at, ga.teacher_id, ga.subject_id,
          ga.due_date,
          s.subject_name, s.grade_level as subject_grade, 
          CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
          'quiz' as game_type_flag,
          (SELECT COUNT(*) FROM game_questions WHERE game_id = ga.game_id) as question_count,
          (SELECT COUNT(*) FROM game_sessions WHERE game_id = ga.game_id AND student_id = ? AND completed_at IS NOT NULL) as played,
          (SELECT total_score FROM game_sessions WHERE game_id = ga.game_id AND student_id = ? AND completed_at IS NOT NULL ORDER BY total_score DESC LIMIT 1) as best_score
          FROM game_activities ga
          INNER JOIN subjects s ON ga.subject_id = s.subject_id
          INNER JOIN users u ON ga.teacher_id = u.user_id
          WHERE s.grade_level = ? 
          AND ga.status = 'active'
          AND (ga.due_date IS NULL OR ga.due_date >= NOW())
          AND NOT EXISTS (
              SELECT 1 FROM game_sessions gs 
              WHERE gs.game_id = ga.game_id 
              AND gs.student_id = ? 
              AND gs.completed_at IS NOT NULL
          )
          UNION ALL
           SELECT mg.matching_game_id as game_id, mg.title, mg.description, mg.time_limit, 
               mg.show_leaderboard, mg.status, mg.created_at, mg.updated_at, 
               mg.teacher_id, mg.subject_id,
               mg.due_date,
                 s.subject_name, s.grade_level as subject_grade, 
                 CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
                 'matching' as game_type_flag,
          (SELECT COUNT(*) FROM matching_pairs WHERE matching_game_id = mg.matching_game_id) as question_count,
          (SELECT COUNT(*) FROM matching_sessions WHERE matching_game_id = mg.matching_game_id AND student_id = ? AND completed_at IS NOT NULL) as played,
          (SELECT total_score FROM matching_sessions WHERE matching_game_id = mg.matching_game_id AND student_id = ? AND completed_at IS NOT NULL ORDER BY total_score DESC LIMIT 1) as best_score
          FROM matching_games mg
          INNER JOIN subjects s ON mg.subject_id = s.subject_id
          INNER JOIN users u ON mg.teacher_id = u.user_id
          WHERE s.grade_level = ? 
          AND mg.status = 'active'
          AND (mg.due_date IS NULL OR mg.due_date >= NOW())
          AND NOT EXISTS (
              SELECT 1 FROM matching_sessions ms 
              WHERE ms.matching_game_id = mg.matching_game_id 
              AND ms.student_id = ? 
              AND ms.completed_at IS NOT NULL
          )
          ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$student_id, $student_id, $student_grade, $student_id, $student_id, $student_id, $student_grade, $student_id]);
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

// DEBUG: See what games we found
// echo "Games found: " . count($games) . "<br>";
// if (count($games) > 0) {
//     echo "<pre>"; print_r($games); echo "</pre>";
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/tablogo.png">
    <title>Available Games</title>
    <link rel="stylesheet" href="../student-home.css">
    <style>
        .games-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #666;
        }
        
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .game-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .game-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #e21b3c, #1368ce, #ffa602, #26890D);
        }
        
        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .game-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .game-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .game-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #888;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .played-badge {
            background: #26890D;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .due-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e9f5ff;
            color: #0b5ed7;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .due-badge.overdue {
            background: #fde2e1;
            color: #b02a37;
        }
        
        .due-badge.soon {
            background: #fff4cc;
            color: #946200;
        }
        
        .best-score {
            background: #ffd700;
            color: #000;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .play-btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: #26890D;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.2s;
        }
        
        .play-btn:hover {
            background: #1e6a0a;
            transform: scale(1.02);
        }
        
        .replay-btn {
            background: #007bff;
        }
        
        .replay-btn:hover {
            background: #0056b3;
        }
        
        .no-games {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .no-games h2 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .no-games p {
            color: #888;
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
        
        .back-btn:hover {
            background: #555;
        }
        
        .my-scores-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        
        .my-scores-btn:hover {
            background: #0056b3;
        }
        
        /* ============================================
           RESPONSIVE DESIGN FOR AVAILABLE GAMES
           ============================================ */
        
        /* Mobile Phones (up to 575px) */
        @media (max-width: 575px) {
            .games-container {
                margin: 10px;
                padding: 10px;
            }
            
            .page-header {
                padding: 20px 15px;
                margin-bottom: 20px;
            }
            
            .page-header h1 {
                font-size: 24px;
            }
            
            .page-header p {
                font-size: 14px;
            }
            
            .games-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .game-card {
                padding: 20px;
            }
            
            .game-card h2 {
                font-size: 20px;
            }
            
            .game-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .game-info {
                grid-template-columns: 1fr;
                gap: 8px;
                font-size: 13px;
            }
            
            .info-item {
                font-size: 13px;
            }
            
            .game-description {
                font-size: 13px;
            }
            
            .start-game-btn {
                padding: 12px 25px;
                font-size: 15px;
                width: 100%;
            }
            
            .back-btn,
            .my-scores-btn {
                padding: 8px 15px;
                font-size: 14px;
                display: block;
                text-align: center;
                margin: 10px 0;
            }
            
            .no-games {
                padding: 30px 15px;
            }
            
            .no-games h2 {
                font-size: 20px;
            }
        }
        
        /* Tablets Portrait (576px to 767px) */
        @media (min-width: 576px) and (max-width: 767px) {
            .games-container {
                margin: 15px;
                padding: 15px;
            }
            
            .page-header {
                padding: 25px;
            }
            
            .games-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }
            
            .game-card {
                padding: 22px;
            }
        }
        
        /* Tablets Landscape (768px to 991px) */
        @media (min-width: 768px) and (max-width: 991px) {
            .games-container {
                max-width: 100%;
                padding: 20px;
            }
            
            .games-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }
        
        /* Laptops (992px to 1199px) */
        @media (min-width: 992px) and (max-width: 1199px) {
            .games-container {
                max-width: 960px;
            }
            
            .games-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Large Screens (1200px and up) */
        @media (min-width: 1200px) {
            .games-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .start-game-btn,
            .back-btn,
            .my-scores-btn {
                min-height: 44px;
                padding: 12px 20px;
            }
            
            .game-card:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div class="games-container">
        <div style="margin-bottom: 20px;">
            <a href="../student-home.php" class="back-btn">← Back to Home</a>
            <a href="my-scores.php" class="my-scores-btn">📊 My Scores</a>
        </div>
        
        <div class="page-header">
            <h1>🎮 Game Activities</h1>
            <p>Challenge yourself with interactive quiz and matching games from your subjects!</p>
        </div>

        <?php if (count($games) > 0): ?>
            <div class="games-grid">
                <?php foreach ($games as $game): ?>
                    <div class="game-card">
                        <div class="game-title">
                            <?php if ($game['game_type_flag'] == 'matching'): ?>
                                🧩 
                            <?php else: ?>
                                🎯 
                            <?php endif; ?>
                            <?php echo htmlspecialchars($game['title']); ?>
                        </div>
                        
                        <div class="game-description">
                            <?php 
                            if ($game['game_type_flag'] == 'matching') {
                                echo htmlspecialchars($game['description'] ?: 'Match related items in this fun puzzle game!');
                            } else {
                                echo htmlspecialchars($game['description'] ?: 'Test your knowledge with this exciting quiz game!');
                            }
                            ?>
                        </div>
                        <?php
                            $due_badge_text = '';
                            $due_badge_class = 'due-badge';
                            if (!empty($game['due_date'])) {
                                $due_ts = strtotime($game['due_date']);
                                $now_ts = time();
                                if ($due_ts !== false) {
                                    $formatted_due = date('M d, Y g:i A', $due_ts);
                                    if ($due_ts < $now_ts) {
                                        $due_badge_class .= ' overdue';
                                        $due_badge_text = 'Past Due • ' . $formatted_due;
                                    } elseif (($due_ts - $now_ts) <= 86400) {
                                        $due_badge_class .= ' soon';
                                        $due_badge_text = 'Due Soon • ' . $formatted_due;
                                    } else {
                                        $due_badge_text = 'Due • ' . $formatted_due;
                                    }
                                }
                            }
                        ?>
                        <?php if (!empty($due_badge_text)): ?>
                            <div class="<?php echo $due_badge_class; ?>">
                                ⏰ <?php echo htmlspecialchars($due_badge_text); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="game-meta">
                            <span class="meta-item">📚 <?php echo htmlspecialchars($game['subject_name']); ?></span>
                            <span class="meta-item">👨‍🏫 <?php echo htmlspecialchars($game['teacher_name']); ?></span>
                            <?php if ($game['game_type_flag'] == 'matching'): ?>
                                <span class="meta-item">🧩 <?php echo $game['question_count']; ?> pairs</span>
                                <span class="meta-item">⏱️ <?php echo $game['time_limit']; ?>s total</span>
                            <?php else: ?>
                                <span class="meta-item">❓ <?php echo $game['question_count']; ?> questions</span>
                                <span class="meta-item">⏱️ <?php echo $game['time_limit']; ?>s per question</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($game['game_type_flag'] == 'matching'): ?>
                            <a href="play-matching-game.php?matching_game_id=<?php echo $game['game_id']; ?>" class="play-btn">
                                ▶️ Start Matching Game
                            </a>
                        <?php else: ?>
                            <a href="play-game.php?game_id=<?php echo $game['game_id']; ?>" class="play-btn">
                                ▶️ Start Quiz Game
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-games">
                <h2>No Games Available</h2>
                <p>Your teachers haven't created any game activities yet. Check back later!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
