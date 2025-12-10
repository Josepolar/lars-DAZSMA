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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject_id = $_POST['subject_id'];
    $difficulty = $_POST['difficulty'];
    $time_limit = intval($_POST['time_limit']);
    $min_wpm = intval($_POST['min_wpm']);
    $show_leaderboard = isset($_POST['show_leaderboard']) ? 1 : 0;
    $due_date_input = $_POST['due_date'] ?? $default_due_date;
    $typing_texts = isset($_POST['typing_texts']) ? $_POST['typing_texts'] : [];
    
    $due_date = null;
    $due_date_error = false;
    
    if (empty($due_date_input)) {
        $error = 'Please set a due date and time for the typing game';
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
    
    // Filter empty texts
    $typing_texts = array_filter($typing_texts, function($text) {
        return !empty(trim($text));
    });
    
    // Validation
    if (empty($title)) {
        $error = 'Game title is required';
    } elseif (empty($subject_id)) {
        $error = 'Please select a subject';
    } elseif (empty($typing_texts)) {
        $error = 'Please add at least one typing text';
    } elseif ($time_limit < 15 || $time_limit > 600) {
        $error = 'Time limit must be between 15 and 600 seconds';
    } elseif (!$due_date_error) {
        // Verify teacher teaches this subject
        $verify = "SELECT 1 FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?";
        $stmt = $pdo->prepare($verify);
        $stmt->execute([$teacher_id, $subject_id]);
        
        if ($stmt->fetch()) {
            try {
                $pdo->beginTransaction();
                
                // Insert typing game
                $query = "INSERT INTO typing_games (subject_id, teacher_id, title, description, difficulty, time_limit, min_wpm, show_leaderboard, due_date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$subject_id, $teacher_id, $title, $description, $difficulty, $time_limit, $min_wpm, $show_leaderboard, $due_date]);
                
                $typing_game_id = $pdo->lastInsertId();
                
                // Insert typing texts
                $textQuery = "INSERT INTO typing_texts (typing_game_id, text_content, text_order) VALUES (?, ?, ?)";
                $textStmt = $pdo->prepare($textQuery);
                
                $order = 1;
                foreach ($typing_texts as $text) {
                    $textStmt->execute([$typing_game_id, trim($text), $order]);
                    $order++;
                }
                
                $pdo->commit();
                
                $success = 'Typing game created successfully!';
                header("Location: manage-games.php?success=typing_created");
                exit();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error creating game: ' . $e->getMessage();
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
    <title>Create Speed Typing Game - LARSS</title>
    <link rel="icon" type="image/png" href="../../assets/tablogo.png">
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
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: white;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header h1::before {
            content: '⌨️';
            font-size: 32px;
        }
        
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .form-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="datetime-local"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.2);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        
        .texts-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
        }
        
        .text-item {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .text-item textarea {
            width: 100%;
            border: none;
            resize: vertical;
            min-height: 80px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .text-item textarea:focus {
            outline: none;
        }
        
        .text-item .char-count {
            position: absolute;
            bottom: 10px;
            right: 15px;
            font-size: 12px;
            color: #999;
        }
        
        .text-item .remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .text-item .remove-btn:hover {
            background: #c82333;
        }
        
        .add-text-btn {
            width: 100%;
            padding: 15px;
            border: 2px dashed #667eea;
            background: transparent;
            color: #667eea;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .add-text-btn:hover {
            background: rgba(102,126,234,0.1);
        }
        
        .sample-texts {
            margin-top: 15px;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 8px;
        }
        
        .sample-texts h4 {
            margin-bottom: 10px;
            color: #1976d2;
            font-size: 14px;
        }
        
        .sample-btn {
            background: #1976d2;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .sample-btn:hover {
            background: #1565c0;
        }
        
        .difficulty-options {
            display: flex;
            gap: 15px;
        }
        
        .difficulty-option {
            flex: 1;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        
        .difficulty-option:hover {
            border-color: #667eea;
        }
        
        .difficulty-option.selected {
            border-color: #667eea;
            background: rgba(102,126,234,0.1);
        }
        
        .difficulty-option input {
            display: none;
        }
        
        .difficulty-option .diff-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .difficulty-option .diff-label {
            font-weight: 600;
            color: #333;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
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
        
        .submit-btn {
            width: 100%;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        .preview-section {
            margin-top: 20px;
            padding: 20px;
            background: #fff3cd;
            border-radius: 8px;
        }
        
        .preview-section h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .preview-text {
            font-family: 'Courier New', monospace;
            font-size: 16px;
            line-height: 1.8;
            color: #333;
            background: white;
            padding: 15px;
            border-radius: 6px;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .difficulty-options {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Create Speed Typing Game</h1>
            <a href="manage-games.php" class="back-btn">← Back to Games</a>
        </div>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="typingGameForm">
                <!-- Basic Info Section -->
                <div class="form-section">
                    <h3 class="section-title">📝 Basic Information</h3>
                    
                    <div class="form-group">
                        <label for="title">Game Title *</label>
                        <input type="text" id="title" name="title" placeholder="Enter a descriptive title for your typing game" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Describe what students will practice in this typing game..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="subject_id">Subject *</label>
                            <select id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['subject_id']; ?>">
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="due_date">Due Date & Time *</label>
                            <input type="datetime-local" id="due_date" name="due_date" value="<?php echo $default_due_date; ?>" required>
                        </div>
                    </div>
                </div>
                
                <!-- Game Settings Section -->
                <div class="form-section">
                    <h3 class="section-title">⚙️ Game Settings</h3>
                    
                    <div class="form-group">
                        <label>Difficulty Level</label>
                        <div class="difficulty-options">
                            <label class="difficulty-option selected">
                                <input type="radio" name="difficulty" value="easy" checked>
                                <div class="diff-icon">🟢</div>
                                <div class="diff-label">Easy</div>
                            </label>
                            <label class="difficulty-option">
                                <input type="radio" name="difficulty" value="medium">
                                <div class="diff-icon">🟡</div>
                                <div class="diff-label">Medium</div>
                            </label>
                            <label class="difficulty-option">
                                <input type="radio" name="difficulty" value="hard">
                                <div class="diff-icon">🔴</div>
                                <div class="diff-label">Hard</div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="time_limit">Time Limit (seconds) *</label>
                            <input type="number" id="time_limit" name="time_limit" value="60" min="15" max="600" required>
                            <small style="color: #666;">Between 15 - 600 seconds</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="min_wpm">Minimum WPM to Pass</label>
                            <input type="number" id="min_wpm" name="min_wpm" value="20" min="0" max="200">
                            <small style="color: #666;">Set to 0 for no minimum requirement</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="show_leaderboard" name="show_leaderboard" checked>
                            <label for="show_leaderboard">Show Leaderboard (display rankings to students)</label>
                        </div>
                    </div>
                </div>
                
                <!-- Typing Texts Section -->
                <div class="form-section">
                    <h3 class="section-title">⌨️ Typing Texts</h3>
                    <p style="color: #666; margin-bottom: 20px;">Add paragraphs or sentences for students to type. One random text will be selected for each game session.</p>
                    
                    <div class="texts-container" id="textsContainer">
                        <div class="text-item">
                            <textarea name="typing_texts[]" placeholder="Enter a paragraph or sentence for students to type..." required></textarea>
                            <span class="char-count">0 characters</span>
                        </div>
                    </div>
                    
                    <button type="button" class="add-text-btn" onclick="addTextItem()">
                        + Add Another Text
                    </button>
                    
                    <div class="sample-texts">
                        <h4>💡 Quick Add Sample Texts:</h4>
                        <button type="button" class="sample-btn" onclick="addSampleText('easy')">Easy Sentences</button>
                        <button type="button" class="sample-btn" onclick="addSampleText('medium')">Medium Paragraph</button>
                        <button type="button" class="sample-btn" onclick="addSampleText('hard')">Hard Paragraph</button>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    🚀 Create Typing Game
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Sample texts for quick add
        const sampleTexts = {
            easy: [
                "The quick brown fox jumps over the lazy dog.",
                "Pack my box with five dozen liquor jugs.",
                "How vexingly quick daft zebras jump!",
                "The five boxing wizards jump quickly."
            ],
            medium: [
                "Learning to type quickly and accurately is an essential skill in today's digital world. With practice, anyone can improve their typing speed and reduce errors. Regular practice sessions of just 15 minutes a day can lead to significant improvement over time.",
                "Technology has transformed the way we live, work, and communicate. From smartphones to artificial intelligence, innovations continue to shape our daily experiences. Understanding these tools has become crucial for success in many fields."
            ],
            hard: [
                "The phenomenon of metacognition, or thinking about one's own thinking, has garnered significant attention in educational psychology research. Students who develop strong metacognitive skills demonstrate enhanced problem-solving abilities and improved academic performance across multiple disciplines. This self-regulatory capacity enables learners to monitor their comprehension, identify knowledge gaps, and implement appropriate strategies to address deficiencies.",
                "Quantum computing represents a paradigm shift in computational technology, leveraging the principles of quantum mechanics to process information in fundamentally different ways than classical computers. Unlike traditional bits that exist in binary states, quantum bits or qubits can exist in superposition, enabling simultaneous calculation of multiple possibilities."
            ]
        };
        
        // Add new text item
        function addTextItem() {
            const container = document.getElementById('textsContainer');
            const textItem = document.createElement('div');
            textItem.className = 'text-item';
            textItem.innerHTML = `
                <button type="button" class="remove-btn" onclick="removeTextItem(this)">×</button>
                <textarea name="typing_texts[]" placeholder="Enter a paragraph or sentence for students to type..."></textarea>
                <span class="char-count">0 characters</span>
            `;
            container.appendChild(textItem);
            
            // Add event listener for character count
            const textarea = textItem.querySelector('textarea');
            textarea.addEventListener('input', updateCharCount);
            textarea.focus();
        }
        
        // Remove text item
        function removeTextItem(btn) {
            const textItems = document.querySelectorAll('.text-item');
            if (textItems.length > 1) {
                btn.closest('.text-item').remove();
            } else {
                alert('You must have at least one typing text.');
            }
        }
        
        // Add sample text
        function addSampleText(difficulty) {
            const texts = sampleTexts[difficulty];
            const randomText = texts[Math.floor(Math.random() * texts.length)];
            
            // Find an empty textarea or add new one
            const textareas = document.querySelectorAll('.text-item textarea');
            let emptyTextarea = null;
            
            for (let ta of textareas) {
                if (ta.value.trim() === '') {
                    emptyTextarea = ta;
                    break;
                }
            }
            
            if (emptyTextarea) {
                emptyTextarea.value = randomText;
                emptyTextarea.dispatchEvent(new Event('input'));
            } else {
                addTextItem();
                const newTextarea = document.querySelector('.text-item:last-child textarea');
                newTextarea.value = randomText;
                newTextarea.dispatchEvent(new Event('input'));
            }
        }
        
        // Update character count
        function updateCharCount(e) {
            const textarea = e.target;
            const countSpan = textarea.closest('.text-item').querySelector('.char-count');
            countSpan.textContent = textarea.value.length + ' characters';
        }
        
        // Difficulty selection
        document.querySelectorAll('.difficulty-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.difficulty-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
            });
        });
        
        // Initialize character counts
        document.querySelectorAll('.text-item textarea').forEach(textarea => {
            textarea.addEventListener('input', updateCharCount);
        });
        
        // Form validation
        document.getElementById('typingGameForm').addEventListener('submit', function(e) {
            const textareas = document.querySelectorAll('.text-item textarea');
            let hasContent = false;
            
            textareas.forEach(ta => {
                if (ta.value.trim() !== '') {
                    hasContent = true;
                }
            });
            
            if (!hasContent) {
                e.preventDefault();
                alert('Please add at least one typing text.');
            }
        });
    </script>
</body>
</html>
