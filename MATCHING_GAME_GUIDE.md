# Matching Game System - Implementation Guide

## Overview
An educational matching game system has been successfully implemented for the LARS platform. Students can now play interactive drag-and-drop matching games where they connect related items, images, or words.

## Features Implemented

### 🧩 Four Matching Game Types
1. **Image to Text** - Match pictures with words (e.g., animals with their names)
2. **Text to Text** - Match related words/concepts (e.g., vocabulary with definitions)
3. **Image to Image** - Match related pictures (e.g., baby animals with adults)
4. **Number to Text** - Match numbers with words (e.g., "5" with "five")

### 👨‍🏫 Teacher Features
- Choose between Quiz Game or Matching Game when creating activities
- Select matching game type with visual previews
- Upload images for matching pairs (optional based on game type)
- Add unlimited matching pairs (minimum 4 required)
- View detailed student results and statistics
- Track student performance and completion times

### 👨‍🎓 Student Features
- Play interactive drag-and-drop matching games
- Visual feedback for correct/incorrect matches
- Timer-based gameplay with score tracking
- Progress bar showing completion status
- Beautiful animations and sound effects
- View completion statistics

## Database Setup

### Step 1: Import Database Schema
Run the SQL file to create the necessary tables:

```sql
-- Located at: DB/matching_game_system.sql
```

This creates the following tables:
- `matching_games` - Stores matching game details
- `matching_pairs` - Stores the items to be matched
- `matching_sessions` - Tracks student game sessions
- `matching_responses` - Records individual match attempts

### Step 2: Verify Directory Structure
Ensure the uploads directory exists with proper permissions:

```
uploads/
└── matching_games/  (Auto-created, needs write permissions)
```

## File Structure

### Teacher Files (teachers/games/)
- `create-game.php` - Updated with game type selector (Quiz or Matching)
- `create-matching-game.php` - Create new matching games
- `add-matching-pairs.php` - Add/manage matching pairs with image uploads
- `manage-games.php` - Updated to show both quiz and matching games
- `matching-game-results.php` - View student results for matching games

### Student Files (students/games/)
- `available-games.php` - Updated to show both game types
- `play-matching-game.php` - Interactive drag-and-drop game interface
- `save-matching-results.php` - AJAX endpoint to save game results

### Database Files
- `DB/matching_game_system.sql` - Database schema for matching games

## How to Use

### For Teachers:

1. **Create a Matching Game**
   - Navigate to Teacher Dashboard → Games
   - Click "Create New Game"
   - Select "Matching Game" from the game type dropdown
   - Choose matching type (Image to Text, Text to Text, etc.)
   - Fill in game details and click "Create Game & Add Pairs"

2. **Add Matching Pairs**
   - Upload images (if required by game type)
   - Enter text for left and right items
   - Add at least 4 pairs to publish
   - Click "Publish Game" when ready

3. **View Results**
   - Navigate to Manage Games
   - Click "Results" on any matching game
   - View statistics and student performance

### For Students:

1. **Play a Matching Game**
   - Navigate to Student Dashboard → Games
   - Browse available matching games (marked with 🧩)
   - Click "Start Matching Game"
   - Drag items from left column to matching items on right
   - Complete all pairs before time runs out

2. **Game Controls**
   - Drag and drop items to match them
   - Green highlight = correct match
   - Red shake = incorrect match
   - Timer counts down - complete before time expires
   - Progress bar shows completion status

## Technical Details

### Image Upload
- Supported formats: JPG, PNG, GIF
- Maximum file size: 5MB per image
- Images stored in: `uploads/matching_games/`
- Auto-generated unique filenames

### Scoring System
- 100 points per correct match
- Bonus points for speed (future enhancement)
- Score displayed in real-time
- Results saved to database

### Drag and Drop
- HTML5 Drag and Drop API
- Touch-friendly on mobile devices
- Visual feedback during dragging
- Smooth animations for matches/mismatches

## Updates to Existing Files

### create-game.php
- Added game type dropdown (Quiz/Matching)
- Dynamic form fields based on selection
- Redirects to appropriate creation flow

### manage-games.php
- Updated SQL query to include both game types using UNION
- Different action buttons for matching games
- Icons to distinguish game types (🎯 Quiz, 🧩 Matching)

### available-games.php
- Updated SQL query to show both game types
- Different display for matching vs quiz games
- Proper routing to correct game player

## Browser Compatibility
- Chrome/Edge: ✅ Full support
- Firefox: ✅ Full support
- Safari: ✅ Full support
- Mobile browsers: ✅ Touch-optimized

## Performance Considerations
- Images are optimized on upload
- Lazy loading for game lists
- Efficient database queries with indexes
- AJAX for seamless result saving

## Future Enhancements (Optional)
- Sound effects for matches (files referenced in code)
- Timed bonus scoring
- Multiplayer competitions
- Leaderboards
- Hint system
- More matching game types
- CSV export of results

## Troubleshooting

### Images Not Uploading
- Check `uploads/matching_games/` directory permissions (755 or 777)
- Verify PHP upload limits in php.ini
- Check file size (max 5MB)

### Games Not Appearing
- Verify game status is 'active'
- Check student grade level matches subject
- Ensure at least 4 pairs are added

### Drag and Drop Not Working
- Enable JavaScript in browser
- Try on different browser
- Check browser console for errors

## Support
For issues or questions, contact the development team or refer to the LARS documentation.

---
**Version:** 1.0  
**Last Updated:** November 8, 2025  
**Developed for:** LARS Educational Platform
