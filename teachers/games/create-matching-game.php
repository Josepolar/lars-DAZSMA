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
$default_due_date = date('Y-m-d\TH:i', strtotime('+7 days'));

// Get teacher's subjects
$query = "SELECT DISTINCT s.subject_id, s.subject_name 
          FROM subjects s 
          INNER JOIN teacher_subjects ts ON s.subject_id = ts.subject_id 
          WHERE ts.teacher_id = ?
          ORDER BY s.subject_name";
$stmt = $pdo->prepare($query);
$stmt->execute([$teacher_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pre-fill from create-game.php if redirected
$prefill = [
    'title' => $_GET['title'] ?? '',
    'description' => $_GET['description'] ?? '',
    'subject_id' => $_GET['subject_id'] ?? '',
    'time_limit' => $_GET['time_limit'] ?? 300,
    'show_leaderboard' => $_GET['show_leaderboard'] ?? 1,
    'due_date' => $_GET['due_date'] ?? $default_due_date
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject_id = $_POST['subject_id'];
    $game_type = $_POST['game_type'];
    $time_limit = $_POST['time_limit'];
    $show_leaderboard = isset($_POST['show_leaderboard']) ? 1 : 0;
    $due_date_input = $_POST['due_date'] ?? $prefill['due_date'];
    $due_date = null;
    $due_date_error = false;
    
    if (empty($due_date_input)) {
        $error = 'Please set a due date and time for the matching game';
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
    } elseif (empty($game_type)) {
        $error = 'Please select a matching game type';
    } elseif (!$due_date_error) {
        // Verify teacher teaches this subject
        $verify = "SELECT 1 FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?";
        $stmt = $pdo->prepare($verify);
        $stmt->execute([$teacher_id, $subject_id]);
        
        if ($stmt->fetch()) {
            $query = "INSERT INTO matching_games (subject_id, teacher_id, title, description, game_type, time_limit, show_leaderboard, due_date) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$subject_id, $teacher_id, $title, $description, $game_type, $time_limit, $show_leaderboard, $due_date]);
            
            $matching_game_id = $pdo->lastInsertId();
            header("Location: add-matching-pairs.php?matching_game_id=" . $matching_game_id);
            exit();
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
    <title>Create Matching Game</title>
    <link rel="stylesheet" href="../../admin/admin-dashboard.css">
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
        
        .game-type-preview {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #26890D;
        }
        
        .game-type-preview h3 {
            color: #26890D;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .game-type-preview p {
            color: #666;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .type-example {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 13px;
        }
        
        .type-example strong {
            color: #333;
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
        <a href="create-game.php" class="back-btn">← Back</a>
        
        <div class="form-card">
            <div class="form-header">
                <h1>🧩 Create Matching Game</h1>
                <p>Create an interactive matching game where students connect related items</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Game Title *</label>
                    <input type="text" id="title" name="title" required 
                           placeholder="e.g., Match Animals to Their Habitats"
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : htmlspecialchars($prefill['title']); ?>">
                    <div class="help-text">Choose a clear, descriptive title for your matching game</div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Describe what students will match..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : htmlspecialchars($prefill['description']); ?></textarea>
                    <div class="help-text">Optional: Add details about the matching activity</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="subject_id">Subject *</label>
                        <select id="subject_id" name="subject_id" required>
                            <option value="">Select a subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['subject_id']; ?>"
                                        <?php echo ((isset($_POST['subject_id']) && $_POST['subject_id'] == $subject['subject_id']) || 
                                                    ($prefill['subject_id'] == $subject['subject_id'])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="time_limit">Total Game Time (seconds) *</label>
                        <input type="number" id="time_limit" name="time_limit" min="60" max="1800" 
                               value="<?php echo isset($_POST['time_limit']) ? $_POST['time_limit'] : $prefill['time_limit']; ?>" required>
                        <div class="help-text">Recommended: 180-600 seconds (3-10 min)</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="due_date">Due Date &amp; Time *</label>
                    <input type="datetime-local" id="due_date" name="due_date" required
                           min="<?php echo date('Y-m-d\TH:i'); ?>"
                           value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : htmlspecialchars($prefill['due_date']); ?>">
                    <div class="help-text">Students can only start this matching game before the due date.</div>
                </div>
                
                <div class="form-group">
                    <label for="game_type">Matching Type *</label>
                    <select id="game_type" name="game_type" required onchange="updateGameTypePreview(this.value)">
                        <option value="">Select matching type</option>
                        <option value="image-to-text">🖼️ Image to Text (Match pictures with words)</option>
                        <option value="text-to-text">📝 Text to Text (Match related words/concepts)</option>
                        <option value="image-to-image">🎨 Image to Image (Match related pictures)</option>
                        <option value="number-to-text">🔢 Number to Text (Match numbers with words)</option>
                    </select>
                    <div class="help-text">Choose how students will match items</div>
                </div>
                
                <div id="game-type-preview" style="display: none;">
                    <!-- Preview will be populated by JavaScript -->
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="show_leaderboard" name="show_leaderboard" value="1" 
                               <?php echo (!isset($_POST['show_leaderboard']) || $_POST['show_leaderboard'] || $prefill['show_leaderboard']) ? 'checked' : ''; ?>>
                        <label for="show_leaderboard">Show leaderboard to students</label>
                    </div>
                    <div class="help-text">Students will see their ranking after completing the game</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Game & Add Pairs</button>
                    <a href="create-game.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function updateGameTypePreview(gameType) {
            const previewDiv = document.getElementById('game-type-preview');
            
            if (!gameType) {
                previewDiv.style.display = 'none';
                return;
            }
            
            const previews = {
                'image-to-text': {
                    title: '🖼️ Image to Text Matching',
                    description: 'Students will match images on the left with corresponding text/words on the right.',
                    example: 'Example: Match animal pictures with their names, or flag images with country names.'
                },
                'text-to-text': {
                    title: '📝 Text to Text Matching',
                    description: 'Students will match related words, definitions, or concepts.',
                    example: 'Example: Match vocabulary words with definitions, or capital cities with countries.'
                },
                'image-to-image': {
                    title: '🎨 Image to Image Matching',
                    description: 'Students will match related images together.',
                    example: 'Example: Match baby animal pictures with adult animals, or tools with their uses.'
                },
                'number-to-text': {
                    title: '🔢 Number to Text Matching',
                    description: 'Students will match numbers with their written forms or related concepts.',
                    example: 'Example: Match "5" with "five", or math problems with their answers.'
                }
            };
            
            const preview = previews[gameType];
            if (preview) {
                previewDiv.innerHTML = `
                    <div class="game-type-preview">
                        <h3>${preview.title}</h3>
                        <p>${preview.description}</p>
                        <div class="type-example">
                            <strong>📌 ${preview.example}</strong>
                        </div>
                    </div>
                `;
                previewDiv.style.display = 'block';
            }
        }
    </script>
</body>
</html>
