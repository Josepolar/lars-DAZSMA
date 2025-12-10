

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/png" href="../assets/tablogo.png">
    <link rel="stylesheet" href="teacher-acts.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Teacher Activities</title>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleMobileSidebar()" style="display:none;">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay" onclick="toggleMobileSidebar()"></div>
    
    <nav class="sidebar">
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="../assets/larslogo.png" alt="logo">
                </span>

                <div class="text header-text">
                    <span class="profession">Teacher Dashboard</span>
                    <span class="name">Hello <?php $firstName = ''; if (!empty($_SESSION['first_name'])) { $firstName = $_SESSION['first_name']; } elseif (!empty($_SESSION['name'])) { $parts = explode(' ', trim($_SESSION['name'])); $firstName = $parts[0]; } echo htmlspecialchars($firstName); ?></span>
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
                    
                    <li class="nav-link">
                        <button class="tablinks"><a href="teacher-game-scores.php" class="tablinks">Game Scores</a></button>
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
            <button class="btn btn-primary" onclick="window.location.href='games/manage-games.php'" style="background: #e67e22;">
                <i class="fas fa-gamepad"></i> Manage Games
            </button>
            <button class="btn btn-success" onclick="showScoreReportModal()" style="background: #28a745;">
                <i class="fas fa-trophy"></i> Score Reports
            </button>
            <button class="btn btn-danger" onclick="showResetLeaderboardModal()" style="background: #dc3545;">
                <i class="fas fa-redo"></i> Reset Leaderboard
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

                <div class="table-wrapper">
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









    <!-- Score Report Modal -->
    <div id="scoreReportModal" class="modal">
        <div class="modal-content extra-large">
            <div class="modal-header">
                <h2><i class="fas fa-trophy"></i> Game Score Reports</h2>
                <span class="close" onclick="closeScoreReportModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Filter Options -->
                <div class="report-filters">
                    <div class="filter-group">
                        <label for="reportGameType">Game Type:</label>
                        <select id="reportGameType" onchange="loadScoreReport()">
                            <option value="all">All Games</option>
                            <option value="quiz">Quiz Games</option>
                            <option value="matching">Matching Games</option>
                            <option value="typing">Typing Games</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="reportGrade">Grade Level:</label>
                        <select id="reportGrade" onchange="loadScoreReport()">
                            <option value="all">All Grades</option>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                            <option value="10">Grade 10</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="reportSection">Section:</label>
                        <select id="reportSection" onchange="loadScoreReport()">
                            <option value="all">All Sections</option>
                            <!-- Populated dynamically -->
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="reportGame">Specific Game:</label>
                        <select id="reportGame" onchange="loadScoreReport()">
                            <option value="all">All Games</option>
                            <!-- Populated dynamically -->
                        </select>
                    </div>
                </div>

                <!-- Export Buttons -->
                <div class="export-buttons">
                    <button class="btn btn-export-pdf" onclick="exportScoreReport('top10_all')">
                        <i class="fas fa-file-pdf"></i> Export Top 10 All Grades (PDF)
                    </button>
                    <button class="btn btn-export-pdf" onclick="exportScoreReport('top10_section')">
                        <i class="fas fa-file-pdf"></i> Export Top 10 Per Section (PDF)
                    </button>
                    <button class="btn btn-export-pdf" onclick="exportScoreReport('full_report')">
                        <i class="fas fa-file-pdf"></i> Export Full Report (PDF)
                    </button>
                </div>

                <!-- Score Report Content -->
                <div id="scoreReportContent">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i> Loading score reports...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Leaderboard Modal -->
    <div id="resetLeaderboardModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i> Reset Leaderboard</h2>
                <span class="close" onclick="closeResetLeaderboardModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="warning-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <p><strong>Warning:</strong> This action will permanently delete all game session scores. This cannot be undone!</p>
                </div>
                
                <div class="reset-options">
                    <div class="form-group">
                        <label for="resetGameType">Select Game Type to Reset:</label>
                        <select id="resetGameType" onchange="updateResetGameList()">
                            <option value="">-- Select Game Type --</option>
                            <option value="all">All Games (Complete Reset)</option>
                            <option value="quiz">Quiz Games Only</option>
                            <option value="matching">Matching Games Only</option>
                            <option value="typing">Typing Games Only</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="specificGameGroup" style="display: none;">
                        <label for="resetSpecificGame">Select Specific Game (Optional):</label>
                        <select id="resetSpecificGame">
                            <option value="all">All Games of Selected Type</option>
                            <!-- Populated dynamically -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmReset">Type "RESET" to confirm:</label>
                        <input type="text" id="confirmReset" placeholder="Type RESET to confirm">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeResetLeaderboardModal()">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="executeResetLeaderboard()" id="resetBtn" disabled>
                        <i class="fas fa-trash-alt"></i> Reset Leaderboard
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="teacher-acts.js"></script>
    <script>
        // Mobile Menu Toggle
        function toggleMobileSidebar() {
            document.querySelector('.sidebar').classList.toggle('show-mobile');
            document.querySelector('.sidebar-overlay').classList.toggle('show');
        }
        
        // Show mobile menu button on small screens
        function checkMobileMenu() {
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (window.innerWidth <= 576) {
                toggle.style.display = 'flex';
            } else {
                toggle.style.display = 'none';
                document.querySelector('.sidebar').classList.remove('show-mobile');
                document.querySelector('.sidebar-overlay').classList.remove('show');
            }
        }
        
        window.addEventListener('resize', checkMobileMenu);
        window.addEventListener('load', checkMobileMenu);
    </script>

</body>
</html>