-- Add due_date column to game_activities table
ALTER TABLE game_activities 
ADD COLUMN due_date DATETIME NULL 
AFTER default_points;

-- Add due_date column to matching_games table
ALTER TABLE matching_games 
ADD COLUMN due_date DATETIME NULL 
AFTER points_per_pair;
