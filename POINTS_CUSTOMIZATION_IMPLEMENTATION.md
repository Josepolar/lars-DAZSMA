# Points Customization Feature - Implementation Summary

## Overview
Added the ability for teachers to customize points per question for Quiz Games and points per pair for Matching Games.

## Changes Made

### 1. Database Updates
**File:** `add_points_per_pair.sql`
- Added `points_per_pair` column to `matching_games` table (default: 100)
- Added `default_points` column to `game_activities` table (default: 100)

**SQL executed:**
```sql
ALTER TABLE matching_games ADD COLUMN points_per_pair INT DEFAULT 100 AFTER show_leaderboard;
ALTER TABLE game_activities ADD COLUMN default_points INT DEFAULT 100 AFTER time_limit;
```

### 2. Frontend Updates

#### teacher-acts.php
- **Quiz Game Modal:** Added "Default Points per Question" input field
  - Default value: 100
  - Min: 10, Max: 10000
  - Name: `default_points`
  
- **Quiz Question Form:** Enhanced "Points" field
  - Required field with step of 10
  - Pre-fills with default points from game creation
  
- **Matching Game Modal:** Added "Points per Pair" input field
  - Default value: 100
  - Min: 10, Max: 1000
  - Name: `points_per_pair`
  - Positioned side-by-side with Game Type

#### teacher-acts.js
- **createGameForm Handler:** Captures `default_points` from form and passes to modals
- **openAddQuestionsModal():** Updated to accept and use `defaultPoints` parameter
  - Pre-fills question points input with default value
  - Stores default_points in currentGameData
- **openCreateMatchingGameModal():** Updated to accept and use `pointsPerPair` parameter
  - Pre-fills points per pair input with default value

### 3. Backend Updates

#### teacher-activities-backend.php

**create_game() Function:**
- Captures `default_points` from POST data (default: 100)
- Includes `default_points` in INSERT query for `game_activities` table
- SQL: `INSERT INTO game_activities (..., default_points, ...)`

**create_matching_game() Function:**
- Captures `points_per_pair` from POST data (default: 100)
- Includes `points_per_pair` in INSERT query for `matching_games` table
- SQL: `INSERT INTO matching_games (..., points_per_pair, ...)`

### 4. Student Game Play Updates

#### students/games/play-matching-game.php
- **PHP Section:** Loads `points_per_pair` from game data
- **JavaScript Section:**
  - Added `pointsPerPair` constant from PHP variable
  - Updated score calculation: `score += pointsPerPair` (was hardcoded 100)

#### students/games/save-matching-results.php
- Updated query to JOIN `matching_games` table to get `points_per_pair`
- Updated points calculation: `$points = $response['is_correct'] ? $points_per_pair : 0`
- Each correct match now earns the custom points value

## How It Works

### Quiz Games
1. Teacher creates game and sets "Default Points per Question" (e.g., 50)
2. When adding questions, the points field pre-fills with 50
3. Teacher can customize points for individual questions (e.g., 100 for harder questions)
4. Students earn the specified points for each correct answer

### Matching Games
1. Teacher creates game and sets "Points per Pair" (e.g., 200)
2. All matching pairs in that game are worth 200 points
3. Students earn 200 points for each correct match
4. Frontend and backend both use the custom points value

## Testing Checklist
- [ ] Create Quiz Game with default points = 50
- [ ] Add questions and verify points pre-fill with 50
- [ ] Modify individual question points to different values
- [ ] Student plays quiz and scores are calculated correctly
- [ ] Create Matching Game with points per pair = 150
- [ ] Add matching pairs
- [ ] Student plays matching game and earns 150 per correct match
- [ ] Verify leaderboard reflects custom points
- [ ] Check teacher records show correct scores

## Files Modified
1. `add_points_per_pair.sql` - NEW (database update)
2. `teachers/teacher-acts.php` - Added input fields for points
3. `teachers/teacher-acts.js` - Updated to capture and pass points values
4. `teachers/teacher-activities-backend.php` - Updated create_game() and create_matching_game()
5. `students/games/play-matching-game.php` - Uses custom points for scoring
6. `students/games/save-matching-results.php` - Saves custom points for each response

## Default Values
- Quiz Game Default Points: 100 per question
- Matching Game Points per Pair: 100 per correct match
- Minimum points allowed: 10
- Maximum points (Quiz): 10,000
- Maximum points (Matching): 1,000

## Benefits
- Teachers have full control over point values
- Can weight more difficult questions/pairs higher
- Flexibility to match institutional grading systems
- Easy to use with sensible defaults
- Backward compatible (defaults to 100 points if not specified)
