class CrosswordGame extends GameActivity {
    constructor(container, activity) {
        super(container, activity);
        this.selectedCell = null;
        this.direction = 'across'; // or 'down'
        this.grid = [];
        this.answers = new Map();
    }

    initCrossword() {
        const { words, clues, gridSize } = this.gameContent;
        const [rows, cols] = gridSize.split('x').map(Number);
        
        this.createGrid(rows, cols);
        this.setupClues(clues);
        this.setupKeyboardNavigation();
        
        // Initialize answers map
        words.forEach(word => {
            const key = `${word.startRow}-${word.startCol}-${word.direction}`;
            this.answers.set(key, word.word.toUpperCase());
        });
    }

    createGrid(rows, cols) {
        const gridContainer = document.createElement('div');
        gridContainer.className = 'crossword-grid';
        gridContainer.style.gridTemplateColumns = `repeat(${cols}, 40px)`;

        this.grid = Array(rows).fill().map(() => Array(cols).fill(null));

        for (let i = 0; i < rows; i++) {
            for (let j = 0; j < cols; j++) {
                const cell = this.createCell(i, j);
                this.grid[i][j] = cell;
                gridContainer.appendChild(cell);
            }
        }

        this.container.appendChild(gridContainer);
    }

    createCell(row, col) {
        const cell = document.createElement('div');
        cell.className = 'crossword-cell';
        cell.dataset.row = row;
        cell.dataset.col = col;
        
        const input = document.createElement('input');
        input.type = 'text';
        input.maxLength = 1;
        input.addEventListener('input', (e) => this.handleInput(e, row, col));
        input.addEventListener('click', () => this.selectCell(row, col));
        input.addEventListener('focus', () => this.selectCell(row, col));
        
        cell.appendChild(input);
        return cell;
    }

    setupClues(clues) {
        const cluesContainer = document.createElement('div');
        cluesContainer.className = 'clues-container';
        
        const acrossClues = document.createElement('div');
        acrossClues.className = 'clues-section';
        acrossClues.innerHTML = '<h3>Across</h3>';
        
        const downClues = document.createElement('div');
        downClues.className = 'clues-section';
        downClues.innerHTML = '<h3>Down</h3>';

        clues.forEach(clue => {
            const clueElement = document.createElement('div');
            clueElement.className = 'clue';
            clueElement.textContent = `${clue.number}. ${clue.text}`;
            clueElement.addEventListener('click', () => this.selectClue(clue));
            
            if (clue.direction === 'across') {
                acrossClues.appendChild(clueElement);
            } else {
                downClues.appendChild(clueElement);
            }
        });

        cluesContainer.appendChild(acrossClues);
        cluesContainer.appendChild(downClues);
        this.container.appendChild(cluesContainer);
    }

    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            if (!this.selectedCell) return;
            
            const [row, col] = this.selectedCell;
            
            switch (e.key) {
                case 'ArrowRight':
                    this.selectCell(row, col + 1);
                    break;
                case 'ArrowLeft':
                    this.selectCell(row, col - 1);
                    break;
                case 'ArrowUp':
                    this.selectCell(row - 1, col);
                    break;
                case 'ArrowDown':
                    this.selectCell(row + 1, col);
                    break;
                case 'Tab':
                    e.preventDefault();
                    this.toggleDirection();
                    break;
            }
        });
    }

    selectCell(row, col) {
        // Clear previous selection
        if (this.selectedCell) {
            const [prevRow, prevCol] = this.selectedCell;
            this.grid[prevRow][prevCol].classList.remove('selected');
        }

        // Set new selection
        if (this.grid[row]?.[col]) {
            this.selectedCell = [row, col];
            this.grid[row][col].classList.add('selected');
            this.grid[row][col].querySelector('input').focus();
        }
    }

    handleInput(e, row, col) {
        const input = e.target;
        input.value = input.value.toUpperCase();

        if (input.value) {
            // Move to next cell
            if (this.direction === 'across') {
                this.selectCell(row, col + 1);
            } else {
                this.selectCell(row + 1, col);
            }
        }

        this.checkCompletion();
    }

    toggleDirection() {
        this.direction = this.direction === 'across' ? 'down' : 'across';
    }

    selectClue(clue) {
        this.direction = clue.direction;
        this.selectCell(clue.startRow, clue.startCol);
    }

    checkCompletion() {
        let isComplete = true;
        this.answers.forEach((answer, key) => {
            const [startRow, startCol, direction] = key.split('-');
            const word = this.getEnteredWord(parseInt(startRow), parseInt(startCol), direction);
            if (word !== answer) {
                isComplete = false;
            }
        });

        if (isComplete) {
            this.handleCompletion();
        }
    }

    getEnteredWord(startRow, startCol, direction) {
        let word = '';
        let row = startRow;
        let col = startCol;

        while (this.grid[row]?.[col]) {
            const input = this.grid[row][col].querySelector('input');
            if (!input.value) return '';
            word += input.value;
            
            if (direction === 'across') {
                col++;
            } else {
                row++;
            }
        }

        return word;
    }

    handleCompletion() {
        clearInterval(this.timer);
        const endTime = new Date();
        const timeTaken = (endTime - this.startTime) / 1000;

        const results = {
            score: 100,
            timeTaken,
            accuracy: 100
        };

        this.submitActivity(results);
    }

    getCurrentProgress() {
        const progress = {
            grid: [],
            timeTaken: (new Date() - this.startTime) / 1000
        };

        for (let i = 0; i < this.grid.length; i++) {
            progress.grid[i] = [];
            for (let j = 0; j < this.grid[i].length; j++) {
                progress.grid[i][j] = this.grid[i][j].querySelector('input').value;
            }
        }

        return progress;
    }
}