<?php
require_once 'Database/database.php';

try {
    // Add new columns to activities table
    $alterTable = "ALTER TABLE activities
                  ADD COLUMN IF NOT EXISTS activity_type ENUM('regular', 'crossword', 'flashcards', 'speed_typing') NOT NULL DEFAULT 'regular' AFTER activity_id,
                  ADD COLUMN IF NOT EXISTS game_settings JSON DEFAULT NULL AFTER activity_type";
    
    $pdo->exec($alterTable);
    echo "Successfully updated activities table structure.\n";

} catch (PDOException $e) {
    die("Error updating database structure: " . $e->getMessage());
}
?>