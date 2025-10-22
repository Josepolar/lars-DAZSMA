// Game Activity Creator JS
document.addEventListener('DOMContentLoaded', function() {
    const activityType = document.getElementById('activity-type');
    const gameSettingsContainer = document.getElementById('game-settings');
    const gameContentContainer = document.getElementById('game-content');

    // Show/hide appropriate settings based on activity type
    activityType.addEventListener('change', function() {
        const type = this.value;
        showGameSettings(type);
        showContentEditor(type);
    });

    function showGameSettings(type) {
        let settingsHTML = '';
        switch(type) {
            case 'crossword':
                settingsHTML = `
                    <div class="settings-group">
                        <label>Grid Size:</label>
                        <select name="grid_size">
                            <option value="10x10">10x10</option>
                            <option value="15x15">15x15</option>
                            <option value="20x20">20x20</option>
                        </select>
                    </div>
                    <div class="settings-group">
                        <label>Time Limit (minutes):</label>
                        <input type="number" name="time_limit" min="5" max="60">
                    </div>
                `;
                break;

            case 'flashcards':
                settingsHTML = `
                    <div class="settings-group">
                        <label>Cards Per Session:</label>
                        <input type="number" name="cards_per_session" min="5" max="50">
                    </div>
                    <div class="settings-group">
                        <label>Review Mode:</label>
                        <select name="review_mode">
                            <option value="spaced">Spaced Repetition</option>
                            <option value="random">Random</option>
                            <option value="sequential">Sequential</option>
                        </select>
                    </div>
                `;
                break;

            case 'speed_typing':
                settingsHTML = `
                    <div class="settings-group">
                        <label>Minimum WPM Target:</label>
                        <input type="number" name="min_wpm" min="20" max="100">
                    </div>
                    <div class="settings-group">
                        <label>Time Limit (minutes):</label>
                        <input type="number" name="time_limit" min="1" max="15">
                    </div>
                    <div class="settings-group">
                        <label>Difficulty:</label>
                        <select name="difficulty">
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                `;
                break;
        }
        gameSettingsContainer.innerHTML = settingsHTML;
    }

    function showContentEditor(type) {
        let editorHTML = '';
        switch(type) {
            case 'crossword':
                editorHTML = `
                    <div class="content-editor crossword-editor">
                        <div class="word-list">
                            <h4>Word List</h4>
                            <div class="word-entry">
                                <input type="text" placeholder="Word" class="word-input">
                                <input type="text" placeholder="Clue" class="clue-input">
                                <button type="button" class="add-word">+</button>
                            </div>
                            <div id="words-container"></div>
                        </div>
                        <div class="crossword-preview">
                            <h4>Preview</h4>
                            <div id="crossword-grid"></div>
                        </div>
                    </div>
                `;
                break;

            case 'flashcards':
                editorHTML = `
                    <div class="content-editor flashcard-editor">
                        <div class="card-creator">
                            <h4>Create Flashcards</h4>
                            <div class="card-entry">
                                <textarea placeholder="Front side" class="front-input"></textarea>
                                <textarea placeholder="Back side" class="back-input"></textarea>
                                <button type="button" class="add-card">Add Card</button>
                            </div>
                        </div>
                        <div class="cards-list">
                            <h4>Cards List</h4>
                            <div id="cards-container"></div>
                        </div>
                    </div>
                `;
                break;

            case 'speed_typing':
                editorHTML = `
                    <div class="content-editor typing-editor">
                        <h4>Typing Exercise Content</h4>
                        <div class="text-entry">
                            <textarea placeholder="Enter the text for typing practice" class="typing-text" rows="10"></textarea>
                        </div>
                        <div class="text-preview">
                            <h4>Preview</h4>
                            <div id="typing-preview"></div>
                        </div>
                    </div>
                `;
                break;
        }
        gameContentContainer.innerHTML = editorHTML;
        initializeContentEditors(type);
    }

    function initializeContentEditors(type) {
        switch(type) {
            case 'crossword':
                initCrosswordEditor();
                break;
            case 'flashcards':
                initFlashcardEditor();
                break;
            case 'speed_typing':
                initTypingEditor();
                break;
        }
    }

    function initCrosswordEditor() {
        const addWordBtn = document.querySelector('.add-word');
        const wordsContainer = document.getElementById('words-container');
        
        addWordBtn.addEventListener('click', function() {
            const wordInput = document.querySelector('.word-input').value;
            const clueInput = document.querySelector('.clue-input').value;
            
            if (wordInput && clueInput) {
                const wordEntry = document.createElement('div');
                wordEntry.className = 'word-item';
                wordEntry.innerHTML = `
                    <span>${wordInput} - ${clueInput}</span>
                    <button type="button" class="remove-word">×</button>
                `;
                wordsContainer.appendChild(wordEntry);
                
                // Clear inputs
                document.querySelector('.word-input').value = '';
                document.querySelector('.clue-input').value = '';
            }
        });
    }

    function initFlashcardEditor() {
        const addCardBtn = document.querySelector('.add-card');
        const cardsContainer = document.getElementById('cards-container');
        
        addCardBtn.addEventListener('click', function() {
            const frontText = document.querySelector('.front-input').value;
            const backText = document.querySelector('.back-input').value;
            
            if (frontText && backText) {
                const cardEntry = document.createElement('div');
                cardEntry.className = 'card-item';
                cardEntry.innerHTML = `
                    <div class="card-preview">
                        <div class="card-front">${frontText}</div>
                        <div class="card-back">${backText}</div>
                    </div>
                    <button type="button" class="remove-card">×</button>
                `;
                cardsContainer.appendChild(cardEntry);
                
                // Clear inputs
                document.querySelector('.front-input').value = '';
                document.querySelector('.back-input').value = '';
            }
        });
    }

    function initTypingEditor() {
        const typingText = document.querySelector('.typing-text');
        const typingPreview = document.getElementById('typing-preview');
        
        typingText.addEventListener('input', function() {
            typingPreview.textContent = this.value;
        });
    }

    // Form submission
    document.getElementById('game-activity-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        // Add game-specific content
        const gameContent = collectGameContent(activityType.value);
        formData.append('content_data', JSON.stringify(gameContent));
        
        fetch('game-activities.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Game activity created successfully!');
                window.location.reload();
            } else {
                alert('Error creating game activity: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the game activity');
        });
    });

    function collectGameContent(type) {
        let content = {};
        switch(type) {
            case 'crossword':
                content.words = Array.from(document.querySelectorAll('.word-item')).map(item => {
                    const [word, clue] = item.querySelector('span').textContent.split(' - ');
                    return { word, clue };
                });
                break;

            case 'flashcards':
                content.cards = Array.from(document.querySelectorAll('.card-item')).map(item => ({
                    front: item.querySelector('.card-front').textContent,
                    back: item.querySelector('.card-back').textContent
                }));
                break;

            case 'speed_typing':
                content.text = document.querySelector('.typing-text').value;
                break;
        }
        return content;
    }
});