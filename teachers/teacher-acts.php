
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
    <link rel="stylesheet" href="game-activity-styles.css">
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
            <button class="btn btn-primary" onclick="showCreateActivityModal()">
                <i class="fas fa-plus"></i> Create New Activity
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
                        <option value="crossword">Crossword Puzzle</option>
                        <option value="flashcards">Flash Cards</option>
                        <option value="speed_typing">Speed Typing</option>
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

    <!-- Create Activity Modal -->
    <div id="createActivityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Create New Activity</h2>
                <span class="close" onclick="closeCreateActivityModal()">&times;</span>
            </div>
            <form id="createActivityForm">
                <div class="form-section">
                    <h3>Basic Information</h3>
                    <div class="form-group">
                        <label for="activityTitle">Activity Title *</label>
                        <input type="text" id="activityTitle" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="activityDescription">Description</label>
                        <textarea id="activityDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="activitySubject">Subject *</label>
                            <select id="activitySubject" name="subject_id" required>
                                <option value="">Select Subject</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="activityType">Activity Type *</label>
                            <select id="activityType" name="activity_type" required>
                                <option value="">Select a game type</option>
                                <option value="crossword">Crossword Puzzle</option>
                                <option value="flashcards">Flash Cards</option>
                                <option value="speed_typing">Speed Typing</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="totalPoints">Total Points *</label>
                            <input type="number" id="totalPoints" name="total_points" min="1" value="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="timeLimit">Time Limit (minutes)</label>
                            <input type="number" id="timeLimit" name="time_limit" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="dueDate">Due Date</label>
                            <input type="datetime-local" id="dueDate" name="due_date">
                        </div>
                    </div>
                </div>

                <div class="form-section" id="gameSettingsSection" style="display: none;">
                    <h3>Game Settings</h3>
                    <div id="gameSettingsContainer">
                        <!-- Game settings will be added dynamically -->
                    </div>
                </div>

                <div class="form-section" id="gameContentSection" style="display: none;">
                    <h3>Game Content</h3>
                    <div id="gameContentContainer">
                        <!-- Game content will be added dynamically -->
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateActivityModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Activity</button>
                </div>
            </form>
        </div>
    </div>

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

        <script src="teacher-acts.js"></script>
    <script src="game-activity-handler.js"></script>
```

</body>
</html>
