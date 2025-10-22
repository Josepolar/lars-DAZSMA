<?php
session_start();

// Redirect to login if session is missing or expired
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    header('Location: stud-login.php');
    exit();
}

// Get activity ID from URL
$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$activity_id) {
    header('Location: student-activities.php');
    exit();
}

// Get activity details
require_once('../Database/database.php');
$stmt = $conn->prepare("SELECT title, activity_type FROM activities WHERE activity_id = ?");
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();

if (!$activity) {
    header('Location: student-activities.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($activity['title']); ?> - LARS</title>
    <link rel="stylesheet" href="student-activities.css">
    <link rel="stylesheet" href="game-activities.css">
    <link rel="stylesheet" href="games/game-activities-new.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .activity-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .activity-title {
            font-size: 24px;
            margin: 0;
            color: #333;
        }

        .activity-type {
            background: #e3f2fd;
            padding: 5px 15px;
            border-radius: 20px;
            color: #1976d2;
            font-weight: 500;
        }

        .activity-content {
            min-height: 400px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.1);
        }

        .error-message {
            text-align: center;
            padding: 20px;
            color: #d32f2f;
        }

        .error-message i {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .error-message button {
            margin-top: 15px;
            padding: 8px 20px;
            background: #1976d2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .error-message button:hover {
            background: #1565c0;
        }
    </style>
</head>
<body>
    <div id="securityWarning" class="security-warning" style="display: none;">
        <div class="warning-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="warning-text">
            <h3>⚠️ Security Warning ⚠️</h3>
            <p>Tab switching detected! Activity will be auto-submitted after 3 warnings.</p>
            <div class="warning-counter">Warnings: <span id="warningCount">0</span>/3</div>
        </div>
    </div>

    <div class="timer-bar">
        <div id="timerProgress" class="timer-progress"></div>
    </div>

    <div class="activity-container">
        <div class="activity-header">
            <div class="header-left">
                <h1 class="activity-title" id="activityTitle">Loading activity...</h1>
                <div class="security-status">
                    <i class="fas fa-shield-alt"></i> Secure Mode Active
                </div>
            </div>
            <div class="header-right">
                <div class="time-remaining" id="timeRemaining"></div>
                <div class="warning-indicator" id="warningIndicator"></div>
            </div>
        </div>
        
        <div id="activityContent" class="activity-content">
            <!-- Activity content will be loaded here -->
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>⚠️ Warning</h2>
            <p>Are you sure you want to submit? This action cannot be undone.</p>
            <div class="modal-buttons">
                <button onclick="confirmSubmit()" class="btn-submit">Yes, Submit</button>
                <button onclick="closeConfirmModal()" class="btn-cancel">Continue Activity</button>
            </div>
        </div>
    </div>

    <!-- Game Scripts -->
    <script>
        // Global namespace for activity variables
        window.Activity = {
            data: null,
            timeLeft: 0,
            totalTime: 0,
            tabSwitches: 0,
            isSubmitting: false,
            maxTabSwitches: 3,
            timer: null
        };
    </script>
    <script src="games/crossword-game.js"></script>
    <script src="games/flashcard-game.js"></script>
    <script src="games/speed-typing-game.js"></script>
    <script src="secure-activity-handler.js"></script>
    <script>

        // Handle visibility change for tab switching
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && !Activity.isSubmitting) {
                Activity.tabSwitches++;
                document.getElementById('securityWarning').style.display = 'block';
                document.getElementById('warningCount').textContent = Activity.tabSwitches;
                
                if (Activity.tabSwitches >= Activity.maxTabSwitches) {
                    submitActivity({ 
                        auto_submitted: true, 
                        reason: 'Too many tab switches',
                        switches: Activity.tabSwitches
                    });
                }
                
                setTimeout(() => {
                    document.getElementById('securityWarning').style.display = 'none';
                }, 3000);
            }
        });

        // Request fullscreen mode
        function requestFullscreen() {
            const element = document.documentElement;
            if (element.requestFullscreen) {
                element.requestFullscreen();
            } else if (element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
            }
        }

        // Start timer function
        function startTimer() {
            updateTimerDisplay();
            Activity.timer = setInterval(() => {
                Activity.timeLeft--;
                updateTimerDisplay();
                
                if (Activity.timeLeft <= 0) {
                    clearInterval(Activity.timer);
                    submitActivity({ 
                        auto_submitted: true, 
                        reason: 'Time expired'
                    });
                }
            }, 1000);
        }

        // Update timer display
        function updateTimerDisplay() {
            const minutes = Math.floor(Activity.timeLeft / 60);
            const seconds = Activity.timeLeft % 60;
            document.getElementById('timeRemaining').textContent = 
                `Time Remaining: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            const percentage = (Activity.timeLeft / Activity.totalTime) * 100;
            document.getElementById('timerProgress').style.width = `${percentage}%`;
        }

        // Load activity
        async function loadActivity() {
            try {
                const activityId = <?php echo $activity_id; ?>;
                const response = await fetch(`../api/student_game_activities.php?activity_id=${activityId}`);
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                Activity.data = data.activity;
                document.getElementById('activityTitle').textContent = Activity.data.title;
                
                if (Activity.data.time_limit) {
                    Activity.timeLeft = Activity.totalTime = Activity.data.time_limit * 60;
                    startTimer();
                }

                // Initialize game content
                if (!Activity.data.game_content) {
                    throw new Error('No game content available for this activity');
                }

                // Start the activity
                const startResponse = await fetch(`../api/student_activities.php?action=start&id=${activityId}`);
                const startData = await startResponse.json();
                
                if (startData.error) {
                    throw new Error(startData.error);
                }

                activityData.questions = startData.questions || [];
                
                // Initialize based on activity type
                switch (Activity.data.type.toLowerCase()) {
                    case 'crossword':
                        const crosswordGame = new CrosswordGame(document.getElementById('activityContent'), Activity.data);
                        crosswordGame.initialize(Activity.data.game_content);
                        break;
                    case 'flashcards':
                        const flashcardsGame = new FlashcardsGame(document.getElementById('activityContent'), Activity.data);
                        flashcardsGame.initialize(Activity.data.game_content);
                        break;
                    case 'speed_typing':
                        const speedTypingGame = new SpeedTypingGame(document.getElementById('activityContent'), Activity.data);
                        speedTypingGame.initialize(Activity.data.game_content);
                        break;
                    default:
                        throw new Error(`Unsupported game type: ${Activity.data.type}`);
                }

                requestFullscreen();
                
            } catch (error) {
                console.error('Error loading activity:', error);
                document.getElementById('activityContent').innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Failed to load activity: ${error.message}</p>
                        <button onclick="window.location.reload()">Try Again</button>
                    </div>
                `;
            }
        }

        // Submit activity
        async function submitActivity(additionalData = {}) {
            if (Activity.isSubmitting) return;
            Activity.isSubmitting = true;

            try {
                const response = await fetch('../api/student_activities.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'submit',
                        activity_id: <?php echo $activity_id; ?>,
                        tab_switches: Activity.tabSwitches,
                        time_spent: Activity.totalTime - Activity.timeLeft,
                        ...additionalData
                    })
                });

                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }

                window.location.href = 'student-activities.php?submitted=true';
                
            } catch (error) {
                console.error('Error submitting activity:', error);
                alert('Failed to submit activity. Please try again.');
                Activity.isSubmitting = false;
            }
        }

        // Modal functions
        function showConfirmModal() {
            document.getElementById('confirmModal').style.display = 'block';
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }

        function confirmSubmit() {
            submitActivity();
        }

        // Handle beforeunload
        window.addEventListener('beforeunload', function(e) {
            if (!Activity.isSubmitting && Activity.timeLeft > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Initialize on load
        document.addEventListener('DOMContentLoaded', loadActivity);
    </script>
    <style>
        /* Activity Styles */
        .activity-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .activity-title {
            font-size: 24px;
            margin: 0;
            color: #333;
        }

        .activity-content {
            min-height: 400px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.1);
        }

        /* Security Warning Styles */
        .security-warning {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff5252;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .warning-icon {
            font-size: 24px;
        }

        .timer-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #eee;
        }

        .timer-progress {
            height: 100%;
            background: #4CAF50;
            transition: width 1s linear;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
        }

        .modal-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .btn-submit, .btn-cancel {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-submit {
            background: #4CAF50;
            color: white;
        }

        .btn-cancel {
            background: #f44336;
            color: white;
        }

        .error-message {
            text-align: center;
            padding: 20px;
            color: #d32f2f;
        }

        .error-message i {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>
</body>
</html>
                    Tab Switches: <span id="switchCount">0</span>/3
                </div>
            </div>
        </div>

        <div class="content-wrapper">
            <div id="activityContent" class="activity-content">
                <!-- Activity content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h2>⚠️ Warning</h2>
            <p>Are you sure you want to submit? This action cannot be undone.</p>
            <div class="modal-buttons">
                <button onclick="confirmSubmit()" class="btn-submit">Yes, Submit</button>
                <button onclick="closeConfirmModal()" class="btn-cancel">Continue Activity</button>
            </div>
        </div>
    </div>

    <script>
        let activityData = null;
        let timeLeft = 0;
        let totalTime = 0;
        let tabSwitchCount = 0;
        const maxTabSwitches = 3;
        let timer = null;
        let isSubmitting = false;

        // Handle visibility change
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && !isSubmitting) {
                tabSwitchCount++;
                document.getElementById('tabWarning').style.display = 'block';
                
                if (tabSwitchCount >= maxTabSwitches) {
                    submitActivity({ 
                        auto_submitted: true, 
                        reason: 'Too many tab switches',
                        switches: tabSwitchCount
                    });
                }
                
                // Hide warning after 3 seconds
                setTimeout(() => {
                    document.getElementById('tabWarning').style.display = 'none';
                }, 3000);
            }
        });

        // Request fullscreen
        function requestFullscreen() {
            const element = document.documentElement;
            if (element.requestFullscreen) {
                element.requestFullscreen();
            } else if (element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
            }
        }

        // Load activity
        async function loadActivity() {
            try {
                // First request to get activity details
                const response = await fetch(`../api/student_activities.php?action=details&id=<?php echo $activity_id; ?>`);
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                activityData = data.activity;
                document.getElementById('activityTitle').textContent = activityData.title;
                
                // Set up timer if there's a time limit
                if (activityData.time_limit) {
                    timeLeft = totalTime = activityData.time_limit * 60;
                    startTimer();
                }

                // Initialize activity based on type
                const contentArea = document.getElementById('activityContent');
                
                // Second request to start the activity
                const startResponse = await fetch(`../api/student_activities.php?action=start&id=<?php echo $activity_id; ?>`);
                const startData = await startResponse.json();
                
                if (startData.error) {
                    throw new Error(startData.error);
                }

                // Add the questions from the start response
                activityData.questions = startData.questions || [];
                
                // Initialize based on activity type
                if (activityData.activity_type === 'quiz') {
                    initializeQuiz(activityData);
                } else if (['crossword', 'flashcards', 'speed_typing'].includes(activityData.activity_type)) {
                    initializeGame(activityData);
                } else {
                    // Regular activity
                    initializeQuiz(activityData);
                }

                // Request fullscreen
                requestFullscreen();
                
            } catch (error) {
                console.error('Error loading activity:', error);
                alert('Failed to load activity. Returning to activities list...');
                window.location.href = 'student-activities.php';
            }
        }

        // Start timer
        function startTimer() {
            updateTimerDisplay();
            
            timer = setInterval(() => {
                timeLeft--;
                updateTimerDisplay();
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    submitActivity({ 
                        auto_submitted: true, 
                        reason: 'Time expired'
                    });
                }
            }, 1000);
        }

        // Update timer display
        function updateTimerDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timeRemaining').textContent = 
                `Time Remaining: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            // Update progress bar
            const percentage = (timeLeft / totalTime) * 100;
            document.getElementById('timerProgress').style.width = `${percentage}%`;
        }

        // Submit activity
        async function submitActivity(additionalData = {}) {
            if (isSubmitting) return;
            isSubmitting = true;

            try {
                const response = await fetch('../api/student_activities.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'submit',
                        activity_id: <?php echo $activity_id; ?>,
                        tab_switches: tabSwitchCount,
                        time_spent: totalTime - timeLeft,
                        ...additionalData
                    })
                });

                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                // Exit fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }

                // Redirect back to activities page
                window.location.href = 'student-activities.php?submitted=true';
                
            } catch (error) {
                console.error('Error submitting activity:', error);
                alert('Failed to submit activity. Please try again.');
                isSubmitting = false;
            }
        }

        // Handle beforeunload
        window.addEventListener('beforeunload', function(e) {
            if (!isSubmitting && timeLeft > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Initialize on load
        document.addEventListener('DOMContentLoaded', loadActivity);
        // Initialize quiz
        function initializeQuiz(activityData) {
            const contentArea = document.getElementById('activityContent');
            
            try {
                // Ensure we have questions
                if (!activityData.questions || !Array.isArray(activityData.questions)) {
                    throw new Error('No questions available for this activity');
                }

                const questions = activityData.questions;

                // Create quiz interface
                contentArea.innerHTML = `
                    <form id="quizForm" onsubmit="submitQuiz(event)">
                        <div class="questions-container">
                            ${questions.map((question, index) => `
                                <div class="question-card">
                                    <h3>Question ${index + 1}</h3>
                                    <p class="question-text">${question.question_text || question.text}</p>
                                    <div class="options-container">
                                        ${renderQuestionOptions(question, index)}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <div class="quiz-footer">
                            <button type="submit" class="btn-submit">Submit Activity</button>
                        </div>
                    </form>
                `;

                // Show warning count
                document.getElementById('warningCountBox').style.display = 'block';
            } catch (error) {
                console.error('Error initializing quiz:', error);
                contentArea.innerHTML = `
                    <div class="error-message">
                        <p>Error loading activity content. Please try again.</p>
                        <button onclick="window.location.reload()" class="btn-submit">Reload Activity</button>
                    </div>
                `;
            }
        }

        // Render question options based on question type
        function renderQuestionOptions(question, questionIndex) {
            switch (question.question_type) {
                case 'multiple_choice':
                    return question.options.map((option, optionIndex) => `
                        <div class="option">
                            <input type="radio" 
                                id="q${questionIndex}_opt${optionIndex}"
                                name="q${questionIndex}" 
                                value="${option}"
                                required>
                            <label for="q${questionIndex}_opt${optionIndex}">${option}</label>
                        </div>
                    `).join('');
                
                case 'true_false':
                    return `
                        <div class="option">
                            <input type="radio" 
                                id="q${questionIndex}_true"
                                name="q${questionIndex}" 
                                value="true"
                                required>
                            <label for="q${questionIndex}_true">True</label>
                        </div>
                        <div class="option">
                            <input type="radio" 
                                id="q${questionIndex}_false"
                                name="q${questionIndex}" 
                                value="false"
                                required>
                            <label for="q${questionIndex}_false">False</label>
                        </div>
                    `;
                
                case 'short_answer':
                    return `
                        <textarea 
                            name="q${questionIndex}"
                            rows="3"
                            placeholder="Enter your answer here..."
                            required></textarea>
                    `;
                
                default:
                    return `<p class="error">Unsupported question type</p>`;
            }
        }

        // Submit quiz
        async function submitQuiz(event) {
            event.preventDefault();
            if (isSubmitting) return;
            isSubmitting = true;

            try {
                const form = document.getElementById('quizForm');
                const formData = new FormData(form);
                const answers = {};

                // Collect all answers
                formData.forEach((value, key) => {
                    answers[key] = value;
                });

                const response = await fetch('../api/student_activities.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'submit',
                        activity_id: <?php echo $activity_id; ?>,
                        answers: answers,
                        tab_switches: tabSwitchCount,
                        time_spent: totalTime - timeLeft,
                        activity_type: activityData.activity_type || 'quiz'
                    })
                });

                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }

                // Exit fullscreen if we're in it
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }

                alert('Activity submitted successfully!');
                window.location.href = 'student-activities.php?submitted=true';

            } catch (error) {
                console.error('Error submitting activity:', error);
                alert('Failed to submit activity. Please try again.');
                isSubmitting = false;
            }
        }
    </script>
    <script src="game-activities.js"></script>
    <style>
        /* Quiz Styles */
        .questions-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
        }

        .question-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .question-text {
            font-size: 1.1em;
            color: #333;
            margin-bottom: 15px;
        }

        .options-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            cursor: pointer;
        }

        .option:hover {
            background: #f8f9fa;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }

        .quiz-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-submit {
            padding: 10px 30px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
        }

        .btn-submit:hover {
            background: #45a049;
        }
    </style>
</body>
</html>