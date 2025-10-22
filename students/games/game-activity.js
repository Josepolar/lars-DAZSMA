class GameActivity {
    constructor(container, activity) {
        this.container = container;
        this.activity = activity;
        this.gameContent = null;
        this.score = 0;
        this.isComplete = false;
    }

    initialize(gameContent) {
        this.gameContent = gameContent;
        this.render();
    }

    render() {
        // This should be implemented by child classes
        throw new Error('render() must be implemented by child classes');
    }

    updateScore(points) {
        this.score += points;
        this.updateScoreDisplay();
    }

    updateScoreDisplay() {
        const scoreDisplay = document.getElementById('gameScore');
        if (scoreDisplay) {
            scoreDisplay.textContent = `Score: ${this.score}/${this.activity.total_points}`;
        }
    }

    complete() {
        this.isComplete = true;
        // Submit the activity
        submitActivity({
            score: this.score,
            completed: true
        });
    }

    createScoreBoard() {
        const scoreBoard = document.createElement('div');
        scoreBoard.className = 'game-scoreboard';
        scoreBoard.innerHTML = `
            <div class="score" id="gameScore">Score: 0/${this.activity.total_points}</div>
            <div class="progress">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
        `;
        return scoreBoard;
    }
}