// Game Activity Handler
class GameActivity {
    constructor(container, activity) {
        this.container = container;
        this.activity = activity;
        this.gameSettings = JSON.parse(activity.game_settings || '{}');
        this.gameContent = JSON.parse(activity.content_data || '{}');
        this.score = 0;
        this.startTime = null;
        this.timer = null;
    }

    initialize() {
        switch (this.activity.activity_type) {
            case 'crossword':
                this.initCrossword();
                break;
            case 'flashcards':
                this.initFlashcards();
                break;
            case 'speed_typing':
                this.initSpeedTyping();
                break;
        }
        this.startTimer();
    }

    startTimer() {
        if (!this.activity.time_limit) return;
        
        this.startTime = new Date();
        const timeLimit = this.activity.time_limit * 60 * 1000; // Convert minutes to milliseconds
        
        const timerDisplay = document.createElement('div');
        timerDisplay.className = 'timer';
        this.container.prepend(timerDisplay);

        this.timer = setInterval(() => {
            const now = new Date();
            const elapsed = now - this.startTime;
            const remaining = timeLimit - elapsed;

            if (remaining <= 0) {
                clearInterval(this.timer);
                this.handleTimeUp();
            } else {
                const minutes = Math.floor(remaining / 60000);
                const seconds = Math.floor((remaining % 60000) / 1000);
                timerDisplay.textContent = `Time Remaining: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    }

    handleTimeUp() {
        this.submitActivity();
    }

    // Crossword Implementation
    initCrossword() {
        const { words, clues, gridSize } = this.gameContent;
        const [rows, cols] = gridSize.split('x').map(Number);
        
        const gridContainer = document.createElement('div');
        gridContainer.className = 'crossword-grid';
        gridContainer.style.gridTemplateColumns = `repeat(${cols}, 40px)`;

        // Create grid cells
        for (let i = 0; i < rows; i++) {
            for (let j = 0; j < cols; j++) {
                const cell = document.createElement('div');
                cell.className = 'crossword-cell';
                cell.dataset.row = i;
                cell.dataset.col = j;
                
                const input = document.createElement('input');
                input.type = 'text';
                input.maxLength = 1;
                input.addEventListener('input', (e) => this.handleCrosswordInput(e, i, j));
                
                cell.appendChild(input);
                gridContainer.appendChild(cell);
            }
        }

        // Add clues
        const cluesContainer = document.createElement('div');
        cluesContainer.className = 'clues-container';
        
        const acrossClues = document.createElement('div');
        acrossClues.className = 'clues-section';
        acrossClues.innerHTML = '<h3>Across</h3>';
        
        const downClues = document.createElement('div');
        downClues.className = 'clues-section';
        downClues.innerHTML = '<h3>Down</h3>';

        // Add clues based on gameContent

        this.container.appendChild(gridContainer);
        cluesContainer.appendChild(acrossClues);
        cluesContainer.appendChild(downClues);
        this.container.appendChild(cluesContainer);
    }

    // Flashcards Implementation
    initFlashcards() {
        const { cards } = this.gameContent;
        let currentCardIndex = 0;

        const flashcardContainer = document.createElement('div');
        flashcardContainer.className = 'flashcard-container';

        const card = document.createElement('div');
        card.className = 'flashcard';
        
        const front = document.createElement('div');
        front.className = 'flashcard-front';
        
        const back = document.createElement('div');
        back.className = 'flashcard-back';

        card.appendChild(front);
        card.appendChild(back);
        flashcardContainer.appendChild(card);

        const updateCard = () => {
            front.textContent = cards[currentCardIndex].front;
            back.textContent = cards[currentCardIndex].back;
        };

        card.addEventListener('click', () => {
            card.classList.toggle('flipped');
        });

        const nextButton = document.createElement('button');
        nextButton.textContent = 'Next Card';
        nextButton.addEventListener('click', () => {
            currentCardIndex = (currentCardIndex + 1) % cards.length;
            updateCard();
            card.classList.remove('flipped');
        });

        updateCard();
        this.container.appendChild(flashcardContainer);
        this.container.appendChild(nextButton);
    }

    // Speed Typing Implementation
    initSpeedTyping() {
        const { text } = this.gameContent;
        
        const typingContainer = document.createElement('div');
        typingContainer.className = 'typing-container';

        const textDisplay = document.createElement('div');
        textDisplay.className = 'typing-text';
        textDisplay.textContent = text;

        const input = document.createElement('textarea');
        input.className = 'typing-input';
        input.placeholder = 'Start typing here...';

        const stats = document.createElement('div');
        stats.className = 'typing-stats';

        typingContainer.appendChild(textDisplay);
        typingContainer.appendChild(input);
        typingContainer.appendChild(stats);

        let startTime;
        let wpm = 0;
        let accuracy = 0;

        input.addEventListener('input', () => {
            if (!startTime) startTime = new Date();

            const timeDiff = (new Date() - startTime) / 1000 / 60; // in minutes
            const wordsTyped = input.value.trim().split(/\s+/).length;
            wpm = Math.round(wordsTyped / timeDiff);

            const correct = [...input.value].filter((char, i) => char === text[i]).length;
            accuracy = Math.round((correct / input.value.length) * 100) || 0;

            stats.innerHTML = `
                <div class="stat-box">
                    <h4>WPM</h4>
                    <div class="value">${wpm}</div>
                </div>
                <div class="stat-box">
                    <h4>Accuracy</h4>
                    <div class="value">${accuracy}%</div>
                </div>
                <div class="stat-box">
                    <h4>Progress</h4>
                    <div class="value">${Math.round((input.value.length / text.length) * 100)}%</div>
                </div>
            `;

            if (input.value === text) {
                this.handleCompletion(wpm, accuracy);
            }
        });

        this.container.appendChild(typingContainer);
    }

    async submitActivity() {
        try {
            const response = await fetch('../api/student_activities.php?action=submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    activity_id: this.activity.activity_id,
                    score: this.score,
                    time_taken: Math.floor((new Date() - this.startTime) / 1000),
                    completion_percentage: 100,
                    game_data: this.gameData
                })
            });

            const result = await response.json();
            if (result.success) {
                alert('Activity completed successfully!');
                window.location.reload();
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Error submitting activity:', error);
            alert('Failed to submit activity. Please try again.');
        }
    }
}

// Initialize game when loading activity
document.addEventListener('DOMContentLoaded', function() {
    const gameContainer = document.querySelector('.game-container');
    if (gameContainer && window.currentActivity) {
        const game = new GameActivity(gameContainer, window.currentActivity);
        game.initialize();
    }
});