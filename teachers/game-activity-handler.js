// Add game-specific settings when activity type changes
document.getElementById('activityType').addEventListener('change', function() {
    const type = this.value;
    const gameSettingsContainer = document.getElementById('gameSettings');
    
    if (!gameSettingsContainer) {
        // Create the container if it doesn't exist
        const container = document.createElement('div');
        container.id = 'gameSettings';
        container.className = 'form-section game-settings';
        this.closest('.form-section').after(container);
    }
    
    // Show/hide game settings based on activity type
    if (['crossword', 'flashcards', 'speed_typing'].includes(type)) {
        showGameSettings(type);
    } else {
        hideGameSettings();
    }
});

function showGameSettings(type) {
    const container = document.getElementById('gameSettings');
    let settingsHTML = '<h3>Game Settings</h3>';
    
    switch(type) {
        case 'crossword':
            settingsHTML += `
                <div class="form-group">
                    <label for="gridSize">Grid Size</label>
                    <select id="gridSize" name="game_settings[grid_size]">
                        <option value="10x10">10x10</option>
                        <option value="15x15">15x15</option>
                        <option value="20x20">20x20</option>
                    </select>
                </div>
                <div class="form-group word-list">
                    <label>Words and Clues</label>
                    <div class="word-entry">
                        <input type="text" placeholder="Word" name="game_content[words][]">
                        <input type="text" placeholder="Clue" name="game_content[clues][]">
                        <button type="button" onclick="addWordEntry()">+</button>
                    </div>
                </div>`;
            break;
            
        case 'flashcards':
            settingsHTML += `
                <div class="form-group">
                    <label for="cardsPerSession">Cards Per Session</label>
                    <input type="number" id="cardsPerSession" name="game_settings[cards_per_session]" min="5" max="50" value="10">
                </div>
                <div class="form-group">
                    <label for="reviewMode">Review Mode</label>
                    <select id="reviewMode" name="game_settings[review_mode]">
                        <option value="spaced">Spaced Repetition</option>
                        <option value="random">Random</option>
                        <option value="sequential">Sequential</option>
                    </select>
                </div>
                <div class="form-group flashcard-list">
                    <label>Flashcards</label>
                    <div class="card-entry">
                        <textarea placeholder="Front Side" name="game_content[front][]"></textarea>
                        <textarea placeholder="Back Side" name="game_content[back][]"></textarea>
                        <button type="button" onclick="addCardEntry()">+</button>
                    </div>
                </div>`;
            break;
            
        case 'speed_typing':
            settingsHTML += `
                <div class="form-group">
                    <label for="minWPM">Minimum WPM Target</label>
                    <input type="number" id="minWPM" name="game_settings[min_wpm]" min="20" max="100" value="30">
                </div>
                <div class="form-group">
                    <label for="difficulty">Difficulty</label>
                    <select id="difficulty" name="game_settings[difficulty]">
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="typingText">Typing Text</label>
                    <textarea id="typingText" name="game_content[text]" rows="5" 
                              placeholder="Enter the text students will need to type..."></textarea>
                </div>`;
            break;
    }
    
    container.innerHTML = settingsHTML;
}

function hideGameSettings() {
    const container = document.getElementById('gameSettings');
    if (container) {
        container.innerHTML = '';
    }
}

function addWordEntry() {
    const container = document.querySelector('.word-list');
    const newEntry = document.createElement('div');
    newEntry.className = 'word-entry';
    newEntry.innerHTML = `
        <input type="text" placeholder="Word" name="game_content[words][]">
        <input type="text" placeholder="Clue" name="game_content[clues][]">
        <button type="button" onclick="removeEntry(this)">-</button>
    `;
    container.appendChild(newEntry);
}

function addCardEntry() {
    const container = document.querySelector('.flashcard-list');
    const newEntry = document.createElement('div');
    newEntry.className = 'card-entry';
    newEntry.innerHTML = `
        <textarea placeholder="Front Side" name="game_content[front][]"></textarea>
        <textarea placeholder="Back Side" name="game_content[back][]"></textarea>
        <button type="button" onclick="removeEntry(this)">-</button>
    `;
    container.appendChild(newEntry);
}

function removeEntry(button) {
    button.closest('.word-entry, .card-entry').remove();
}

// Update the existing form submission handler to include game content
const existingCreateActivity = window.createActivity;
window.createActivity = function(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('createActivityForm'));
    const activityType = formData.get('activity_type');
    
    // Add game-specific content if it's a game activity
    if (['crossword', 'flashcards', 'speed_typing'].includes(activityType)) {
        const gameSettings = {};
        const gameContent = {};
        
        // Collect all form data
        for (let [key, value] of formData.entries()) {
            if (key.startsWith('game_settings[')) {
                const settingKey = key.match(/\[(.*?)\]/)[1];
                gameSettings[settingKey] = value;
            } else if (key.startsWith('game_content[')) {
                const contentKey = key.match(/\[(.*?)\]/)[1];
                if (!gameContent[contentKey]) {
                    gameContent[contentKey] = [];
                }
                gameContent[contentKey].push(value);
            }
        }
        
        formData.append('game_settings', JSON.stringify(gameSettings));
        formData.append('game_content', JSON.stringify(gameContent));
    }
    
    // Call the original creation function
    return existingCreateActivity(formData);
};