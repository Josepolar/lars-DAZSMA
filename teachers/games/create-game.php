<?php
session_start();
include '../../Database/database.php';

// Check if user is TEACHER (role_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../teacher-login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$error = '';
$success = '';
$due_date_input = date('Y-m-d\TH:i', strtotime('+7 days'));

// Get teacher's subjects
$query = "SELECT DISTINCT s.subject_id, s.subject_name 
          FROM subjects s 
          INNER JOIN teacher_subjects ts ON s.subject_id = ts.subject_id 
          WHERE ts.teacher_id = ?
          ORDER BY s.subject_name";
$stmt = $pdo->prepare($query);
$stmt->execute([$teacher_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject_id = $_POST['subject_id'];
    $time_limit = $_POST['time_limit'];
    $show_leaderboard = isset($_POST['show_leaderboard']) ? 1 : 0;
    $game_type = $_POST['game_type'] ?? 'quiz'; // quiz or matching
    $due_date_input = $_POST['due_date'] ?? $due_date_input;
    $due_date = null;
    $due_date_error = false;
    
    if (empty($due_date_input)) {
        $error = 'Please set a due date and time for the game';
        $due_date_error = true;
    } else {
        $due_date_obj = DateTime::createFromFormat('Y-m-d\TH:i', $due_date_input);
        if (!$due_date_obj) {
            $error = 'Invalid due date format. Please use the picker provided.';
            $due_date_error = true;
        } else {
            $now = new DateTime();
            if ($due_date_obj <= $now) {
                $error = 'Due date must be set in the future.';
                $due_date_error = true;
            } else {
                $due_date = $due_date_obj->format('Y-m-d H:i:s');
            }
        }
    }
    
    // Validation
    if (empty($title)) {
        $error = 'Game title is required';
    } elseif (empty($subject_id)) {
        $error = 'Please select a subject';
    } elseif (!$due_date_error) {
        // Verify teacher teaches this subject
        $verify = "SELECT 1 FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?";
        $stmt = $pdo->prepare($verify);
        $stmt->execute([$teacher_id, $subject_id]);
        
        if ($stmt->fetch()) {
            if ($game_type == 'matching') {
                // Redirect to create matching game
                $redirect_url = "create-matching-game.php?subject_id=$subject_id";
                $redirect_url .= "&title=" . urlencode($title);
                $redirect_url .= "&description=" . urlencode($description);
                $redirect_url .= "&time_limit=$time_limit";
                $redirect_url .= "&show_leaderboard=$show_leaderboard";
                $redirect_url .= "&due_date=" . urlencode($due_date_input);
                header("Location: $redirect_url");
                exit();
            } else {
                // Create quiz game (existing functionality)
                $query = "INSERT INTO game_activities (subject_id, teacher_id, title, description, time_limit, show_leaderboard, due_date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$subject_id, $teacher_id, $title, $description, $time_limit, $show_leaderboard, $due_date]);
                
                $game_id = $pdo->lastInsertId();
                header("Location: add-questions.php?game_id=" . $game_id);
                exit();
            }
        } else {
            $error = 'You do not have permission to create games for this subject';
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
    <title>Create Game Activity</title>
    <link rel="stylesheet" href="../teacher-dashboard.css">
    <style>
        .create-game-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-header {
            margin-bottom: 30px;
        }
        
        .form-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: #666;
            font-size: 14px;
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
        .form-group select,
        .form-group input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #26890D;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
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
        
        .btn-secondary:hover {
            background: #545b62;
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
        
        .help-text {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
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
    <div class="create-game-container">
        <a href="manage-games.php" class="back-btn">← Back to Games</a>
        
        <div class="form-card">
            <div class="form-header">
                <h1>Create New Game Activity</h1>
                <p>Create an engaging quiz or matching game for your students</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="game_type">Game Type *</label>
                    <select id="game_type" name="game_type" required onchange="updateGameTypeInfo(this.value)">
                        <option value="quiz" <?php echo (!isset($_POST['game_type']) || $_POST['game_type'] == 'quiz') ? 'selected' : ''; ?>>
                            🎯 Quiz Game (Multiple Choice Questions)
                        </option>
                        <option value="matching" <?php echo (isset($_POST['game_type']) && $_POST['game_type'] == 'matching') ? 'selected' : ''; ?>>
                            🧩 Matching Game (Match Items/Images)
                        </option>
                    </select>
                    <div class="help-text" id="game-type-help">
                        Quiz games test knowledge with multiple-choice questions
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="title">Game Title *</label>
                    <input type="text" id="title" name="title" required 
                           placeholder="e.g., Chapter 1 Quiz - Introduction to Programming"
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    <div class="help-text">Choose a clear, descriptive title for your game</div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Describe what this game covers..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    <div class="help-text">Optional: Add details about topics covered or learning objectives</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="subject_id">Subject *</label>
                        <select id="subject_id" name="subject_id" required>
                            <option value="">Select a subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['subject_id']; ?>"
                                        <?php echo (isset($_POST['subject_id']) && $_POST['subject_id'] == $subject['subject_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="time_limit">Time per Question (seconds) *</label>
                        <input type="number" id="time_limit" name="time_limit" min="10" max="300" 
                               value="<?php echo isset($_POST['time_limit']) ? $_POST['time_limit'] : 30; ?>" required>
                        <div class="help-text">Recommended: 20-60 seconds</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="due_date">Due Date &amp; Time *</label>
                    <input type="datetime-local" id="due_date" name="due_date" required
                           min="<?php echo date('Y-m-d\TH:i'); ?>"
                           value="<?php echo htmlspecialchars($due_date_input); ?>">
                    <div class="help-text">Students can only launch this game before the due date.</div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="show_leaderboard" name="show_leaderboard" value="1" 
                               <?php echo (!isset($_POST['show_leaderboard']) || $_POST['show_leaderboard']) ? 'checked' : ''; ?>>
                        <label for="show_leaderboard">Show leaderboard to students</label>
                    </div>
                    <div class="help-text">Students will see their ranking after completing the game</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Game & Add Questions</button>
                    <a href="manage-games.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function updateGameTypeInfo(gameType) {
            const helpText = document.getElementById('game-type-help');
            const timeLimitLabel = document.querySelector('label[for="time_limit"]');
            const timeLimitHelp = timeLimitLabel.parentElement.querySelector('.help-text');
            
            if (gameType === 'matching') {
                helpText.textContent = 'Matching games let students connect related items, images, or words';
                timeLimitLabel.textContent = 'Total Game Time (seconds) *';
                timeLimitHelp.textContent = 'Recommended: 180-600 seconds (3-10 minutes)';
            } else {
                helpText.textContent = 'Quiz games test knowledge with multiple-choice questions';
                timeLimitLabel.textContent = 'Time per Question (seconds) *';
                timeLimitHelp.textContent = 'Recommended: 20-60 seconds';
            }
        }
    </script>
</body>
</html>
