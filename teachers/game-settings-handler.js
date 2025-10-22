// Game Settings Handler
document.getElementById('activityType').addEventListener('change', function() {
    const gameSettingsSection = document.getElementById('gameSettingsSection');
    const gameContentSection = document.getElementById('gameContentSection');
    const questionsSection = document.getElementById('questionsSection');
    const type = this.value;

    if (['crossword', 'flashcards', 'speed_typing'].includes(type)) {
        gameSettingsSection.style.display = 'block';
        gameContentSection.style.display = 'block';
        questionsSection.style.display = 'none';
        initializeGameSettings(type);
    } else {
        gameSettingsSection.style.display = 'none';
        gameContentSection.style.display = 'none';
        questionsSection.style.display = 'block';
    }
});

function initializeGameSettings(type) {
    const settingsContainer = document.getElementById('gameSettingsContainer');
    const contentContainer = document.getElementById('gameContentContainer');

    switch (type) {
        case 'crossword':
            settingsContainer.innerHTML = `
                <div class="form-group">
                    <label for="gridSize">Grid Size</label>
                    <select name="grid_size" id="gridSize">
                        <option value="10x10">10x10</option>
                        <option value="15x15" selected>15x15</option>
                        <option value="20x20">20x20</option>
                    </select>
                </div>
            `;
            contentContainer.innerHTML = `
                <div class="word-list">
                    <div class="word-entry-container">
                        <div class="word-entry">
                            <input type="text" name="words[]" placeholder="Enter word">
                            <input type="text" name="clues[]" placeholder="Enter clue">
                            <button type="button" class="btn btn-small" onclick="addWordEntry()">+</button>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'flashcards':
            settingsContainer.innerHTML = `
                <div class="form-group">
                    <label for="reviewMode">Review Mode</label>
                    <select name="review_mode" id="reviewMode">
                        <option value="sequential">Sequential</option>
                        <option value="random">Random</option>
                        <option value="spaced">Spaced Repetition</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cardsPerSession">Cards Per Session</label>
                    <input type="number" name="cards_per_session" id="cardsPerSession" value="10" min="5" max="50">
                </div>
            `;
            contentContainer.innerHTML = `
                <div class="flashcard-list">
                    <div class="card-entry">
                        <textarea name="card_front[]" placeholder="Front of card"></textarea>
                        <textarea name="card_back[]" placeholder="Back of card"></textarea>
                        <button type="button" class="btn btn-small" onclick="addCardEntry()">+</button>
                    </div>
                </div>
            `;
            break;

        case 'speed_typing':
            settingsContainer.innerHTML = `
                <div class="form-group">
                    <label for="minWPM">Minimum WPM Target</label>
                    <input type="number" name="min_wpm" id="minWPM" value="30" min="20" max="100">
                </div>
                <div class="form-group">
                    <label for="difficulty">Difficulty Level</label>
                    <select name="difficulty" id="difficulty">
                        <option value="easy">Easy</option>
                        <option value="medium" selected>Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>
            `;
            contentContainer.innerHTML = `
                <div class="typing-content">
                    <textarea class="typing-text" name="typing_text" rows="10" 
                              placeholder="Enter the text students will need to type..."></textarea>
                </div>
            `;
            break;
    }
}

function addWordEntry() {
    const container = document.querySelector('.word-entry-container');
    const entry = document.createElement('div');
    entry.className = 'word-entry';
    entry.innerHTML = `
        <input type="text" name="words[]" placeholder="Enter word">
        <input type="text" name="clues[]" placeholder="Enter clue">
        <button type="button" class="btn btn-small btn-danger" onclick="removeEntry(this)">-</button>
    `;
    container.appendChild(entry);
}

function addCardEntry() {
    const container = document.querySelector('.flashcard-list');
    const entry = document.createElement('div');
    entry.className = 'card-entry';
    entry.innerHTML = `
        <textarea name="card_front[]" placeholder="Front of card"></textarea>
        <textarea name="card_back[]" placeholder="Back of card"></textarea>
        <button type="button" class="btn btn-small btn-danger" onclick="removeEntry(this)">-</button>
    `;
    container.appendChild(entry);
}

function removeEntry(button) {
    button.closest('.word-entry, .card-entry').remove();
}