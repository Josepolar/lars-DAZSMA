-- Matching Game System Database Schema
-- Educational matching/puzzle game for children

-- Matching Games (created by TEACHERS only)
CREATE TABLE IF NOT EXISTS matching_games (
    matching_game_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    game_type ENUM('image-to-text', 'text-to-text', 'image-to-image', 'number-to-text') DEFAULT 'image-to-text',
    time_limit INT DEFAULT 300, -- Total time for the game in seconds (5 minutes default)
    show_leaderboard BOOLEAN DEFAULT TRUE,
    status ENUM('draft', 'active', 'completed', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Matching Pairs (the items to be matched)
CREATE TABLE IF NOT EXISTS matching_pairs (
    pair_id INT PRIMARY KEY AUTO_INCREMENT,
    matching_game_id INT NOT NULL,
    left_item_text VARCHAR(255),
    left_item_image VARCHAR(255),
    right_item_text VARCHAR(255) NOT NULL,
    right_item_image VARCHAR(255),
    pair_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (matching_game_id) REFERENCES matching_games(matching_game_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Matching Game Sessions (STUDENTS playing matching games)
CREATE TABLE IF NOT EXISTS matching_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    matching_game_id INT NOT NULL,
    student_id INT NOT NULL,
    total_score INT DEFAULT 0,
    total_correct INT DEFAULT 0,
    total_pairs INT DEFAULT 0,
    time_taken INT DEFAULT 0, -- Time taken in seconds
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (matching_game_id) REFERENCES matching_games(matching_game_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Matching Responses (tracking individual pair matches)
CREATE TABLE IF NOT EXISTS matching_responses (
    response_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    pair_id INT NOT NULL,
    is_correct BOOLEAN,
    attempts INT DEFAULT 1,
    time_taken INT DEFAULT 0,
    points_earned INT DEFAULT 0,
    matched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES matching_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (pair_id) REFERENCES matching_pairs(pair_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX idx_matching_games_teacher ON matching_games(teacher_id);
CREATE INDEX idx_matching_games_subject ON matching_games(subject_id);
CREATE INDEX idx_matching_games_status ON matching_games(status);
CREATE INDEX idx_matching_pairs_game ON matching_pairs(matching_game_id);
CREATE INDEX idx_matching_sessions_student ON matching_sessions(student_id);
CREATE INDEX idx_matching_sessions_game ON matching_sessions(matching_game_id);
CREATE INDEX idx_matching_responses_session ON matching_responses(session_id);
CREATE INDEX idx_matching_responses_pair ON matching_responses(pair_id);
