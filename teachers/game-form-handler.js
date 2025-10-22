// Game Activity Form Handler
document.getElementById('createActivityForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const activityType = formData.get('activity_type');

    // If it's a game activity, collect game-specific data
    if (['crossword', 'flashcards', 'speed_typing'].includes(activityType)) {
        const gameSettings = {};
        const gameContent = {
            words: [],
            clues: [],
            gridSize: formData.get('grid_size') || '15x15'
        };

        // Collect game data based on type
        switch (activityType) {
            case 'crossword':
                // Get all word-clue pairs
                document.querySelectorAll('.word-entry').forEach(entry => {
                    const word = entry.querySelector('input[name="words[]"]').value;
                    const clue = entry.querySelector('input[name="clues[]"]').value;
                    if (word && clue) {
                        gameContent.words.push(word);
                        gameContent.clues.push(clue);
                    }
                });
                gameSettings.gridSize = formData.get('grid_size') || '15x15';
                break;

            case 'flashcards':
                gameContent.cards = [];
                document.querySelectorAll('.card-entry').forEach(entry => {
                    const front = entry.querySelector('textarea[name="card_front[]"]').value;
                    const back = entry.querySelector('textarea[name="card_back[]"]').value;
                    if (front && back) {
                        gameContent.cards.push({ front, back });
                    }
                });
                gameSettings.reviewMode = formData.get('review_mode') || 'sequential';
                gameSettings.cardsPerSession = formData.get('cards_per_session') || 10;
                break;

            case 'speed_typing':
                gameContent.text = document.querySelector('.typing-text').value;
                gameSettings.minWPM = formData.get('min_wpm') || 30;
                gameSettings.difficulty = formData.get('difficulty') || 'medium';
                break;
        }

        // Add game data to form
        formData.append('game_settings', JSON.stringify(gameSettings));
        formData.append('game_content', JSON.stringify(gameContent));
        formData.append('difficulty_level', formData.get('difficulty') || 'medium');
    }

    try {
        const response = await fetch('teacher-activities-backend.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            alert('Activity created successfully!');
            closeCreateActivityModal();
            loadActivities(); // Refresh the activities list
        } else {
            throw new Error(result.error || 'Failed to create activity');
        }
    } catch (error) {
        console.error('Error creating activity:', error);
        alert('Failed to create activity: ' + error.message);
    }
});