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
        <title>Student Dashboard - LARS</title>
        <link rel="stylesheet" href="student-home.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body>
        <!-- NAVBAR -->
        <nav class="navbar">
        <div class="logo">
        <img src="assets/lars.png" alt="Logo">
    </div>
        <div class="profile">
            <img src="assets/dazsma.png" alt="Profile Picture" class="profile-pic">
            <div class="profile-info">
                <div class="profile-name" id="profileName">Loading...</div>
                <div class="profile-status online">Online</div>
            </div>
        </div>
<div class="dropdown">
                    <button class="dropbtn">☰</button>
                    <div class="dropdown-content">
                        <a href="student-viewprof.php">View Profile</a>
                        <a href="student-activities.php">Activities</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
    </nav>

        <!-- BODY -->
        <div class="dashboard">
            <div class="left-column">


<!-- ======== PROFILE STATISTICS ======== -->
<div class="box scrollable" id="box1">
    <div class="profile-stats">
        <h3 class="student-name" id="studentName">Loading...</h3>
        <p class="student-section" id="studentSection">Grade Loading...</p>
        
        <!-- Rewards (clickable) -->
        <div class="rewards">
            <button id="rewardsBtn">Achievements</button>
        </div>

        <!-- Total points -->
        <div class="total-points">
            <span class="label">Total Points:</span>
            <span class="points" id="totalPoints">0</span>
        </div>
        
        <!-- Completion Rate -->
        <div class="completion-rate">
            <span class="label">Completion:</span>
            <span class="rate" id="completionRate">0%</span>
        </div>
    </div>
</div>

<!-- ===== Modal for Achievements ===== -->
<div id="rewardsModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>My Achievements</h3>
        <hr> 
        <br>
        <div id="achievementsList">
            <div class="achievement-item">
                <i class="fas fa-trophy" style="color: gold;"></i>
                <span>First Activity Completed</span>
                <span class="status" id="firstActivityStatus">Not Yet Earned</span>
            </div>
            <div class="achievement-item">
                <i class="fas fa-star" style="color: silver;"></i>
                <span>Perfect Score Achievement</span>
                <span class="status" id="perfectScoreStatus">Not Yet Earned</span>
            </div>
            <div class="achievement-item">
                <i class="fas fa-medal" style="color: bronze;"></i>
                <span>Active Learner (5+ Activities)</span>
                <span class="status" id="activeLearnerStatus">Not Yet Earned</span>
            </div>
            <div class="achievement-item">
                <i class="fas fa-fire" style="color: orange;"></i>
                <span>Streak Master (3 in a row)</span>
                <span class="status" id="streakMasterStatus">Not Yet Earned</span>
            </div>
        </div>
    </div>
</div>




<!-- ======== LIST OF SUBJECTS AND THEIR ACTIVE RECITS ======== -->
<div class="box scrollable" id="box2">
    <div class="active-recits">
        <h3 class="active-recits">Active Activities</h3>
        <div id="loadingSubjects" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading subjects...
        </div>
        <ul class="subject-list" id="subjectsList" style="display: none;">
            <!-- Subjects will be populated dynamically -->
        </ul>
    </div>
</div>

<!-- ===== Modal for Activities ===== -->
<div id="recitsModal" class="recits">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 id="modal-subject-title">Activities</h3>
        <hr>
        <div id="loadingActivities" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading activities...
        </div>
        <ul class="recits-list" id="activitiesList" style="display: none;">
            <!-- Activities will be dynamically inserted here -->
        </ul>
    </div>
</div>




<!-- ======== RECITATION OF THE DAY ======== -->
<div class="box" id="box3">
    <div class="recit-day">
        <h3 class="recit-rotd">ACTIVITY OF THE DAY!</h3>
        <div id="loadingActivityOfDay" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading...
        </div>
        <div id="activityOfDayContent" style="display: none;">
            <h3 class="recit-subject" id="activitySubject">No Activity</h3>
            <p class="recit-recitation" id="activityTitle">No active activities</p>
            <p class="activity-details" id="activityDetails">Check back later</p>
            <button class="take-now-btn" id="takeActivityBtn" style="display: none;">Take Now</button>
        </div>
    </div>
</div>



            </div> <!-- DASHBOARD END DIV -->

            <div class="center-column">
                <!-- ======== RECENT ACTIVITIES ======== -->
<div class="detailed-recits" id="detailedActivities">
    <div id="loadingDetailedActivities" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading recent activities...
    </div>
    <!-- Activities will be populated dynamically -->
</div>



        <!-- ======== LEADERBOARDS ======== -->
                <div class="box scrollable" id="box9">
<div class="leaderboard">
    <h3>LEADERBOARDS</h3>
    <HR>
    <BR>
    <div id="loadingLeaderboard" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading leaderboard...
    </div>
    <ul class="leaderboard-list" id="leaderboardList" style="display: none;">
        <!-- Leaderboard will be populated dynamically -->
    </ul>
</div>

                </div>

            </div><!-- center-column END DIV -->





            <div class="right-column">



<!-- ======== LIST OF SUBMITTED ACTIVITIES ======== -->
<div class="box scrollable" id="box4">
    <div class="submitted-recits">
        <h3>Completed</h3>
        <div class="submitted-total-points">
            <span class="label">Points:</span>
            <span class="points" id="submittedPoints">0</span>
        </div>
        <hr>    
        <div id="loadingSubmitted" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading...
        </div>
        <ul class="submitted-list" id="submittedList" style="display: none;">
            <!-- Submitted activities will be populated dynamically -->
        </ul>
    </div>
</div>

<!-- ======== LIST OF PENDING ACTIVITIES ======== -->
<div class="box scrollable" id="box5">
    <div class="not-submitted-recits">
        <h3>Pending</h3>
        <div class="notsubmitted-total-points">
            <span class="label">Total:</span>
            <span class="points" id="pendingCount">0</span>
        </div>
        <hr>
        <div id="loadingPending" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading...
        </div>
        <ul class="notsubmitted-list" id="pendingList" style="display: none;">
            <!-- Pending activities will be populated dynamically -->
        </ul>
    </div>
</div>


            </div><!-- right-column END DIV -->

        </div>

        <script src="student-home.js"></script>
    </body>
    </html>
