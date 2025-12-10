-- Speed Typing Games SQL Schema
-- Run this SQL to add typing game support to LARSS

-- Create typing games table
CREATE TABLE IF NOT EXISTS typing_games (
    typing_game_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    time_limit INT DEFAULT 60 COMMENT 'Time limit in seconds',
    min_wpm INT DEFAULT 0 COMMENT 'Minimum WPM to pass',
    show_leaderboard TINYINT(1) DEFAULT 1,
    status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
    due_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create typing texts table (paragraphs/sentences for typing)
CREATE TABLE IF NOT EXISTS typing_texts (
    text_id INT AUTO_INCREMENT PRIMARY KEY,
    typing_game_id INT NOT NULL,
    text_content TEXT NOT NULL,
    text_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (typing_game_id) REFERENCES typing_games(typing_game_id) ON DELETE CASCADE
);

-- Create typing sessions table (student game sessions)
CREATE TABLE IF NOT EXISTS typing_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    typing_game_id INT NOT NULL,
    student_id INT NOT NULL,
    text_id INT NOT NULL,
    total_characters INT DEFAULT 0,
    correct_characters INT DEFAULT 0,
    wrong_characters INT DEFAULT 0,
    wpm DECIMAL(6,2) DEFAULT 0.00 COMMENT 'Words per minute',
    accuracy DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Accuracy percentage',
    total_score INT DEFAULT 0,
    time_taken INT DEFAULT 0 COMMENT 'Time taken in seconds',
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (typing_game_id) REFERENCES typing_games(typing_game_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (text_id) REFERENCES typing_texts(text_id) ON DELETE CASCADE
);

-- Add indexes for performance
CREATE INDEX idx_typing_games_teacher ON typing_games(teacher_id);
CREATE INDEX idx_typing_games_subject ON typing_games(subject_id);
CREATE INDEX idx_typing_games_status ON typing_games(status);
CREATE INDEX idx_typing_sessions_student ON typing_sessions(student_id);
CREATE INDEX idx_typing_sessions_game ON typing_sessions(typing_game_id);
CREATE INDEX idx_typing_texts_game ON typing_texts(typing_game_id);

-- Sample typing texts (optional - teacher can add their own)
-- INSERT INTO typing_games (subject_id, teacher_id, title, description, difficulty, time_limit) 
-- VALUES (1, 1, 'Basic Typing Practice', 'Practice your typing speed with simple sentences.', 'easy', 60);
