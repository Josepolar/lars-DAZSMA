

<?php
session_start();
// Redirect to login if session is missing or expired
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    header('Location: teacher-login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="teacher-acts.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Teacher Activities</title>
</head>
<body>
    <nav class="sidebar">
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="../assets/larslogo.png" alt="logo">
                </span>

                <div class="text header-text">
                    <span class="profession">Teacher Dashboard</span>
                </div>
            </div>
            <hr>
        </header>

        <div class="menu-bar">
            <div class="menu">
                <ul class="menu-links">
                    <li class="nav-link">
                        <button class="tablinks"><a href="teacher-dashboard.php" class="tablinks">Dashboard</a></button>
                    </li>

                    <li class="nav-link">
                        <button class="tablinks active"><a href="teacher-acts.php" class="tablinks">Activities</a></button>
                    </li>
                    
                    <li class="nav-link">
                        <button class="tablinks"><a href="teacher-studs.php" class="tablinks">Students</a></button>
                    </li> 
                    
                </ul>
            </div>

            <div class="bottom-content">
                <li class="nav-link">
                    <button class="tablinks"><a href="../logout.php" class="tablinks">Logout</a></button>
                </li>
            </div>
        </div>
    </nav>

    <section class="home" id="home-section">
        
        <!-- Activity Statistics -->
        <div class="stats-container">
            <div class="stat">
                <div class="stat-content">
                    <h1 id="totalActivities">0</h1>
                    <h3>Total Activities</h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
            </div>

            <div class="stat">
                <div class="stat-content">
                    <h1 id="totalSubmissions">0</h1>
                    <h3>Total Submissions</h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
            </div>

            <div class="stat">
                <div class="stat-content">
                    <h1 id="avgScore">0%</h1>
                    <h3>Average Score</h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            </button>
            <button class="btn btn-primary" onclick="showCreateGameModal()" style="background: #ff6b6b;">
                <i class="fas fa-gamepad"></i> Create Game Activity
            </button>
            <button class="btn btn-primary" onclick="window.location.href='games/manage-games.php'" style="background: #e67e22;">
                <i class="fas fa-cog"></i> Manage Games
            </button>
            <button class="btn btn-secondary" onclick="showBulkUploadModal()">
                <i class="fas fa-upload"></i> Bulk Upload
            </button>
            <button class="btn btn-secondary" onclick="refreshActivities()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>

        <!-- Activities Table -->
        <div class="table-container">
            <div class="table_responsive">
                <h1>MY ACTIVITIES</h1>
                <hr>
                
                <div class="table-controls">
                    <div class="search-container">
                        <input type="text" id="searchActivities" placeholder="Search activities..." onkeyup="filterActivities()">
                        <i class="fas fa-search"></i>
                    </div>
                    <select id="filterType" onchange="filterActivities()">
                        <option value="">All Types</option>
                        <option value="quiz">Quiz</option>
                        <option value="assignment">Assignment</option>
                        <option value="recitation">Recitation</option>
                        <option value="exam">Exam</option>
                        <option value="game">Game</option>
                    </select>
                </div>

                <table id="activitiesTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Points</th>
                            <th>Due Date</th>
                            <th>Submissions</th>
                            <th>Avg Score</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="activitiesTableBody">
                        <tr>
                            <td colspan="9" class="loading-row">
                                <i class="fas fa-spinner fa-spin"></i> Loading activities...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </section>

    <!-- View Submissions Modal -->
    <div id="viewSubmissionsModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2><i class="fas fa-clipboard-list"></i> Activity Submissions</h2>
                <span class="close" onclick="closeViewSubmissionsModal()">&times;</span>
            </div>
            <div id="submissionsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Activity Details Modal -->
    <div id="activityDetailsModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Activity Details</h2>
                <span class="close" onclick="closeActivityDetailsModal()">&times;</span>
            </div>
            <div id="activityDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Bulk Upload Modal -->
    <div id="bulkUploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-upload"></i> Bulk Upload Records</h2>
                <span class="close" onclick="closeBulkUploadModal()">&times;</span>
            </div>
            <form id="bulkUploadForm">
                <div class="form-section">
                    <div class="form-group">
                        <label for="csvFile">Select CSV File</label>
                        <input type="file" id="csvFile" name="csvFile" accept=".csv" required>
                    </div>
                    <div class="template-download">
                        <p>Download the template for: </p>
                        <button type="button" class="btn btn-small" onclick="downloadTemplate('activities')">Activities Template</button>
                        <button type="button" class="btn btn-small" onclick="downloadTemplate('grades')">Grades Template</button>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeBulkUploadModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Game Modal -->
    <div id="createGameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-gamepad"></i> Create New Game Activity</h2>
                <span class="close" onclick="closeCreateGameModal()">&times;</span>
            </div>
            <form id="createGameForm">
                <div class="form-section">
                    <p style="color: #666; margin-bottom: 20px;">Create an engaging quiz or matching game for your students</p>
                    
                    <div id="gameFormError" class="error-message" style="display: none;"></div>
                    <div id="gameFormSuccess" class="success-message" style="display: none;"></div>
                    
                    <div class="form-group">
                        <label for="gameType">Game Type *</label>
                        <select id="gameType" name="game_type" required onchange="updateGameTypeInfo(this.value)">
                            <option value="quiz">🎯 Quiz Game (Multiple Choice Questions)</option>
                            <option value="matching">🧩 Matching Game (Match Items/Images)</option>
                        </select>
                        <div class="help-text" id="game-type-help">
                            Quiz games test knowledge with multiple-choice questions
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="gameTitle">Game Title *</label>
                        <input type="text" id="gameTitle" name="title" required 
                               placeholder="e.g., Chapter 1 Quiz - Introduction to Programming">
                        <div class="help-text">Choose a clear, descriptive title for your game</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="gameDescription">Description</label>
                        <textarea id="gameDescription" name="description" rows="3"
                                  placeholder="Describe what this game covers..."></textarea>
                        <div class="help-text">Optional: Add details about topics covered or learning objectives</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gameSubject">Subject *</label>
                            <select id="gameSubject" name="subject_id" required>
                                <option value="">Select a subject</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="gameTimeLimit" id="gameTimeLimitLabel">Time per Question (seconds) *</label>
                            <input type="number" id="gameTimeLimit" name="time_limit" 
                                   min="10" max="600" value="30" required>
                            <div class="help-text" id="gameTimeLimitHelp">Recommended: 20-60 seconds</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="defaultPointsPerQuestion">Default Points per Question *</label>
                            <input type="number" id="defaultPointsPerQuestion" name="default_points" 
                                   min="10" max="10000" value="100" step="10" required>
                            <div class="help-text">Default points awarded for each correct answer</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="showLeaderboard" name="show_leaderboard" value="1" checked>
                            <label for="showLeaderboard">Show leaderboard to students</label>
                        </div>
                        <div class="help-text">Students will see their ranking after completing the game</div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateGameModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Game & Add Questions/Pairs</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Questions Modal -->
    <div id="addQuestionsModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2><i class="fas fa-question-circle"></i> Add Questions - <span id="gameTitle"></span></h2>
                <span class="close" onclick="closeAddQuestionsModal()">&times;</span>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <div class="game-info-bar" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                    <p style="margin: 5px 0;"><strong>Subject:</strong> <span id="gameSubjectName"></span></p>
                    <p style="margin: 5px 0;"><strong>Questions Added:</strong> <span id="questionCount">0</span></p>
                </div>
                
                <div class="add-question-section" style="background: white; padding: 25px; border: 2px solid #e0e0e0; border-radius: 12px; margin-bottom: 25px;">
                    <h3 style="color: var(--primary-color); margin-bottom: 20px;">Add New Question</h3>
                    
                    <div id="questionFormError" class="error-message" style="display: none;"></div>
                    <div id="questionFormSuccess" class="success-message" style="display: none;"></div>
                    
                    <form id="addQuestionForm">
                        <input type="hidden" id="currentGameId" name="game_id">
                        
                        <div class="form-group">
                            <label for="questionText">Question *</label>
                            <textarea id="questionText" name="question_text" rows="3" required 
                                      placeholder="Enter your question here..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Answer Options * (Select the correct answer)</label>
                            <div class="options-grid">
                                <div class="option-input option-red">
                                    <input type="radio" name="correct_option" value="0" required>
                                    <input type="text" name="options[]" placeholder="Option 1" required>
                                </div>
                                <div class="option-input option-blue">
                                    <input type="radio" name="correct_option" value="1" required>
                                    <input type="text" name="options[]" placeholder="Option 2" required>
                                </div>
                                <div class="option-input option-yellow">
                                    <input type="radio" name="correct_option" value="2">
                                    <input type="text" name="options[]" placeholder="Option 3">
                                </div>
                                <div class="option-input option-green">
                                    <input type="radio" name="correct_option" value="3">
                                    <input type="text" name="options[]" placeholder="Option 4">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="questionTimeLimit">Time Limit (seconds)</label>
                                <input type="number" id="questionTimeLimit" name="time_limit" value="30" min="10" max="300">
                            </div>
                            
                            <div class="form-group">
                                <label for="questionPoints">Points *</label>
                                <input type="number" id="questionPoints" name="points" value="100" min="10" max="10000" step="10" required>
                                <div class="help-text">Points awarded for answering correctly</div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Question
                        </button>
                    </form>
                </div>
                
                <div class="questions-list-section" id="questionsListSection" style="display: none;">
                    <h3 style="color: var(--primary-color); margin-bottom: 15px;">
                        Questions (<span id="questionsListCount">0</span>)
                    </h3>
                    <div id="questionsList">
                        <!-- Questions will be loaded here -->
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
                    <button type="button" class="btn btn-secondary" onclick="closeAddQuestionsModal()">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="finishAddingQuestions()">
                        <i class="fas fa-check"></i> Done Adding Questions
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Matching Game Modal -->
    <div id="createMatchingGameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-puzzle-piece"></i> Create Matching Game</h2>
                <span class="close" onclick="closeCreateMatchingGameModal()">&times;</span>
            </div>
            <form id="createMatchingGameForm">
                <div class="form-section">
                    <p style="color: #666; margin-bottom: 20px;">Create an interactive matching game where students connect related items</p>
                    
                    <div id="matchingGameFormError" class="error-message" style="display: none;"></div>
                    <div id="matchingGameFormSuccess" class="success-message" style="display: none;"></div>
                    
                    <input type="hidden" id="matchingGameSubjectId">
                    <input type="hidden" id="matchingGameShowLeaderboard">
                    
                    <div class="form-group">
                        <label for="matchingGameTitle">Game Title *</label>
                        <input type="text" id="matchingGameTitle" name="title" required 
                               placeholder="e.g., Match Animals to Their Habitats">
                        <div class="help-text">Choose a clear, descriptive title for your matching game</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="matchingGameDescription">Description</label>
                        <textarea id="matchingGameDescription" name="description" rows="3"
                                  placeholder="Describe what students will match..."></textarea>
                        <div class="help-text">Optional: Add details about the matching activity</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Subject</label>
                        <div style="padding: 12px; background: #f8f9fa; border-radius: 6px; color: #333;">
                            <strong id="matchingGameSubjectName">-</strong>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="matchingGameTimeLimit">Total Game Time (seconds) *</label>
                        <input type="number" id="matchingGameTimeLimit" name="time_limit" 
                               min="60" max="1800" value="300" required>
                        <div class="help-text">Recommended: 180-600 seconds (3-10 minutes)</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="matchingGameType">Matching Type *</label>
                            <select id="matchingGameType" name="game_type" required onchange="updateMatchingGameTypePreview(this.value)">
                                <option value="">Select matching type</option>
                                <option value="image-to-text">🖼️ Image to Text (Match pictures with words)</option>
                                <option value="text-to-text">📝 Text to Text (Match related words/concepts)</option>
                                <option value="image-to-image">🎨 Image to Image (Match related pictures)</option>
                                <option value="number-to-text">🔢 Number to Text (Match numbers with words)</option>
                            </select>
                            <div class="help-text">Choose how students will match items</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="matchingGamePointsPerPair">Points per Pair *</label>
                            <input type="number" id="matchingGamePointsPerPair" name="points_per_pair" 
                                   min="10" max="1000" value="100" step="10" required>
                            <div class="help-text">Points awarded for each correct match</div>
                        </div>
                    </div>
                    
                    <div id="matching-game-type-preview" style="display: none;">
                        <!-- Preview will be populated by JavaScript -->
                    </div>
                    
                    <div class="form-group">
                        <div style="padding: 12px; background: #f0f9ff; border-radius: 6px; border-left: 4px solid #0ea5e9;">
                            <p style="margin: 0; color: #0c4a6e; font-size: 13px;">
                                <i class="fas fa-info-circle"></i> <strong>Next step:</strong> After creating the game, you'll add matching pairs (items that students will match together)
                            </p>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateMatchingGameModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Game & Add Pairs</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Matching Pairs Modal -->
    <div id="addMatchingPairsModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2><i class="fas fa-puzzle-piece"></i> Add Matching Pairs - <span id="matchingGameTitleDisplay"></span></h2>
                <span class="close" onclick="closeAddMatchingPairsModal()">&times;</span>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <div class="game-info-bar" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                    <p style="margin: 5px 0;"><strong>Subject:</strong> <span id="matchingGameSubjectDisplay"></span></p>
                    <p style="margin: 5px 0;"><strong>Game Type:</strong> <span id="matchingGameTypeDisplay"></span></p>
                    <p style="margin: 5px 0;"><strong>Pairs Added:</strong> <span id="pairCount">0</span></p>
                </div>
                
                <div class="add-pair-section" style="background: white; padding: 25px; border: 2px solid #e0e0e0; border-radius: 12px; margin-bottom: 25px;">
                    <h3 style="color: var(--primary-color); margin-bottom: 20px;">Add New Pair</h3>
                    
                    <div id="pairFormError" class="error-message" style="display: none;"></div>
                    <div id="pairFormSuccess" class="success-message" style="display: none;"></div>
                    
                    <form id="addPairForm" enctype="multipart/form-data">
                        <input type="hidden" id="currentMatchingGameId" name="matching_game_id">
                        <input type="hidden" id="currentGameType" name="game_type">
                        
                        <div class="form-row">
                            <div class="form-group" id="leftItemGroup">
                                <label id="leftItemLabel">Left Item *</label>
                                <input type="text" id="leftItemText" name="left_item" 
                                       placeholder="Enter text for left side">
                                <input type="file" id="leftItemImage" name="left_image" 
                                       accept="image/*" style="display: none;">
                                <div class="help-text" id="leftItemHelp">Text that appears on the left side</div>
                            </div>
                            
                            <div class="form-group" id="rightItemGroup">
                                <label id="rightItemLabel">Right Item *</label>
                                <input type="text" id="rightItemText" name="right_item" required
                                       placeholder="Enter text for right side">
                                <input type="file" id="rightItemImage" name="right_image" 
                                       accept="image/*" style="display: none;">
                                <div class="help-text" id="rightItemHelp">Text that matches with the left item</div>
                            </div>
                        </div>
                        
                        <div id="imagePreviewContainer" style="display: none; margin-top: 15px;">
                            <div class="form-row">
                                <div id="leftImagePreview" style="display: none;">
                                    <p style="font-weight: bold; margin-bottom: 5px;">Left Image Preview:</p>
                                    <img id="leftPreviewImg" style="max-width: 200px; max-height: 150px; border-radius: 8px;">
                                </div>
                                <div id="rightImagePreview" style="display: none;">
                                    <p style="font-weight: bold; margin-bottom: 5px;">Right Image Preview:</p>
                                    <img id="rightPreviewImg" style="max-width: 200px; max-height: 150px; border-radius: 8px;">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i> Add Pair
                        </button>
                    </form>
                </div>
                
                <div class="pairs-list-section" id="pairsListSection" style="display: none;">
                    <h3 style="color: var(--primary-color); margin-bottom: 15px;">
                        Matching Pairs (<span id="pairsListCount">0</span>)
                    </h3>
                    <div id="pairsList">
                        <!-- Pairs will be loaded here -->
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
                    <button type="button" class="btn btn-secondary" onclick="closeAddMatchingPairsModal()">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="finishAddingPairs()">
                        <i class="fas fa-check"></i> Done Adding Pairs
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="teacher-acts.js"></script>

</body>
</html>

