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

// Get typing game ID
if (!isset($_GET['typing_game_id'])) {
    header("Location: manage-games.php");
    exit();
}

$typing_game_id = $_GET['typing_game_id'];

// Verify this game belongs to the teacher
$query = "SELECT tg.*, s.subject_name 
          FROM typing_games tg
          INNER JOIN subjects s ON tg.subject_id = s.subject_id
          WHERE tg.typing_game_id = ? AND tg.teacher_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$typing_game_id, $teacher_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: manage-games.php");
    exit();
}

// Get existing texts
$query = "SELECT * FROM typing_texts WHERE typing_game_id = ? ORDER BY text_id";
$stmt = $pdo->prepare($query);
$stmt->execute([$typing_game_id]);
$texts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_text'])) {
        $text_content = isset($_POST['text_content']) ? trim($_POST['text_content']) : '';
        
        if (empty($text_content)) {
            $error = 'Please enter some text content';
        } elseif (strlen($text_content) < 50) {
            $error = 'Text content should be at least 50 characters long';
        } elseif (strlen($text_content) > 2000) {
            $error = 'Text content should be less than 2000 characters';
        } else {
            $query = "INSERT INTO typing_texts (typing_game_id, text_content) VALUES (?, ?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$typing_game_id, $text_content]);
            
            $success = 'Typing text added successfully!';
            
            // Refresh texts list
            $stmt = $pdo->prepare("SELECT * FROM typing_texts WHERE typing_game_id = ? ORDER BY text_id");
            $stmt->execute([$typing_game_id]);
            $texts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif (isset($_POST['delete_text'])) {
        $text_id = (int)$_POST['text_id'];
        
        // Verify text belongs to this game
        $stmt = $pdo->prepare("SELECT text_id FROM typing_texts WHERE text_id = ? AND typing_game_id = ?");
        $stmt->execute([$text_id, $typing_game_id]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM typing_texts WHERE text_id = ? AND typing_game_id = ?");
            $stmt->execute([$text_id, $typing_game_id]);
            
            $success = 'Text deleted successfully!';
            
            // Refresh texts list
            $stmt = $pdo->prepare("SELECT * FROM typing_texts WHERE typing_game_id = ? ORDER BY text_id");
            $stmt->execute([$typing_game_id]);
            $texts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif (isset($_POST['publish_game'])) {
        if (count($texts) >= 1) {
            $stmt = $pdo->prepare("UPDATE typing_games SET status = 'active' WHERE typing_game_id = ?");
            $stmt->execute([$typing_game_id]);
            header("Location: manage-games.php?success=Typing game published successfully!");
            exit();
        } else {
            $error = 'You need at least 1 typing text to publish the game';
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
    <title>Manage Typing Texts - <?php echo htmlspecialchars($game['title']); ?></title>
    <link rel="stylesheet" href="../teacher-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #545b62;
            transform: translateY(-2px);
        }
        
        .game-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .game-header h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .game-header h1 i {
            color: #00d4ff;
        }
        
        .game-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .game-info span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .game-info i {
            color: #26890D;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card h2 i {
            color: #00d4ff;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-group textarea {
            width: 100%;
            min-height: 200px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            resize: vertical;
            transition: border-color 0.3s;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #00d4ff;
        }
        
        .char-count {
            text-align: right;
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .char-count.warning {
            color: #ffc107;
        }
        
        .char-count.error {
            color: #dc3545;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 212, 255, 0.3);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .texts-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .text-item {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .text-item:hover {
            border-color: #00d4ff;
        }
        
        .text-number {
            position: absolute;
            top: -10px;
            left: 15px;
            background: #00d4ff;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .text-content {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: #333;
            margin-top: 10px;
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }
        
        .text-content.expanded {
            max-height: none;
        }
        
        .text-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .text-meta-info {
            color: #666;
            font-size: 12px;
        }
        
        .no-texts {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .no-texts i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .publish-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 20px;
            text-align: center;
        }
        
        .publish-section h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .publish-section p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .tips-box {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .tips-box h4 {
            color: #1976d2;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tips-box ul {
            margin: 0;
            padding-left: 20px;
            color: #333;
            font-size: 13px;
        }
        
        .tips-box li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="manage-games.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Manage Games
        </a>
        
        <div class="game-header">
            <h1>
                <i class="fas fa-keyboard"></i> 
                <?php echo htmlspecialchars($game['title']); ?>
                <span class="status-badge status-<?php echo $game['status']; ?>">
                    <?php echo ucfirst($game['status']); ?>
                </span>
            </h1>
            <div class="game-info">
                <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($game['subject_name']); ?></span>
                <span><i class="fas fa-clock"></i> <?php echo $game['time_limit']; ?> seconds</span>
                <span><i class="fas fa-tachometer-alt"></i> Min WPM: <?php echo $game['min_wpm']; ?></span>
                <span><i class="fas fa-layer-group"></i> Difficulty: <?php echo ucfirst($game['difficulty']); ?></span>
                <span><i class="fas fa-file-alt"></i> <?php echo count($texts); ?> text(s) added</span>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="content-grid">
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> Add New Typing Text</h2>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="text_content">Text Content</label>
                        <textarea 
                            name="text_content" 
                            id="text_content" 
                            placeholder="Enter the text that students will type. Make sure it's challenging but fair for the difficulty level you've selected."
                            required
                        ></textarea>
                        <div class="char-count" id="charCount">0 / 2000 characters</div>
                    </div>
                    
                    <button type="submit" name="add_text" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Text
                    </button>
                </form>
                
                <div class="tips-box">
                    <h4><i class="fas fa-lightbulb"></i> Tips for Good Typing Texts</h4>
                    <ul>
                        <li>Use complete sentences with proper punctuation</li>
                        <li>Include a variety of common words and some challenging ones</li>
                        <li>Keep text length appropriate for the time limit</li>
                        <li>For beginners: 50-100 words; Advanced: 150-250 words</li>
                        <li>Avoid excessive special characters for easier difficulty</li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <h2><i class="fas fa-list"></i> Existing Texts (<?php echo count($texts); ?>)</h2>
                
                <div class="texts-list">
                    <?php if (count($texts) > 0): ?>
                        <?php foreach ($texts as $index => $text): ?>
                            <div class="text-item">
                                <span class="text-number">Text #<?php echo $index + 1; ?></span>
                                <div class="text-content" id="text-<?php echo $text['text_id']; ?>">
                                    <?php echo htmlspecialchars($text['text_content']); ?>
                                </div>
                                <div class="text-meta">
                                    <div class="text-meta-info">
                                        <i class="fas fa-text-width"></i> 
                                        <?php echo strlen($text['text_content']); ?> characters | 
                                        <?php echo str_word_count($text['text_content']); ?> words
                                    </div>
                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this text?');">
                                        <input type="hidden" name="text_id" value="<?php echo $text['text_id']; ?>">
                                        <button type="submit" name="delete_text" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-texts">
                            <i class="fas fa-file-alt"></i>
                            <p>No typing texts added yet.</p>
                            <p>Add at least one text to publish the game.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($game['status'] == 'draft'): ?>
            <div class="publish-section">
                <h3><i class="fas fa-rocket"></i> Ready to Publish?</h3>
                <p>
                    <?php if (count($texts) >= 1): ?>
                        You have <?php echo count($texts); ?> typing text(s). Your game is ready to be published!
                    <?php else: ?>
                        Add at least 1 typing text before publishing the game.
                    <?php endif; ?>
                </p>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="publish_game" class="btn btn-success" <?php echo count($texts) < 1 ? 'disabled' : ''; ?>>
                        <i class="fas fa-check-circle"></i> Publish Game
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Character count
        const textarea = document.getElementById('text_content');
        const charCount = document.getElementById('charCount');
        
        textarea.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length + ' / 2000 characters';
            
            charCount.classList.remove('warning', 'error');
            if (length > 1800) {
                charCount.classList.add('error');
            } else if (length > 1500) {
                charCount.classList.add('warning');
            }
        });
    </script>
</body>
</html>
