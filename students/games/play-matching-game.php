<?php
session_start();
include '../../Database/database.php';

// Check if user is STUDENT (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: ../stud-login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get matching game ID
if (!isset($_GET['matching_game_id'])) {
    header("Location: available-games.php");
    exit();
}

$matching_game_id = $_GET['matching_game_id'];

// Get game details
$query = "SELECT mg.*, s.subject_name, CONCAT(u.first_name, ' ', u.last_name) as teacher_name
          FROM matching_games mg
          INNER JOIN subjects s ON mg.subject_id = s.subject_id
          INNER JOIN users u ON mg.teacher_id = u.user_id
          WHERE mg.matching_game_id = ? AND mg.status = 'active'
          AND (mg.due_date IS NULL OR mg.due_date >= NOW())";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: available-games.php");
    exit();
}

if (!empty($game['due_date'])) {
    $due_date_obj = new DateTime($game['due_date']);
    if ($due_date_obj <= new DateTime()) {
        header("Location: available-games.php");
        exit();
    }
}

// Get matching pairs
$query = "SELECT * FROM matching_pairs WHERE matching_game_id = ? ORDER BY pair_order";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id]);
$pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($pairs) < 3) {
    die("This game doesn't have enough pairs to play. Please contact your teacher.");
}

// Create session
$query = "INSERT INTO matching_sessions (matching_game_id, student_id, total_pairs) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id, $student_id, count($pairs)]);
$session_id = $pdo->lastInsertId();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['title']); ?> - Matching Game</title>
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
        
        .game-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .game-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .game-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .game-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .info-item {
            color: #666;
            font-size: 14px;
        }
        
        .timer {
            font-size: 24px;
            font-weight: bold;
            color: #e21b3c;
            padding: 10px 20px;
            background: #fff3cd;
            border-radius: 8px;
        }
        
        .score-display {
            font-size: 20px;
            font-weight: bold;
            color: #26890D;
        }
        
        .matching-board {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .column {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .column-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #26890D;
        }
        
        .item {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            cursor: grab;
            transition: all 0.3s;
            border: 3px solid transparent;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 16px;
            font-weight: 500;
            position: relative;
        }
        
        .item:active {
            cursor: grabbing;
        }
        
        .item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .item.dragging {
            opacity: 0.5;
            cursor: grabbing;
        }
        
        .item.matched {
            background: #d4edda;
            border-color: #28a745;
            cursor: default;
            pointer-events: none;
        }
        
        .item.matched::after {
            content: '✓';
            position: absolute;
            top: 5px;
            right: 10px;
            font-size: 24px;
            color: #28a745;
        }
        
        .item.incorrect {
            background: #f8d7da;
            border-color: #dc3545;
            cursor: default;
            pointer-events: none;
        }
        
        .item.incorrect::after {
            content: '✗';
            position: absolute;
            top: 5px;
            right: 10px;
            font-size: 24px;
            color: #dc3545;
        }
        
        .item img {
            max-width: 100%;
            max-height: 150px;
            border-radius: 8px;
        }
        
        .drop-zone {
            min-height: 80px;
            border: 3px dashed #ccc;
            border-radius: 10px;
            margin-bottom: 15px;
            padding: 20px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .drop-zone.drag-over {
            border-color: #26890D;
            background: #e8f5e9;
        }
        
        .drop-zone.has-item {
            border-style: solid;
            border-color: #007bff;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .check-answers-btn {
            background: #26890D;
            color: white;
            padding: 15px 40px;
            border-radius: 8px;
            border: none;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin: 20px auto;
            display: block;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .check-answers-btn:hover {
            background: #1e6a0a;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.3);
        }
        
        .check-answers-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .completion-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .completion-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
            animation: bounce 0.6s;
        }
        
        .completion-card h2 {
            font-size: 36px;
            color: #26890D;
            margin-bottom: 20px;
        }
        
        .completion-stats {
            margin: 30px 0;
            font-size: 18px;
            color: #333;
        }
        
        .completion-stats div {
            margin: 10px 0;
        }
        
        .btn {
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            display: inline-block;
            margin: 5px;
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
        
        .progress-bar {
            background: #e9ecef;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #26890D, #34a853);
            transition: width 0.5s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .matching-board {
                grid-template-columns: 1fr;
            }
            
            .game-title {
                font-size: 20px;
            }
            
            .timer {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="game-container">
        <a href="available-games.php" class="back-btn">← Back to Games</a>
        
        <div class="game-header">
            <div class="game-title">🧩 <?php echo htmlspecialchars($game['title']); ?></div>
            <div class="game-info">
                <span class="info-item">📚 <?php echo htmlspecialchars($game['subject_name']); ?></span>
                <span class="info-item">👨‍🏫 <?php echo htmlspecialchars($game['teacher_name']); ?></span>
                <?php if (!empty($game['due_date'])): ?>
                    <span class="info-item">📅 Due: <?php echo date('M d, Y g:i A', strtotime($game['due_date'])); ?></span>
                <?php endif; ?>
                <span class="timer" id="timer">Time: <span id="time-display"><?php echo $game['time_limit']; ?></span>s</span>
                <span class="score-display">Score: <span id="score">0</span></span>
            </div>
            
            <div class="progress-bar">
                <div class="progress-fill" id="progress">0 / <?php echo count($pairs); ?> Placed</div>
            </div>
        </div>
        
        <button class="check-answers-btn" id="check-btn" onclick="checkAnswers()" disabled>Check My Answers</button>
        
        <div class="matching-board">
            <div class="column">
                <div class="column-title">Match These</div>
                <div id="left-column">
                    <?php 
                    $shuffled_pairs = $pairs;
                    shuffle($shuffled_pairs);
                    foreach ($shuffled_pairs as $pair): 
                    ?>
                        <div class="item" draggable="true" data-pair-id="<?php echo $pair['pair_id']; ?>">
                            <?php if ($pair['left_item_image']): ?>
                                <img src="../../<?php echo htmlspecialchars($pair['left_item_image']); ?>" alt="Item">
                            <?php else: ?>
                                <?php echo htmlspecialchars($pair['left_item_text']); ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="column">
                <div class="column-title">With These</div>
                <div id="right-column">
                    <?php 
                    $right_items = $pairs;
                    shuffle($right_items);
                    foreach ($right_items as $pair): 
                    ?>
                        <div class="drop-zone" data-pair-id="<?php echo $pair['pair_id']; ?>">
                            <div class="target-item">
                                <?php if ($pair['right_item_image']): ?>
                                    <img src="../../<?php echo htmlspecialchars($pair['right_item_image']); ?>" alt="Target">
                                <?php else: ?>
                                    <?php echo htmlspecialchars($pair['right_item_text']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="completion-overlay" id="completion-overlay">
        <div class="completion-card">
            <h2>🎉 Congratulations!</h2>
            <div class="completion-stats">
                <div>✅ Matched: <strong id="final-correct">0</strong> / <?php echo count($pairs); ?></div>
                <div>⏱️ Time Taken: <strong id="final-time">0</strong>s</div>
                <div>🏆 Score: <strong id="final-score">0</strong> points</div>
            </div>
            <a href="available-games.php" class="btn btn-primary">Back to Games</a>
           
        </div>
    </div>
    
    <script>
        const sessionId = <?php echo $session_id; ?>;
        const totalPairs = <?php echo count($pairs); ?>;
        const timeLimit = <?php echo $game['time_limit']; ?>;
        const pointsPerPair = <?php echo isset($game['points_per_pair']) ? $game['points_per_pair'] : 100; ?>;
        
        let timeRemaining = timeLimit;
        let placedCount = 0;
        let score = 0;
        let startTime = Date.now();
        let timerInterval;
        let gameChecked = false;
        
        // Start timer
        timerInterval = setInterval(() => {
            timeRemaining--;
            document.getElementById('time-display').textContent = timeRemaining;
            
            if (timeRemaining <= 0) {
                checkAnswers();
            }
        }, 1000);
        
        // Drag and drop functionality
        const items = document.querySelectorAll('.item');
        const dropZones = document.querySelectorAll('.drop-zone');
        
        items.forEach(item => {
            item.addEventListener('dragstart', dragStart);
            item.addEventListener('dragend', dragEnd);
        });
        
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', dragOver);
            zone.addEventListener('dragleave', dragLeave);
            zone.addEventListener('drop', drop);
        });
        
        function dragStart(e) {
            if (gameChecked) return;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.pairId);
        }
        
        function dragEnd(e) {
            this.classList.remove('dragging');
        }
        
        function dragOver(e) {
            if (gameChecked) return;
            e.preventDefault();
            this.classList.add('drag-over');
        }
        
        function dragLeave(e) {
            this.classList.remove('drag-over');
        }
        
        function drop(e) {
            if (gameChecked) return;
            e.preventDefault();
            this.classList.remove('drag-over');
            
            const draggedPairId = e.dataTransfer.getData('text/plain');
            const draggedItem = document.querySelector(`.item[data-pair-id="${draggedPairId}"]`);
            
            // Check if this drop zone already has an item
            const existingItem = this.querySelector('.item');
            if (existingItem) {
                // Swap items - put existing item back to left column
                document.getElementById('left-column').appendChild(existingItem);
                placedCount--;
            }
            
            // Place the dragged item in this drop zone
            this.appendChild(draggedItem);
            this.classList.add('has-item');
            placedCount++;
            
            updateProgress();
        }
        
        function updateProgress() {
            const progress = document.getElementById('progress');
            progress.textContent = `${placedCount} / ${totalPairs} Placed`;
            progress.style.width = `${(placedCount / totalPairs) * 100}%`;
            
            // Enable check button when all items are placed
            document.getElementById('check-btn').disabled = placedCount < totalPairs;
        }
        
        function checkAnswers() {
            if (gameChecked) return;
            gameChecked = true;
            clearInterval(timerInterval);
            
            let correctCount = 0;
            const responses = [];
            
            // Check each drop zone
            dropZones.forEach(zone => {
                const correctPairId = zone.dataset.pairId;
                const placedItem = zone.querySelector('.item');
                
                if (placedItem) {
                    const placedPairId = placedItem.dataset.pairId;
                    const isCorrect = placedPairId === correctPairId;
                    
                    if (isCorrect) {
                        // Correct match
                        placedItem.classList.add('matched');
                        correctCount++;
                        score += pointsPerPair;
                    } else {
                        // Incorrect match
                        placedItem.classList.add('incorrect');
                    }
                    
                    // Store the response
                    responses.push({
                        pair_id: correctPairId,
                        student_answer: placedPairId,
                        is_correct: isCorrect
                    });
                }
            });
            
            // Calculate final score
            const timeTaken = Math.floor((Date.now() - startTime) / 1000);
            const timeBonus = Math.max(0, (timeLimit - timeTaken) * 10);
            score += timeBonus;
            
            document.getElementById('score').textContent = score;
            
            // Show results after a brief delay
            setTimeout(() => {
                endGame(correctCount, timeTaken, responses);
            }, 2000);
        }
        
        function playSound(type) {
            // Placeholder for sound effects
        }
        
        function endGame(correctCount, timeTaken, responses) {
            const finalScore = score;
            
            // Save results to database
            fetch('save-matching-results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    total_correct: correctCount,
                    time_taken: timeTaken,
                    total_score: finalScore,
                    completed: true,
                    responses: responses
                })
            });
            
            // Show completion overlay
            document.getElementById('final-correct').textContent = correctCount;
            document.getElementById('final-time').textContent = timeTaken;
            document.getElementById('final-score').textContent = finalScore;
            document.getElementById('completion-overlay').style.display = 'flex';
        }
    </script>
</body>
</html>
