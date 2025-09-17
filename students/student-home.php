    <?php
session_start();

// Redirect to login if session is missing or expired
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    header('Location: stud-login.php');
    exit();
}

// Database connection
include '../Database/database.php';

// Get current student's profile information
$profileStmt = $pdo->prepare("SELECT user_id, first_name, last_name, grade_level FROM users WHERE user_id = ? AND role_id = 4");
$profileStmt->execute([$_SESSION['user_id']]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    header('Location: stud-login.php');
    exit();
}

// Get class leaderboard (ALL students in same grade level with points only from their grade level activities)
$leaderboardStmt = $pdo->prepare("
    SELECT 
        u.user_id,
        CONCAT(u.first_name, ' ', u.last_name) as full_name,
        u.first_name,
        u.last_name,
        COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level THEN ss.total_score ELSE 0 END), 0) as total_points,
        COUNT(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level THEN 1 END) as completed_activities,
        COALESCE(AVG(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = u.grade_level AND ss.percentage IS NOT NULL THEN ss.percentage END), 0) as avg_percentage
    FROM users u
    LEFT JOIN student_submissions ss ON u.user_id = ss.student_id
    LEFT JOIN activities a ON ss.activity_id = a.activity_id
    LEFT JOIN subjects s ON a.subject_id = s.subject_id
    WHERE u.role_id = 4 AND u.grade_level = ?
    GROUP BY u.user_id, u.first_name, u.last_name
    ORDER BY total_points DESC, avg_percentage DESC
");
$leaderboardStmt->execute([$profile['grade_level']]);
$leaderboard = $leaderboardStmt->fetchAll(PDO::FETCH_ASSOC);

// Get current user's detailed statistics
$userStatsStmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = ? THEN ss.total_score ELSE 0 END), 0) as total_points,
        COUNT(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = ? THEN 1 END) as completed_activities,
        COUNT(CASE WHEN a.activity_id IS NOT NULL AND s.grade_level = ? THEN 1 END) as total_available_activities,
        COALESCE(AVG(CASE WHEN ss.submission_status IN ('submitted', 'graded') AND s.grade_level = ? AND ss.percentage IS NOT NULL THEN ss.percentage END), 0) as avg_percentage
    FROM student_submissions ss
    JOIN activities a ON ss.activity_id = a.activity_id
    JOIN subjects s ON a.subject_id = s.subject_id
    WHERE ss.student_id = ?
");
$userStatsStmt->execute([$profile['grade_level'], $profile['grade_level'], $profile['grade_level'], $profile['grade_level'], $_SESSION['user_id']]);
$userStats = $userStatsStmt->fetch(PDO::FETCH_ASSOC);

// Calculate completion rate
$completionRate = 0;
if ($userStats && $userStats['total_available_activities'] > 0) {
    $completionRate = ($userStats['completed_activities'] / $userStats['total_available_activities']) * 100;
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
        <img src="../assets/lars.png" alt="Logo">
    </div>
        <div class="profile">
            <img src="../assets/dazsma.png" alt="Profile Picture" class="profile-pic">
            <div class="profile-info">
                <div class="profile-name" id="profileName"><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></div>
                <div class="profile-status online">Online</div>
            </div>
        </div>
<div class="dropdown">
                    <button class="dropbtn">☰</button>
                    <div class="dropdown-content">
                        <a href="student-viewprof.php">View Profile</a>
                        <a href="student-activities.php">Activities</a>
                        <a href="../logout.php">Logout</a>
                    </div>
                </div>
    </nav>

        <!-- BODY -->
        <div class="dashboard">
            <div class="left-column">


<!-- ======== PROFILE STATISTICS ======== -->
<div class="box scrollable" id="box1">
    <div class="profile-stats">
        <h3 class="student-name" id="studentName"><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h3>
        <p class="student-section" id="studentSection">Grade <?php echo htmlspecialchars($profile['grade_level']); ?></p>
        
        <!-- Rewards (clickable) -->
        <div class="rewards">
            <button id="rewardsBtn">Achievements</button>
        </div>

        <!-- Total points -->
        <div class="total-points">
            <span class="label">Total Points:</span>
            <span class="points" id="totalPoints"><?php echo $userStats ? number_format($userStats['total_points'], 0) : '0'; ?></span>
        </div>
        
        <!-- Completion Rate -->
        <div class="completion-rate">
            <span class="label">Completion:</span>
            <span class="rate" id="completionRate"><?php echo number_format($completionRate, 1); ?>%</span>
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
    <h3>LEADERBOARDS - GRADE <?php echo htmlspecialchars($profile['grade_level']); ?></h3>
    <HR>
    <BR>
    
    <?php if (!empty($leaderboard)): ?>
        <ul class="leaderboard-list" id="leaderboardList">
            <?php foreach ($leaderboard as $index => $student): ?>
                <?php 
                $rank = $index + 1;
                $isCurrentStudent = ($student['user_id'] == $_SESSION['user_id']);
                $highlightClass = $isCurrentStudent ? 'current-student' : '';
                ?>
                <li class="leaderboard-item <?php echo $highlightClass; ?>">
                    <div class="rank-number">#<?php echo $rank; ?></div>
                    <div class="student-avatar">
                        <img src="../assets/dazsma.png" alt="Avatar" class="lb-pic">
                    </div>
                    <div class="student-details">
                        <div class="student-name">
                            <?php echo htmlspecialchars($student['full_name']); ?>
                            <?php if ($isCurrentStudent): ?>
                                <span class="you-badge">(You)</span>
                            <?php endif; ?>
                        </div>
                        <div class="student-grade">Grade <?php echo htmlspecialchars($profile['grade_level']); ?></div>
                    </div>
                    <div class="student-points">
                        <div class="points-value"><?php echo number_format($student['total_points'], 0); ?></div>
                        <div class="points-label">pts</div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <div class="leaderboard-summary">
            <p><strong>Total students in Grade <?php echo $profile['grade_level']; ?>: <?php echo count($leaderboard); ?></strong></p>
            <?php 
            $zeroPointStudents = array_filter($leaderboard, function($student) {
                return $student['total_points'] == 0;
            });
            ?>
            <p>Students with activities completed: <?php echo count($leaderboard) - count($zeroPointStudents); ?></p>
        </div>
        
    <?php else: ?>
        <div class="no-leaderboard">
            <p>No students found in your grade level.</p>
        </div>
    <?php endif; ?>
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
            <span class="points" id="submittedPoints"><?php echo $userStats ? number_format($userStats['total_points'], 0) : '0'; ?></span>
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
