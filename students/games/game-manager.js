class GameManager {
    static init() {
        this.bindEventListeners();
    }

    static bindEventListeners() {
        // Close button handler
        const closeButton = document.getElementById('closeGame');
        if (closeButton) {
            closeButton.addEventListener('click', () => this.closeGame());
        }

        // ESC key handler
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeGame();
            }
        });
    }

    static startGame(activityId) {
        console.log('Starting game:', activityId);
        const gameContainer = document.getElementById('gameContainer');
        const gameContent = document.getElementById('gameContent');
        
        // Show loading state
        gameContent.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading game...</div>';
        gameContainer.style.display = 'block';

        // Fetch game data
        fetch(`../api/game_activity_handler.php?action=get&id=${activityId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.initializeGame(data.activity);
                } else {
                    throw new Error(data.error || 'Failed to load game');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                gameContent.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <h3>Error Loading Game</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            });
    }

    static initializeGame(activity) {
        console.log('Initializing game:', activity);
        const gameContainer = document.getElementById('gameContainer');
        const gameContent = document.getElementById('gameContent');
        const gameTitle = document.getElementById('gameTitle');
        const gameSubject = document.getElementById('gameSubject');
        const gamePoints = document.getElementById('gamePoints');

        // Set game information
        gameTitle.textContent = activity.title;
        gameSubject.textContent = activity.subject_name;
        gamePoints.textContent = `${activity.total_points} points`;

        // Clear previous content
        gameContent.innerHTML = '';

        try {
            // Parse game content
            const gameData = typeof activity.content === 'string' 
                ? JSON.parse(activity.content) 
                : activity.content;

            // Initialize appropriate game type
            switch (activity.game_type) {
                case 'flashcards':
                    new FlashcardGame(gameContent, {...activity, gameContent: gameData}).init();
                    break;
                case 'crossword':
                    new CrosswordGame(gameContent, {...activity, gameContent: gameData}).init();
                    break;
                case 'speed_typing':
                    new SpeedTypingGame(gameContent, {...activity, gameContent: gameData}).init();
                    break;
                default:
                    throw new Error('Unknown game type: ' + activity.game_type);
            }
        } catch (error) {
            console.error('Error initializing game:', error);
            gameContent.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>Error Initializing Game</h3>
                    <p>${error.message}</p>
                </div>
            `;
        }
    }

    static closeGame() {
        const gameContainer = document.getElementById('gameContainer');
        if (gameContainer && gameContainer.style.display === 'block') {
            gameContainer.style.display = 'none';
        }
    }
}

// Initialize game manager
document.addEventListener('DOMContentLoaded', () => {
    GameManager.init();
    
    // Bind click events for game cards
    document.querySelectorAll('.play-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const activityId = e.target.getAttribute('data-activity-id');
            if (activityId) {
                GameManager.startGame(activityId);
            }
        });
    });
});