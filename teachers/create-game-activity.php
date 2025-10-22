<?php
session_start();
require_once '../Database/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    header('Location: teacher-login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Game Activity</title>
    <link rel="stylesheet" href="teacher-acts.css">
    <link rel="stylesheet" href="game-activity-creator.css">
</head>
<body>
    <?php include 'teacher-header.php'; ?>

    <div class="game-activity-container">
        <h2>Create Game Activity</h2>
        
        <form id="game-activity-form">
            <div class="settings-section">
                <h3>Basic Settings</h3>
                <div class="settings-group">
                    <label for="title">Activity Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="settings-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <div class="settings-group">
                    <label for="activity-type">Game Type</label>
                    <select id="activity-type" name="activity_type" required>
                        <option value="">Select a game type</option>
                        <option value="crossword">Crossword Puzzle</option>
                        <option value="flashcards">Flash Cards</option>
                        <option value="speed_typing">Speed Typing</option>
                    </select>
                </div>

                <div class="settings-group">
                    <label for="subject">Subject</label>
                    <select id="subject" name="subject_id" required>
                        <?php
                        // Fetch subjects taught by the teacher
                        $teacherId = $_SESSION['user_id'];
                        $stmt = $pdo->prepare("
                            SELECT DISTINCT s.subject_id, s.subject_name 
                            FROM subjects s
                            JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
                            WHERE ts.teacher_id = ?
                        ");
                        $stmt->execute([$teacherId]);
                        while ($subject = $stmt->fetch()) {
                            echo "<option value=\"{$subject['subject_id']}\">{$subject['subject_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="settings-group">
                    <label for="total-points">Total Points</label>
                    <input type="number" id="total-points" name="total_points" min="1" max="100" required>
                </div>

                <div class="settings-group">
                    <label for="due-date">Due Date</label>
                    <input type="datetime-local" id="due-date" name="due_date" required>
                </div>
            </div>

            <div class="settings-section">
                <h3>Game Settings</h3>
                <div id="game-settings"></div>
            </div>

            <div class="settings-section">
                <h3>Game Content</h3>
                <div id="game-content"></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="action-button">Create Game Activity</button>
            </div>
        </form>
    </div>

    <script src="game-activity-creator.js"></script>
</body>
</html>