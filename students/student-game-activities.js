// Student Game Activities Handler
class StudentGameActivity {
    constructor(activity) {
        this.activity = activity;
        this.container = document.getElementById('activityContent');
        this.loadGameData(activity.activity_id);
        this.score = 0;
        this.timer = null;
        this.startTime = null;
    }

    async loadGameData(activityId) {
        try {
            const response = await fetch(`../api/game-backend.php?action=get_game_data&activity_id=${activityId}`);
            const result = await response.json();
            
            if (result.success) {
                this.gameSettings = result.data.game_settings;
                this.gameContent = result.data.game_content;
                this.initialize();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error loading game data:', error);
            this.showError('Failed to load game data. Please try again.');
        }
    }

    initialize() {
        // Clear existing content
        this.container.innerHTML = '';
        
        // Add game container
        const gameContainer = document.createElement('div');
        gameContainer.className = 'game-container';
        this.container.appendChild(gameContainer);

        // Initialize based on game type
        switch (this.activity.activity_type) {
            case 'crossword':
                this.initCrossword(gameContainer);
                break;
            case 'flashcards':
                this.initFlashcards(gameContainer);
                break;
            case 'speed_typing':
                this.initSpeedTyping(gameContainer);
                break;
        }

        // Start timer if time limit exists
        if (this.activity.time_limit) {
            this.startTimer();
        }
    }

    startTimer() {
        const timerDisplay = document.createElement('div');
        timerDisplay.className = 'timer-display';
        this.container.insertBefore(timerDisplay, this.container.firstChild);

        let timeLeft = this.activity.time_limit * 60; // Convert to seconds
        this.startTime = Date.now();

        this.timer = setInterval(() => {
            const now = Date.now();
            const elapsed = Math.floor((now - this.startTime) / 1000);
            timeLeft = (this.activity.time_limit * 60) - elapsed;

            if (timeLeft <= 0) {
                clearInterval(this.timer);
                this.handleTimeUp();
            } else {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerDisplay.textContent = `Time Remaining: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    }

    initCrossword(container) {
        const content = this.gameContent;
        if (!content || !content.words || !content.words.length) {
            container.innerHTML = '<div class="error-message">No crossword data available</div>';
            return;
        }

        const gridSize = this.gameSettings.grid_size || '15x15';
        const [rows, cols] = gridSize.split('x').map(Number);

        // Create crossword grid
        const gridElement = document.createElement('div');
        gridElement.className = 'crossword-grid';
        gridElement.style.gridTemplate = `repeat(${rows}, 40px) / repeat(${cols}, 40px)`;

        // Create cells
        for (let i = 0; i < rows * cols; i++) {
            const cell = document.createElement('div');
            cell.className = 'crossword-cell';
            const input = document.createElement('input');
            input.type = 'text';
            input.maxLength = 1;
            cell.appendChild(input);
            gridElement.appendChild(cell);
        }

        // Add clues
        const cluesContainer = document.createElement('div');
        cluesContainer.className = 'clues-container';
        cluesContainer.innerHTML = `
            <div class="clues-across">
                <h3>Across</h3>
                <ul>
                    ${content.words.map((word, i) => `
                        <li>${i + 1}. ${word.clue}</li>
                    `).join('')}
                </ul>
            </div>
        `;

        container.appendChild(gridElement);
        container.appendChild(cluesContainer);
    }

    initFlashcards(container) {
        const content = this.gameContent;
        if (!content || !content.cards || !content.cards.length) {
            container.innerHTML = '<div class="error-message">No flashcard data available</div>';
            return;
        }

        let currentCard = 0;
        const flashcardElement = document.createElement('div');
        flashcardElement.className = 'flashcard-container';
        
        const updateCard = () => {
            flashcardElement.innerHTML = `
                <div class="flashcard">
                    <div class="flashcard-front">${content.cards[currentCard].front}</div>
                    <div class="flashcard-back">${content.cards[currentCard].back}</div>
                </div>
                <div class="flashcard-controls">
                    <button onclick="prevCard()">Previous</button>
                    <span>${currentCard + 1} / ${content.cards.length}</span>
                    <button onclick="nextCard()">Next</button>
                </div>
            `;
        };

        window.nextCard = () => {
            currentCard = (currentCard + 1) % content.cards.length;
            updateCard();
        };

        window.prevCard = () => {
            currentCard = (currentCard - 1 + content.cards.length) % content.cards.length;
            updateCard();
        };

        updateCard();
        container.appendChild(flashcardElement);
    }

    initSpeedTyping(container) {
        const content = this.gameContent;
        if (!content || !content.text) {
            container.innerHTML = '<div class="error-message">No typing content available</div>';
            return;
        }

        container.innerHTML = `
            <div class="typing-container">
                <div class="typing-text">${content.text}</div>
                <textarea class="typing-input" placeholder="Start typing here..."></textarea>
                <div class="typing-stats">
                    <div class="wpm">WPM: 0</div>
                    <div class="accuracy">Accuracy: 0%</div>
                    <div class="progress">Progress: 0%</div>
                </div>
            </div>
        `;

        const input = container.querySelector('.typing-input');
        const stats = container.querySelector('.typing-stats');
        let startTime = null;

        input.addEventListener('input', () => {
            if (!startTime) startTime = new Date();

            const timeDiff = (new Date() - startTime) / 1000 / 60;
            const wordsTyped = input.value.trim().split(/\s+/).length;
            const wpm = Math.round(wordsTyped / timeDiff);

            const correct = [...input.value].filter((char, i) => char === content.text[i]).length;
            const accuracy = Math.round((correct / input.value.length) * 100) || 0;
            const progress = Math.round((input.value.length / content.text.length) * 100);

            stats.innerHTML = `
                <div class="wpm">WPM: ${wpm}</div>
                <div class="accuracy">Accuracy: ${accuracy}%</div>
                <div class="progress">Progress: ${progress}%</div>
            `;

            if (input.value === content.text) {
                this.handleGameComplete({
                    wpm,
                    accuracy,
                    time_taken: Math.round(timeDiff * 60)
                });
            }
        });
    }

    handleTimeUp() {
        this.saveProgress({
            status: 'timeout',
            score: this.score,
            time_taken: this.activity.time_limit * 60
        });
    }

    handleGameComplete(stats) {
        this.saveProgress({
            status: 'completed',
            score: this.calculateScore(stats),
            ...stats
        });
    }

    calculateScore(stats) {
        // Calculate score based on game type and performance
        switch (this.activity.activity_type) {
            case 'speed_typing':
                return Math.round((stats.wpm / 100) * (stats.accuracy / 100) * this.activity.total_points);
            default:
                return this.score;
        }
    }

    async saveProgress(data) {
        try {
            const response = await fetch('../api/student_activities.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'save_progress',
                    activity_id: this.activity.activity_id,
                    ...data
                })
            });

            const result = await response.json();
            if (result.success) {
                alert('Activity completed! Score: ' + data.score);
                window.location.reload();
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Error saving progress:', error);
            alert('Failed to save progress. Please try again.');
        }
    }
}