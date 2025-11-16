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
$error = '';
$success = '';

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

// Handle question deletion
if (isset($_GET['delete_question'])) {
    $question_id = $_GET['delete_question'];
    $delete_query = "DELETE FROM game_questions WHERE question_id = ? AND game_id = ?";
    $stmt = $pdo->prepare($delete_query);
    $stmt->execute([$question_id, $game_id]);
    header("Location: add-questions.php?game_id=" . $game_id);
    exit();
}

// Handle add question
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $question_text = trim($_POST['question_text']);
    $time_limit = $_POST['time_limit'];
    $points = $_POST['points'];
    $options = $_POST['options'];
    $correct_option = $_POST['correct_option'];
    
    if (empty($question_text)) {
        $error = 'Question text is required';
    } elseif (count(array_filter($options)) < 2) {
        $error = 'Please provide at least 2 answer options';
    } else {
        // Get next question order
        $order_query = "SELECT COALESCE(MAX(question_order), 0) + 1 as next_order FROM game_questions WHERE game_id = ?";
        $stmt = $pdo->prepare($order_query);
        $stmt->execute([$game_id]);
        $next_order = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
        
        // Insert question
        $query = "INSERT INTO game_questions (game_id, question_text, question_order, time_limit, points) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$game_id, $question_text, $next_order, $time_limit, $points]);
        $question_id = $pdo->lastInsertId();
        
        // Insert options
        $colors = ['red', 'blue', 'yellow', 'green'];
        $option_order = 0;
        foreach ($options as $index => $option_text) {
            if (!empty(trim($option_text))) {
                $is_correct = ($index == $correct_option) ? 1 : 0;
                $color = $colors[$option_order % 4];
                
                $option_query = "INSERT INTO game_options (question_id, option_text, is_correct, option_order, color_code) 
                                VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($option_query);
                $stmt->execute([$question_id, trim($option_text), $is_correct, $option_order, $color]);
                $option_order++;
            }
        }
        
        $success = 'Question added successfully!';
    }
}

// Get existing questions
$questions_query = "SELECT gq.*, 
                    (SELECT COUNT(*) FROM game_options WHERE question_id = gq.question_id) as option_count
                    FROM game_questions gq
                    WHERE gq.game_id = ?
                    ORDER BY gq.question_order";
$stmt = $pdo->prepare($questions_query);
$stmt->execute([$game_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Questions - <?php echo htmlspecialchars($game['title']); ?></title>
    <link rel="stylesheet" href="../teacher-dashboard.css">
    <style>
        .questions-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .game-info {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .game-info h2 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .game-info p {
            margin: 5px 0;
            color: #666;
        }
        
        .add-question-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input[type="text"],
        .form-group textarea,
        .form-group input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .option-input {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .option-input:hover {
            border-color: #26890D;
        }
        
        .option-input input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .option-input input[type="text"] {
            flex: 1;
            border: none;
            padding: 8px;
            font-size: 14px;
        }
        
        .option-input input[type="text"]:focus {
            outline: none;
        }
        
        .option-red { border-color: #e21b3c; }
        .option-blue { border-color: #1368ce; }
        .option-yellow { border-color: #ffa602; }
        .option-green { border-color: #26890D; }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
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
        
        .questions-list {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .question-item {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .question-text {
            font-weight: bold;
            color: #333;
            flex: 1;
        }
        
        .question-meta {
            font-size: 12px;
            color: #888;
            margin-bottom: 10px;
        }
        
        .question-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
        }
        
        .btn-delete:hover {
            background: #a71d2a;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
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
        
        .done-btn {
            background: #26890D;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="questions-container">
        <a href="manage-games.php" class="back-btn">← Back to Games</a>
        
        <div class="game-info">
            <h2><?php echo htmlspecialchars($game['title']); ?></h2>
            <p><strong>Subject:</strong> <?php echo htmlspecialchars($game['subject_name']); ?></p>
            <p><strong>Questions:</strong> <?php echo count($questions); ?></p>
            <?php if (!empty($game['due_date'])): ?>
                <p><strong>Due:</strong> <?php echo date('M d, Y g:i A', strtotime($game['due_date'])); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="add-question-form">
            <h3>Add New Question</h3>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="question_text">Question *</label>
                    <textarea id="question_text" name="question_text" required 
                              placeholder="Enter your question here..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Answer Options * (Select the correct answer)</label>
                    <div class="options-grid">
                        <div class="option-input option-red">
                            <input type="radio" name="correct_option" value="0" required>
                            <input type="text" name="options[]" placeholder="Option 1">
                        </div>
                        <div class="option-input option-blue">
                            <input type="radio" name="correct_option" value="1">
                            <input type="text" name="options[]" placeholder="Option 2">
                        </div>
                        <div class="option-input option-yellow">
                            <input type="radio" name="correct_option" value="2">
                            <input type="text" name="options[]" placeholder="Option 3">
                        </div>
                        <div class="option-input option-green">
                            <input type="radio" name="correct_option" value="3">
                            <input type="text" name="options[]" placeholder="Option 4">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="time_limit">Time Limit (seconds)</label>
                        <input type="number" id="time_limit" name="time_limit" value="<?php echo $game['time_limit']; ?>" min="10" max="300">
                    </div>
                    
                    <div class="form-group">
                        <label for="points">Points</label>
                        <input type="number" id="points" name="points" value="1000" min="100" max="5000" step="100">
                    </div>
                </div>
                
                <button type="submit" name="add_question" class="btn btn-primary">➕ Add Question</button>
            </form>
        </div>
        
        <?php if (count($questions) > 0): ?>
            <div class="questions-list">
                <h3>Questions (<?php echo count($questions); ?>)</h3>
                
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-item">
                        <div class="question-header">
                            <div class="question-text">
                                <?php echo ($index + 1); ?>. <?php echo htmlspecialchars($question['question_text']); ?>
                            </div>
                            <div class="question-actions">
                                <a href="?game_id=<?php echo $game_id; ?>&delete_question=<?php echo $question['question_id']; ?>" 
                                   class="btn-delete"
                                   onclick="return confirm('Delete this question?')">
                                    🗑️ Delete
                                </a>
                            </div>
                        </div>
                        <div class="question-meta">
                            ⏱️ <?php echo $question['time_limit']; ?>s | 
                            🎯 <?php echo $question['points']; ?> points | 
                            📝 <?php echo $question['option_count']; ?> options
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 20px; text-align: center;">
                    <a href="manage-games.php" class="done-btn">✓ Done Adding Questions</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
