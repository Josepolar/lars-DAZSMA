<?php
session_start();

// Redirect to login if session is missing or expired
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    header('Location: stud-login.php');
    exit();
}

// Database connection
include '../Database/database.php';

// Get student info
$stmt = $pdo->prepare("SELECT first_name, last_name, grade_level FROM users WHERE user_id = ? AND role_id = 4");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Games - LARS</title>
    <link rel="stylesheet" href="games/game-activities-new.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="../assets/lars.png" alt="Logo">
        </div>
        <div class="nav-center">
            <h2>Learning Games</h2>
        </div>
        <div class="back-button">
            <a href="student-activities.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Activities
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="games-grid" id="gamesGrid">
            <?php
            // Fetch all active games
            $stmt = $pdo->prepare("
                SELECT 
                    a.activity_id,
                    a.title,
                    a.description,
                    a.total_points,
                    agc.game_type,
                    s.subject_name,
                    COALESCE(ss.submission_status, 'not_started') as status,
                    COALESCE(ss.total_score, 0) as score
                FROM activities a
                JOIN activity_game_content agc ON a.activity_id = agc.activity_id
                JOIN subjects s ON a.subject_id = s.subject_id
                LEFT JOIN student_submissions ss ON a.activity_id = ss.activity_id 
                    AND ss.student_id = ?
                WHERE a.status = 'active'
                ORDER BY a.activity_id
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($games as $game): ?>
                <div class="game-card">
                    <div class="game-icon">
                        <i class="fas <?php 
                            echo match($game['game_type']) {
                                'flashcards' => 'fa-clone',
                                'crossword' => 'fa-th',
                                'speed_typing' => 'fa-keyboard',
                                default => 'fa-gamepad'
                            };
                        ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($game['title']); ?></h3>
                    <p><?php echo htmlspecialchars($game['description']); ?></p>
                    <div class="game-info">
                        <span class="subject"><?php echo htmlspecialchars($game['subject_name']); ?></span>
                        <span class="points"><?php echo $game['total_points']; ?> points</span>
                    </div>
                    <div class="game-status">
                        <?php if ($game['status'] === 'submitted'): ?>
                            <span class="completed">Score: <?php echo $game['score']; ?></span>
                        <?php endif; ?>
                    </div>
                    <button class="play-btn" data-activity-id="<?php echo $game['activity_id']; ?>">
                        <?php echo $game['status'] === 'submitted' ? 'Play Again' : 'Play Game'; ?>
                    </button>
                </div>
            <?php endforeach; ?>

            <?php if (empty($games)): ?>
                <div class="no-games">
                    <i class="fas fa-gamepad"></i>
                    <h3>No games available</h3>
                    <p>Check back later for new games!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Game container that will be shown when a game is selected -->
    <div id="gameContainer" class="game-container" style="display: none;">
        <div class="game-header">
            <h2 id="gameTitle"></h2>
            <div class="game-meta">
                <span id="gameSubject"></span>
                <span id="gamePoints"></span>
            </div>
            <button id="closeGame" class="close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="gameContent" class="game-content">
            <!-- Game content will be loaded here -->
        </div>
        <div id="gameControls" class="game-controls">
            <!-- Game controls will be added here -->
        </div>
            <h2 id="gameTitle">Game Title</h2>
            <div class="game-info">
                <span id="gameSubject"></span>
                <span id="gamePoints"></span>
            </div>
            <button id="closeGame" class="close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="gameContent" class="game-content">
            <!-- Game content will be loaded here -->
        </div>
        <div class="game-footer">
            <div id="gameProgress"></div>
            <div id="gameControls"></div>
        </div>
    </div>

    <!-- Load required scripts -->
    <!-- Load required scripts -->
    <script src="games/script-loader.js"></script>
    <script src="games/game-activity.js"></script>
    <script src="games/flashcard-game.js"></script>
    <script src="games/crossword-game.js"></script>
    <script src="games/speed-typing-game.js"></script>
    <script src="games/game-manager.js"></script>
    <script>
        // Debug logging
        function debugLog(message, data) {
            console.log(`[Debug] ${message}:`, data);
        }

        // Game initialization
        function initGame(gameType, container, activity) {
            debugLog('Initializing game', { gameType, activity });
            
            switch(gameType) {
                case 'crossword':
                    return initCrosswordGame(activity, container);
                case 'flashcards':
                    return initFlashcardGame(activity, container);
                case 'speed_typing':
                    return initSpeedTypingGame(activity, container);
                default:
                    console.error('Unknown game type:', gameType);
            }
        }

        // Load game content
        function loadGame(activity) {
            debugLog('Loading game', activity);
            
            const gameContainer = document.getElementById('gameContainer');
            const gameContent = document.getElementById('gameContent');
            const gameTitle = document.getElementById('gameTitle');
            const gameSubject = document.getElementById('gameSubject');
            const gamePoints = document.getElementById('gamePoints');
            
            // Clear previous content
            gameContent.innerHTML = '';
            
            // Set game information
            gameTitle.textContent = activity.title;
            gameSubject.textContent = activity.subject_name;
            gamePoints.textContent = `${activity.total_points} points`;
            
            // Show game container
            gameContainer.style.display = 'block';
            
            try {
                // Parse game content if it's a string
                const parsedContent = typeof activity.content === 'string' 
                    ? JSON.parse(activity.content) 
                    : activity.content;
                
                activity.content = parsedContent;
                
                // Initialize the appropriate game
                initGame(activity.game_type, gameContent, activity);
                
            } catch (error) {
                console.error('Error loading game:', error);
                gameContent.innerHTML = `
                    <div class="error-message">
                        <h3>Error Loading Game</h3>
                        <p>There was a problem loading the game content. Please try again later.</p>
                        <small>Error details: ${error.message}</small>
                    </div>
                `;
            }
        }

        // Handle game start
        function startGame(activityId) {
            debugLog('Starting game', { activityId });
            
            fetch(`../api/game_activity_handler.php?action=get&id=${activityId}`)
                .then(response => response.json())
                .then(data => {
                    debugLog('Game data received', data);
                    
                    if (data.success && data.activity) {
                        loadGame(data.activity);
                    } else {
                        throw new Error(data.error || 'Failed to load game data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load the game. Please try again later.');
                });
        }

        // Close game handler
        document.getElementById('closeGame').addEventListener('click', () => {
            document.getElementById('gameContainer').style.display = 'none';
        });
    </script>

    <script>
        // Initialize the games view
        document.addEventListener('DOMContentLoaded', () => {
            // Load available games from the server
            fetch('../api/game_activity_handler.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayGames(data.activities);
                    } else {
                        console.error('Error loading games:', data.error);
                    }
                })
                .catch(error => console.error('Error:', error));
        });

        function displayGames(activities) {
            const gamesGrid = document.getElementById('gamesGrid');
            gamesGrid.innerHTML = '';

            activities.forEach(activity => {
                const gameCard = document.createElement('div');
                gameCard.className = 'game-card';
                gameCard.innerHTML = `
                    <div class="game-icon">
                        <i class="fas ${getGameIcon(activity.game_type)}"></i>
                    </div>
                    <h3>${activity.title}</h3>
                    <p>${activity.description}</p>
                    <div class="game-info">
                        <span class="points">${activity.total_points} points</span>
                        <span class="status ${activity.status}">${formatStatus(activity.status)}</span>
                    </div>
                    <button class="play-btn" onclick="startGame(${activity.activity_id})">
                        Play Game
                    </button>
                `;
                gamesGrid.appendChild(gameCard);
            });
        }

        function getGameIcon(gameType) {
            switch(gameType) {
                case 'flashcards':
                    return 'fa-clone';
                case 'crossword':
                    return 'fa-th';
                case 'speed_typing':
                    return 'fa-keyboard';
                default:
                    return 'fa-gamepad';
            }
        }

        function formatStatus(status) {
            return status.replace('_', ' ').replace(/(^\w|\s\w)/g, l => l.toUpperCase());
        }

        function startGame(activityId) {
            fetch(`../api/game_activity_handler.php?action=get&id=${activityId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadGame(data.activity);
                    } else {
                        console.error('Error loading game:', data.error);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function loadGame(activity) {
            const gameContainer = document.getElementById('gameContainer');
            const gameTitle = document.getElementById('gameTitle');
            const gameContent = document.getElementById('gameContent');
            
            gameTitle.textContent = activity.title;
            gameContainer.style.display = 'block';
            
            // Initialize the appropriate game type
            switch(activity.game_type) {
                case 'flashcards':
                    initFlashcardGame(activity, gameContent);
                    break;
                case 'crossword':
                    initCrosswordGame(activity, gameContent);
                    break;
                case 'speed_typing':
                    initSpeedTypingGame(activity, gameContent);
                    break;
            }
        }

        document.getElementById('closeGame').addEventListener('click', () => {
            document.getElementById('gameContainer').style.display = 'none';
        });
    </script>
</body>
</html>