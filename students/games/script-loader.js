// Game script loader
window.addEventListener('DOMContentLoaded', function() {
    // Load the base game activity class first
    loadScript('games/game-activity.js')
        .then(() => {
            // Then load the specific game scripts
            return Promise.all([
                loadScript('games/crossword-game.js'),
                loadScript('games/flashcard-game.js'),
                loadScript('games/speed-typing-game.js')
            ]);
        })
        .catch(error => {
            console.error('Error loading game scripts:', error);
            document.getElementById('activityContent').innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Failed to load game resources: ${error.message}</p>
                    <button onclick="window.location.reload()">Try Again</button>
                </div>
            `;
        });
});

// Helper function to load scripts in sequence
function loadScript(src) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
        document.head.appendChild(script);
    });
}