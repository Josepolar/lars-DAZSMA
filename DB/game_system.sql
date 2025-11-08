-- Game System Database Schema
-- Kahoot-like gamified quiz system for LARS

-- Gamified Activities (created by TEACHERS only)
CREATE TABLE IF NOT EXISTS game_activities (
    game_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    time_limit INT DEFAULT 30,
    show_leaderboard BOOLEAN DEFAULT TRUE,
    status ENUM('draft', 'active', 'completed', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Game Questions
CREATE TABLE IF NOT EXISTS game_questions (
    question_id INT PRIMARY KEY AUTO_INCREMENT,
    game_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_order INT NOT NULL,
    time_limit INT DEFAULT 30,
    points INT DEFAULT 1000,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES game_activities(game_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Answer Options
CREATE TABLE IF NOT EXISTS game_options (
    option_id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    option_text VARCHAR(255) NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    option_order INT NOT NULL,
    color_code VARCHAR(20) DEFAULT 'blue',
    FOREIGN KEY (question_id) REFERENCES game_questions(question_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Responses
CREATE TABLE IF NOT EXISTS game_responses (
    response_id INT PRIMARY KEY AUTO_INCREMENT,
    game_id INT NOT NULL,
    student_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option_id INT,
    is_correct BOOLEAN,
    time_taken INT,
    points_earned INT DEFAULT 0,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES game_activities(game_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES game_questions(question_id) ON DELETE CASCADE,
    FOREIGN KEY (selected_option_id) REFERENCES game_options(option_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Game Sessions (STUDENTS playing games)
CREATE TABLE IF NOT EXISTS game_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    game_id INT NOT NULL,
    student_id INT NOT NULL,
    total_score INT DEFAULT 0,
    total_correct INT DEFAULT 0,
    total_questions INT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (game_id) REFERENCES game_activities(game_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX idx_game_activities_teacher ON game_activities(teacher_id);
CREATE INDEX idx_game_activities_subject ON game_activities(subject_id);
CREATE INDEX idx_game_activities_status ON game_activities(status);
CREATE INDEX idx_game_questions_game ON game_questions(game_id);
CREATE INDEX idx_game_options_question ON game_options(question_id);
CREATE INDEX idx_game_responses_student ON game_responses(student_id);
CREATE INDEX idx_game_responses_game ON game_responses(game_id);
CREATE INDEX idx_game_sessions_student ON game_sessions(student_id);
CREATE INDEX idx_game_sessions_game ON game_sessions(game_id);
