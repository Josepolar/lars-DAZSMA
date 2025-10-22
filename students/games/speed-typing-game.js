class SpeedTypingGame extends GameActivity {
    constructor(container, activity) {
        super(container, activity);
        this.text = '';
        this.userInput = '';
        this.currentIndex = 0;
        this.mistakes = 0;
        this.startTime = null;
        this.wpm = 0;
        this.accuracy = 100;
    }

    initSpeedTyping() {
        this.text = this.gameContent.text;
        this.createGameInterface();
        this.setupEventListeners();
    }

    createGameInterface() {
        // Create game container
        const gameContainer = document.createElement('div');
        gameContainer.className = 'speed-typing-container';

        // Text display area
        const textDisplay = document.createElement('div');
        textDisplay.className = 'text-display';
        this.textDisplay = textDisplay;

        // Input area
        const inputArea = document.createElement('textarea');
        inputArea.className = 'typing-input';
        inputArea.placeholder = 'Start typing here...';
        this.inputArea = inputArea;

        // Stats display
        const statsDisplay = document.createElement('div');
        statsDisplay.className = 'typing-stats';
        statsDisplay.innerHTML = `
            <div class="stat">
                <label>WPM:</label>
                <span class="wpm">0</span>
            </div>
            <div class="stat">
                <label>Accuracy:</label>
                <span class="accuracy">100%</span>
            </div>
            <div class="stat">
                <label>Progress:</label>
                <span class="progress">0%</span>
            </div>
        `;
        this.statsDisplay = statsDisplay;

        // Add elements to container
        gameContainer.appendChild(textDisplay);
        gameContainer.appendChild(inputArea);
        gameContainer.appendChild(statsDisplay);
        this.container.appendChild(gameContainer);

        // Initialize text display
        this.updateTextDisplay();
    }

    setupEventListeners() {
        this.inputArea.addEventListener('input', () => this.handleInput());
        this.inputArea.addEventListener('paste', (e) => e.preventDefault());
    }

    updateTextDisplay() {
        let displayHtml = '';
        const words = this.text.split('');

        words.forEach((char, index) => {
            if (index < this.currentIndex) {
                // Typed characters
                const isCorrect = this.userInput[index] === char;
                displayHtml += `<span class="${isCorrect ? 'correct' : 'incorrect'}">${char}</span>`;
            } else if (index === this.currentIndex) {
                // Current character
                displayHtml += `<span class="current">${char}</span>`;
            } else {
                // Upcoming characters
                displayHtml += `<span>${char}</span>`;
            }
        });

        this.textDisplay.innerHTML = displayHtml;
    }

    handleInput() {
        if (!this.startTime) {
            this.startTime = new Date();
        }

        const input = this.inputArea.value;
        this.userInput = input;
        
        // Count mistakes
        for (let i = 0; i < input.length; i++) {
            if (input[i] !== this.text[i]) {
                this.mistakes++;
            }
        }

        this.currentIndex = input.length;
        this.updateTextDisplay();
        this.updateStats();

        // Check if completed
        if (input.length === this.text.length) {
            this.handleCompletion();
        }
    }

    updateStats() {
        // Calculate WPM
        const timeElapsed = (new Date() - this.startTime) / 1000 / 60; // in minutes
        const wordsTyped = this.userInput.length / 5; // Assume average word length of 5 characters
        this.wpm = Math.round(wordsTyped / timeElapsed);

        // Calculate accuracy
        const totalChars = this.currentIndex;
        const correctChars = totalChars - this.mistakes;
        this.accuracy = Math.round((correctChars / totalChars) * 100) || 100;

        // Calculate progress
        const progress = Math.round((this.currentIndex / this.text.length) * 100);

        // Update display
        this.statsDisplay.querySelector('.wpm').textContent = this.wpm;
        this.statsDisplay.querySelector('.accuracy').textContent = `${this.accuracy}%`;
        this.statsDisplay.querySelector('.progress').textContent = `${progress}%`;
    }

    handleCompletion() {
        const endTime = new Date();
        const timeTaken = (endTime - this.startTime) / 1000;

        const results = {
            score: Math.round((this.wpm * this.accuracy) / 100), // Combined score
            timeTaken,
            accuracy: this.accuracy,
            wpm: this.wpm,
            mistakes: this.mistakes
        };

        this.submitActivity(results);
    }

    getCurrentProgress() {
        return {
            currentIndex: this.currentIndex,
            userInput: this.userInput,
            mistakes: this.mistakes,
            timeTaken: this.startTime ? (new Date() - this.startTime) / 1000 : 0,
            wpm: this.wpm,
            accuracy: this.accuracy
        };
    }
}