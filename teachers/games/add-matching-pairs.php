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

// Get matching game ID
if (!isset($_GET['matching_game_id'])) {
    header("Location: manage-games.php");
    exit();
}

$matching_game_id = $_GET['matching_game_id'];

// Verify this game belongs to the teacher
$query = "SELECT mg.*, s.subject_name 
          FROM matching_games mg
          INNER JOIN subjects s ON mg.subject_id = s.subject_id
          WHERE mg.matching_game_id = ? AND mg.teacher_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id, $teacher_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header("Location: manage-games.php");
    exit();
}

// Get existing pairs
$query = "SELECT * FROM matching_pairs WHERE matching_game_id = ? ORDER BY pair_order";
$stmt = $pdo->prepare($query);
$stmt->execute([$matching_game_id]);
$pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_pair'])) {
        $left_text = isset($_POST['left_text']) ? trim($_POST['left_text']) : '';
        $right_text = isset($_POST['right_text']) ? trim($_POST['right_text']) : '';
        $pair_order = count($pairs) + 1;
        
        // Handle image uploads
        $left_image = null;
        $right_image = null;
        
        if (isset($_FILES['left_image']) && $_FILES['left_image']['error'] == 0) {
            $left_image = uploadImage($_FILES['left_image'], 'left');
        }
        
        if (isset($_FILES['right_image']) && $_FILES['right_image']['error'] == 0) {
            $right_image = uploadImage($_FILES['right_image'], 'right');
        }
        
        // Validation based on game type
        $valid = true;
        if ($game['game_type'] == 'image-to-text') {
            if (empty($left_image) || empty($right_text)) {
                $error = 'Image to Text matching requires a left image and right text';
                $valid = false;
            }
        } elseif ($game['game_type'] == 'text-to-text') {
            if (empty($left_text) || empty($right_text)) {
                $error = 'Text to Text matching requires both left and right text';
                $valid = false;
            }
        } elseif ($game['game_type'] == 'image-to-image') {
            if (empty($left_image) || empty($right_image)) {
                $error = 'Image to Image matching requires both left and right images';
                $valid = false;
            }
        } elseif ($game['game_type'] == 'number-to-text') {
            if (empty($left_text) || empty($right_text)) {
                $error = 'Number to Text matching requires both left and right text';
                $valid = false;
            }
        }
        
        if ($valid) {
            $query = "INSERT INTO matching_pairs (matching_game_id, left_item_text, left_item_image, right_item_text, right_item_image, pair_order) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$matching_game_id, $left_text, $left_image, $right_text, $right_image, $pair_order]);
            
            $success = 'Matching pair added successfully!';
            
            // Refresh pairs list
            $stmt = $pdo->prepare("SELECT * FROM matching_pairs WHERE matching_game_id = ? ORDER BY pair_order");
            $stmt->execute([$matching_game_id]);
            $pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif (isset($_POST['delete_pair'])) {
        $pair_id = $_POST['pair_id'];
        
        // Get pair info for image deletion
        $stmt = $pdo->prepare("SELECT * FROM matching_pairs WHERE pair_id = ? AND matching_game_id = ?");
        $stmt->execute([$pair_id, $matching_game_id]);
        $pair = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pair) {
            // Delete images if they exist
            if ($pair['left_item_image'] && file_exists($pair['left_item_image'])) {
                unlink($pair['left_item_image']);
            }
            if ($pair['right_item_image'] && file_exists($pair['right_item_image'])) {
                unlink($pair['right_item_image']);
            }
            
            // Delete pair from database
            $stmt = $pdo->prepare("DELETE FROM matching_pairs WHERE pair_id = ? AND matching_game_id = ?");
            $stmt->execute([$pair_id, $matching_game_id]);
            
            // Reorder remaining pairs
            $stmt = $pdo->prepare("SELECT pair_id FROM matching_pairs WHERE matching_game_id = ? ORDER BY pair_order");
            $stmt->execute([$matching_game_id]);
            $remaining = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($remaining as $index => $p) {
                $update = $pdo->prepare("UPDATE matching_pairs SET pair_order = ? WHERE pair_id = ?");
                $update->execute([$index + 1, $p['pair_id']]);
            }
            
            $success = 'Pair deleted successfully!';
            
            // Refresh pairs list
            $stmt = $pdo->prepare("SELECT * FROM matching_pairs WHERE matching_game_id = ? ORDER BY pair_order");
            $stmt->execute([$matching_game_id]);
            $pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif (isset($_POST['publish_game'])) {
        if (count($pairs) >= 4) {
            $stmt = $pdo->prepare("UPDATE matching_games SET status = 'active' WHERE matching_game_id = ?");
            $stmt->execute([$matching_game_id]);
            header("Location: manage-games.php?success=Game published successfully!");
            exit();
        } else {
            $error = 'You need at least 4 matching pairs to publish the game';
        }
    }
}

function uploadImage($file, $prefix) {
    $upload_dir = '../../uploads/matching_games/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return null;
    }
    
    if ($file['size'] > $max_size) {
        return null;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    }
    
    return null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/tablogo.png">
    <title>Add Matching Pairs</title>
    <link rel="stylesheet" href="../../admin/admin-dashboard.css">
    <style>
        .pairs-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .game-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .game-header h1 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .game-info {
            color: #666;
            font-size: 14px;
        }
        
        .game-type-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #26890D;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .add-pair-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .pairs-list-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-height: 600px;
            overflow-y: auto;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
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
            font-size: 14px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
        }
        
        .help-text {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
        
        .pair-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #26890D;
        }
        
        .pair-content {
            display: grid;
            grid-template-columns: 1fr auto 1fr auto;
            gap: 15px;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .pair-side {
            background: white;
            padding: 10px;
            border-radius: 6px;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .pair-side img {
            max-width: 100%;
            max-height: 80px;
            border-radius: 4px;
        }
        
        .pair-arrow {
            font-size: 24px;
            color: #26890D;
        }
        
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .delete-btn:hover {
            background: #c82333;
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
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
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
        
        .publish-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 20px;
            text-align: center;
        }
        
        .pair-count {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .pair-count strong {
            color: #26890D;
            font-size: 24px;
        }
        
        .image-preview {
            max-width: 150px;
            max-height: 100px;
            margin-top: 10px;
            border-radius: 4px;
            display: none;
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }
            
            .pair-content {
                grid-template-columns: 1fr;
            }
            
            .pair-arrow {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="pairs-container">
        <a href="manage-games.php" class="back-btn">← Back to Games</a>
        
        <div class="game-header">
            <h1><?php echo htmlspecialchars($game['title']); ?></h1>
            <div class="game-info">
                <?php echo htmlspecialchars($game['subject_name']); ?>
                <span class="game-type-badge">
                    <?php 
                    $type_labels = [
                        'image-to-text' => '🖼️ Image to Text',
                        'text-to-text' => '📝 Text to Text',
                        'image-to-image' => '🎨 Image to Image',
                        'number-to-text' => '🔢 Number to Text'
                    ];
                    echo $type_labels[$game['game_type']];
                    ?>
                </span>
            </div>
            <?php if (!empty($game['due_date'])): ?>
                <div style="margin-top: 8px; color: #666; font-size: 14px;">
                    <strong>Due:</strong> <?php echo date('M d, Y g:i A', strtotime($game['due_date'])); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="content-wrapper">
            <div class="add-pair-section">
                <div class="section-title">➕ Add New Matching Pair</div>
                
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($game['game_type'] == 'image-to-text'): ?>
                        <div class="form-group">
                            <label>Left Side - Image *</label>
                            <input type="file" name="left_image" accept="image/*" required onchange="previewImage(this, 'left-preview')">
                            <img id="left-preview" class="image-preview">
                            <div class="help-text">Upload an image (JPG, PNG, GIF - Max 5MB)</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Right Side - Text/Word *</label>
                            <input type="text" name="right_text" required placeholder="Enter the matching text">
                            <div class="help-text">Enter the word or text that matches the image</div>
                        </div>
                        
                    <?php elseif ($game['game_type'] == 'text-to-text'): ?>
                        <div class="form-group">
                            <label>Left Side - Text *</label>
                            <input type="text" name="left_text" required placeholder="Enter left side text">
                            <div class="help-text">e.g., Word, Question, or Concept</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Right Side - Text *</label>
                            <input type="text" name="right_text" required placeholder="Enter right side text">
                            <div class="help-text">e.g., Definition, Answer, or Related term</div>
                        </div>
                        
                    <?php elseif ($game['game_type'] == 'image-to-image'): ?>
                        <div class="form-group">
                            <label>Left Side - Image *</label>
                            <input type="file" name="left_image" accept="image/*" required onchange="previewImage(this, 'left-preview')">
                            <img id="left-preview" class="image-preview">
                            <div class="help-text">Upload first image (JPG, PNG, GIF - Max 5MB)</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Right Side - Image *</label>
                            <input type="file" name="right_image" accept="image/*" required onchange="previewImage(this, 'right-preview')">
                            <img id="right-preview" class="image-preview">
                            <div class="help-text">Upload matching image (JPG, PNG, GIF - Max 5MB)</div>
                        </div>
                        
                    <?php elseif ($game['game_type'] == 'number-to-text'): ?>
                        <div class="form-group">
                            <label>Left Side - Number/Problem *</label>
                            <input type="text" name="left_text" required placeholder="Enter number or problem">
                            <div class="help-text">e.g., "5" or "2 + 3"</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Right Side - Word/Answer *</label>
                            <input type="text" name="right_text" required placeholder="Enter word or answer">
                            <div class="help-text">e.g., "five" or "5"</div>
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" name="add_pair" class="btn btn-primary">Add Pair</button>
                </form>
            </div>
            
            <div class="pairs-list-section">
                <div class="section-title">📋 Matching Pairs (<?php echo count($pairs); ?>)</div>
                
                <?php if (count($pairs) > 0): ?>
                    <?php foreach ($pairs as $pair): ?>
                        <div class="pair-item">
                            <div class="pair-content">
                                <div class="pair-side">
                                    <?php if ($pair['left_item_image']): ?>
                                        <img src="<?php echo htmlspecialchars($pair['left_item_image']); ?>" alt="Left item">
                                    <?php else: ?>
                                        <strong><?php echo htmlspecialchars($pair['left_item_text']); ?></strong>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="pair-arrow">↔️</div>
                                
                                <div class="pair-side">
                                    <?php if ($pair['right_item_image']): ?>
                                        <img src="<?php echo htmlspecialchars($pair['right_item_image']); ?>" alt="Right item">
                                    <?php else: ?>
                                        <strong><?php echo htmlspecialchars($pair['right_item_text']); ?></strong>
                                    <?php endif; ?>
                                </div>
                                
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="pair_id" value="<?php echo $pair['pair_id']; ?>">
                                    <button type="submit" name="delete_pair" class="delete-btn" 
                                            onclick="return confirm('Delete this pair?')">🗑️ Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #888; padding: 40px 0;">
                        No pairs added yet. Add at least 4 pairs to publish the game.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="publish-section">
            <div class="pair-count">
                Total Pairs: <strong><?php echo count($pairs); ?></strong> 
                <?php if (count($pairs) < 4): ?>
                    <span style="color: #dc3545; font-size: 14px;">(Minimum 4 required)</span>
                <?php endif; ?>
            </div>
            
            <form method="POST">
                <?php if (count($pairs) >= 4): ?>
                    <button type="submit" name="publish_game" class="btn btn-success">
                        ✅ Publish Game
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary" disabled>
                        Add <?php echo 4 - count($pairs); ?> more pair(s) to publish
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
