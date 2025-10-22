<?php
session_start();

// Redirect to login if session is missing or expired
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    header('Location: stud-login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Activities - LARS</title>
    <link rel="stylesheet" href="student-activities.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="logo">
            <img src="../assets/lars.png" alt="Logo">
        </div>
        <div class="nav-center">
            <h2>Student Activities</h2>
        </div>
        <div class="profile">
            <img src="../assets/dazsma.png" alt="Profile Picture" class="profile-pic">
            <div class="profile-info">
                <div class="profile-name" id="profileName">Loading...</div>
                <div class="profile-status online">Online</div>
            </div>
        </div>
        <div class="dropdown">
            <button class="dropbtn">☰</button>
            <div class="dropdown-content">
                <a href="student-home.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="student-viewprof.php"><i class="fas fa-user"></i> View Profile</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="main-container">
        <!-- FILTERS SECTION -->
        <div class="filters-section">
            <div class="filter-group">
                <label for="statusFilter">Status:</label>
                <select id="statusFilter">
                    <option value="all">All Activities</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="subjectFilter">Subject:</label>
                <select id="subjectFilter">
                    <option value="all">All Subjects</option>
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
            
            <div class="filter-group">
                <label for="typeFilter">Type:</label>
                <select id="typeFilter">
                    <option value="all">All Types</option>
                    <option value="quiz">Quiz</option>
                    <option value="assignment">Assignment</option>
                    <option value="recitation">Recitation</option>
                    <option value="exam">Exam</option>
                </select>
            </div>
            
            <div class="filter-group">
                <button id="refreshBtn" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        <!-- STATISTICS CARDS -->
        <div class="stats-section">
            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3 id="pendingCount">0</h3>
                    <p>Pending Activities</p>
                </div>
            </div>
            
            <div class="stat-card completed">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3 id="completedCount">0</h3>
                    <p>Completed Activities</p>
                </div>
            </div>
            
            <div class="stat-card overdue">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3 id="overdueCount">0</h3>
                    <p>Overdue Activities</p>
                </div>
            </div>
            
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-info">
                    <h3 id="totalCount">0</h3>
                    <p>Total Activities</p>
                </div>
            </div>
        </div>

        <!-- ACTIVITIES GRID -->
        <div class="activities-section">
            <div id="loadingActivities" class="loading-indicator">
                <i class="fas fa-spinner fa-spin"></i> Loading activities...
            </div>
            
            <div id="activitiesGrid" class="activities-grid" style="display: none;">
                <!-- Activities will be populated dynamically -->
            </div>
            
            <div id="noActivities" class="no-activities" style="display: none;">
                <i class="fas fa-inbox"></i>
                <h3>No Activities Found</h3>
                <p>There are no activities matching your current filters.</p>
            </div>
        </div>
    </div>

    <!-- ACTIVITY DETAIL MODAL -->
    <div id="activityModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2 id="modalActivityTitle">Activity Details</h2>
                <span class="close" onclick="closeActivityModal()">&times;</span>
            </div>
            <div id="modalActivityContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- TAKE ACTIVITY MODAL -->
    <div id="takeActivityModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2 id="takeActivityTitle">Take Activity</h2>
                <div class="activity-timer" id="activityTimer" style="display: none;">
                    <i class="fas fa-clock"></i>
                    <span id="timeRemaining">00:00</span>
                </div>
                <span class="close" onclick="closeTakeActivityModal()">&times;</span>
            </div>
            <div id="takeActivityContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <script src="student-activities.js"></script>
</body>
</html>