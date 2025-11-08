-- Add points_per_pair column to matching_games table
ALTER TABLE matching_games 
ADD COLUMN points_per_pair INT DEFAULT 100 
AFTER show_leaderboard;

-- Add default_points column to game_activities table for quiz games
ALTER TABLE game_activities 
ADD COLUMN default_points INT DEFAULT 100 
AFTER time_limit;
