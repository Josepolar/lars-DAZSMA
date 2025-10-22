class FlashcardsGame extends GameActivity {
    constructor(container, activity) {
        super(container, activity);
        this.cards = [];
        this.currentIndex = 0;
        this.isShowingAnswer = false;
        this.correctCount = 0;
        this.totalAttempts = 0;
    }

    initFlashcards() {
        this.cards = this.gameContent.cards;
        this.createFlashcardContainer();
        this.createControls();
        this.showCurrentCard();
    }

    createFlashcardContainer() {
        const container = document.createElement('div');
        container.className = 'flashcard-container';

        const card = document.createElement('div');
        card.className = 'flashcard';
        card.addEventListener('click', () => this.flipCard());

        const front = document.createElement('div');
        front.className = 'flashcard-front';

        const back = document.createElement('div');
        back.className = 'flashcard-back';

        card.appendChild(front);
        card.appendChild(back);
        container.appendChild(card);
        this.container.appendChild(container);

        this.cardElement = card;
        this.frontElement = front;
        this.backElement = back;
    }

    createControls() {
        const controls = document.createElement('div');
        controls.className = 'flashcard-controls';

        // Previous button
        const prevButton = document.createElement('button');
        prevButton.innerHTML = '&larr; Previous';
        prevButton.addEventListener('click', () => this.previousCard());

        // Next button
        const nextButton = document.createElement('button');
        nextButton.innerHTML = 'Next &rarr;';
        nextButton.addEventListener('click', () => this.nextCard());

        // Response buttons (shown when card is flipped)
        const responseButtons = document.createElement('div');
        responseButtons.className = 'response-buttons';
        
        const correctButton = document.createElement('button');
        correctButton.className = 'correct-btn';
        correctButton.textContent = 'I got it right';
        correctButton.addEventListener('click', () => this.handleResponse(true));

        const incorrectButton = document.createElement('button');
        incorrectButton.className = 'incorrect-btn';
        incorrectButton.textContent = 'I got it wrong';
        incorrectButton.addEventListener('click', () => this.handleResponse(false));

        responseButtons.appendChild(correctButton);
        responseButtons.appendChild(incorrectButton);
        this.responseButtons = responseButtons;
        responseButtons.style.display = 'none';

        controls.appendChild(prevButton);
        controls.appendChild(responseButtons);
        controls.appendChild(nextButton);

        this.container.appendChild(controls);
    }

    showCurrentCard() {
        const card = this.cards[this.currentIndex];
        this.frontElement.textContent = card.question;
        this.backElement.textContent = card.answer;
        this.cardElement.classList.remove('flipped');
        this.isShowingAnswer = false;
        this.responseButtons.style.display = 'none';

        // Update progress display
        this.updateProgress();
    }

    flipCard() {
        this.isShowingAnswer = !this.isShowingAnswer;
        this.cardElement.classList.toggle('flipped');
        
        if (this.isShowingAnswer) {
            this.responseButtons.style.display = 'flex';
        } else {
            this.responseButtons.style.display = 'none';
        }
    }

    previousCard() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.showCurrentCard();
        }
    }

    nextCard() {
        if (this.currentIndex < this.cards.length - 1) {
            this.currentIndex++;
            this.showCurrentCard();
        } else {
            this.checkCompletion();
        }
    }

    handleResponse(isCorrect) {
        this.totalAttempts++;
        if (isCorrect) {
            this.correctCount++;
        }
        this.nextCard();
    }

    updateProgress() {
        const progress = document.createElement('div');
        progress.className = 'flashcard-progress';
        progress.textContent = `Card ${this.currentIndex + 1} of ${this.cards.length}`;
        
        // Remove existing progress element if it exists
        const existingProgress = this.container.querySelector('.flashcard-progress');
        if (existingProgress) {
            existingProgress.remove();
        }
        
        this.container.insertBefore(progress, this.container.firstChild);
    }

    checkCompletion() {
        if (this.totalAttempts >= this.cards.length) {
            const accuracy = (this.correctCount / this.totalAttempts) * 100;
            const endTime = new Date();
            const timeTaken = (endTime - this.startTime) / 1000;

            const results = {
                score: Math.round(accuracy),
                timeTaken,
                accuracy: Math.round(accuracy),
                totalCards: this.cards.length,
                correctCards: this.correctCount
            };

            this.handleCompletion(results);
        }
    }

    handleCompletion(results) {
        clearInterval(this.timer);
        this.submitActivity(results);
    }

    getCurrentProgress() {
        return {
            currentIndex: this.currentIndex,
            correctCount: this.correctCount,
            totalAttempts: this.totalAttempts,
            timeTaken: (new Date() - this.startTime) / 1000
        };
    }
}