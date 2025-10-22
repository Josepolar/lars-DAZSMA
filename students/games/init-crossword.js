function initCrosswordGame(activity, container) {
    // Clear previous content
    container.innerHTML = '';
    
    // Create main game container
    const gameContainer = document.createElement('div');
    gameContainer.className = 'crossword-container';
    
    try {
        // Parse game content
        const gameContent = typeof activity.content === 'string' 
            ? JSON.parse(activity.content) 
            : activity.content;
            
        // Create grid container
        const gridContainer = document.createElement('div');
        gridContainer.className = 'crossword-grid';
        
        // Create clues container
        const cluesContainer = document.createElement('div');
        cluesContainer.className = 'clues-container';
        
        // Add containers to main game container
        gameContainer.appendChild(gridContainer);
        gameContainer.appendChild(cluesContainer);
        
        // Initialize the crossword
        const crossword = new CrosswordGame(gameContainer, {
            ...activity,
            gameContent: gameContent
        });
        
        // Initialize the game
        crossword.init();
        
        // Add the game container to the main container
        container.appendChild(gameContainer);
        
        // Add event listener for submission
        const submitButton = document.createElement('button');
        submitButton.textContent = 'Submit Answers';
        submitButton.className = 'submit-button';
        submitButton.onclick = () => crossword.checkAnswers();
        
        container.appendChild(submitButton);
    } catch (error) {
        console.error('Error initializing crossword:', error);
        container.innerHTML = '<div class="error-message">Error loading crossword puzzle. Please try again later.</div>';
    }
}