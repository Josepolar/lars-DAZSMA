<?php
session_start();
include '../../Database/database.php';

// Check if user is TEACHER (role_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../teacher-login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Handle delete action
if (isset($_GET['delete_id']) && isset($_GET['game_type'])) {
    $game_id = $_GET['delete_id'];
    $game_type = $_GET['game_type'];
    
    if ($game_type == 'matching') {
        $delete_query = "DELETE FROM matching_games WHERE matching_game_id = ? AND teacher_id = ?";
    } else {
        $delete_query = "DELETE FROM game_activities WHERE game_id = ? AND teacher_id = ?";
    }
    
    $stmt = $pdo->prepare($delete_query);
    $stmt->execute([$game_id, $teacher_id]);
    header("Location: manage-games.php");
    exit();
}

// Handle status change
if (isset($_GET['change_status']) && isset($_GET['game_id']) && isset($_GET['game_type'])) {
    $game_id = $_GET['game_id'];
    $new_status = $_GET['change_status'];
    $game_type = $_GET['game_type'];
    
    if ($game_type == 'matching') {
        $status_query = "UPDATE matching_games SET status = ? WHERE matching_game_id = ? AND teacher_id = ?";
    } else {
        $status_query = "UPDATE game_activities SET status = ? WHERE game_id = ? AND teacher_id = ?";
    }
    
    $stmt = $pdo->prepare($status_query);
    $stmt->execute([$new_status, $game_id, $teacher_id]);
    header("Location: manage-games.php");
    exit();
}

// Get all games created by this teacher (both quiz and matching games)
$query = "SELECT ga.game_id, ga.title, ga.description, ga.time_limit, ga.show_leaderboard, 
       ga.status, ga.created_at, ga.updated_at, ga.teacher_id, ga.subject_id,
       ga.due_date,
       s.subject_name, 'quiz' as game_type_flag,
          (SELECT COUNT(*) FROM game_questions WHERE game_id = ga.game_id) as question_count,
          (SELECT COUNT(DISTINCT student_id) FROM game_sessions WHERE game_id = ga.game_id) as player_count
          FROM game_activities ga
          INNER JOIN subjects s ON ga.subject_id = s.subject_id
          WHERE ga.teacher_id = ?
          UNION ALL
          SELECT mg.matching_game_id as game_id, mg.title, mg.description, 
           mg.time_limit, mg.show_leaderboard, mg.status, mg.created_at, 
           mg.updated_at, mg.teacher_id, mg.subject_id,
           mg.due_date,
                 s.subject_name, 'matching' as game_type_flag,
          (SELECT COUNT(*) FROM matching_pairs WHERE matching_game_id = mg.matching_game_id) as question_count,
          (SELECT COUNT(DISTINCT student_id) FROM matching_sessions WHERE matching_game_id = mg.matching_game_id) as player_count
          FROM matching_games mg
          INNER JOIN subjects s ON mg.subject_id = s.subject_id
          WHERE mg.teacher_id = ?
          ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$teacher_id, $teacher_id]);
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Games</title>
    <link rel="stylesheet" href="../teacher-dashboard.css">
    <style>
        .games-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .game-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .create-btn {
            background: #26890D;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }
        
        .create-btn:hover {
            background: #1e6a0a;
        }
        
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .game-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .game-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .game-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .game-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #888;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .due-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 600;
            background: #e9f5ff;
            color: #0b5ed7;
            margin-bottom: 10px;
        }
        
        .due-badge.overdue {
            background: #fde2e1;
            color: #b02a37;
        }
        
        .due-badge.soon {
            background: #fff4cc;
            color: #b07900;
        }
        
        .status-draft {
            background: #ffd700;
            color: #000;
        }
        
        .status-active {
            background: #26890D;
            color: white;
        }
        
        .status-completed {
            background: #666;
            color: white;
        }
        
        .game-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            transition: all 0.2s;
        }
        
        .btn-edit {
            background: #007bff;
            color: white;
        }
        
        .btn-edit:hover {
            background: #0056b3;
        }
        
        .btn-questions {
            background: #28a745;
            color: white;
        }
        
        .btn-questions:hover {
            background: #1e7e34;
        }
        
        .btn-results {
            background: #6c757d;
            color: white;
        }
        
        .btn-results:hover {
            background: #545b62;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #a71d2a;
        }
        
        .btn-activate {
            background: #26890D;
            color: white;
        }
        
        .btn-activate:hover {
            background: #1e6a0a;
        }
        
        .no-games {
            text-align: center;
            padding: 60px 20px;
            color: #666;
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
    </style>
</head>
<body>
    <div class="games-container">
        <a href="../teacher-acts.php" class="back-btn">← Back to Dashboard</a>
        
        <div class="game-header">
            <h1>My Game Activities</h1>
            <a href="create-game.php" class="create-btn">+ Create New Game</a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                ✅ <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

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
                        
                        <span class="status-badge status-<?php echo $game['status']; ?>">
                            <?php echo strtoupper($game['status']); ?>
                        </span>

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
                            <span class="<?php echo $due_badge_class; ?>"><?php echo htmlspecialchars($due_badge_text); ?></span>
                        <?php endif; ?>
                        
                        <div class="game-description">
                            <?php echo htmlspecialchars($game['description'] ?: 'No description'); ?>
                        </div>
                        
                        <div class="game-meta">
                            <span>📚 <?php echo htmlspecialchars($game['subject_name']); ?></span>
                            <span>
                                <?php if ($game['game_type_flag'] == 'matching'): ?>
                                    🧩 <?php echo $game['question_count']; ?> pairs
                                <?php else: ?>
                                    ❓ <?php echo $game['question_count']; ?> questions
                                <?php endif; ?>
                            </span>
                            <span>👥 <?php echo $game['player_count']; ?> players</span>
                        </div>
                        
                        <div class="game-actions">
                            <?php if ($game['game_type_flag'] == 'matching'): ?>
                                <a href="add-matching-pairs.php?matching_game_id=<?php echo $game['game_id']; ?>" class="action-btn btn-questions">
                                    ➕ Pairs
                                </a>
                                <a href="matching-game-results.php?matching_game_id=<?php echo $game['game_id']; ?>" class="action-btn btn-results">
                                    📊 Results
                                </a>
                            <?php else: ?>
                                <a href="add-questions.php?game_id=<?php echo $game['game_id']; ?>" class="action-btn btn-questions">
                                    ➕ Questions
                                </a>
                                <a href="game-results.php?game_id=<?php echo $game['game_id']; ?>" class="action-btn btn-results">
                                    📊 Results
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($game['status'] == 'draft'): ?>
                                <a href="?change_status=active&game_id=<?php echo $game['game_id']; ?>&game_type=<?php echo $game['game_type_flag']; ?>" 
                                   class="action-btn btn-activate"
                                   onclick="return confirm('Activate this game for students?')">
                                    🚀 Activate
                                </a>
                            <?php elseif ($game['status'] == 'active'): ?>
                                <a href="?change_status=draft&game_id=<?php echo $game['game_id']; ?>&game_type=<?php echo $game['game_type_flag']; ?>" 
                                   class="action-btn"
                                   style="background: #ffc107; color: #000;"
                                   onclick="return confirm('Deactivate this game?')">
                                    ⏸️ Deactivate
                                </a>
                            <?php endif; ?>
                            
                            <a href="?delete_id=<?php echo $game['game_id']; ?>&game_type=<?php echo $game['game_type_flag']; ?>" 
                               class="action-btn btn-delete"
                               onclick="return confirm('Delete this game? This action cannot be undone!')">
                                🗑️ Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-games">
                <h2>No games yet</h2>
                <p>Create your first game activity to get started!</p>
                <a href="create-game.php" class="create-btn" style="display: inline-block; margin-top: 20px;">
                    + Create New Game
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
