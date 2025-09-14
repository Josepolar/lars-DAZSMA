<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'lars_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Get staff, teacher, student counts and names
$staffCount = 0;
$teacherCount = 0;
$studentCount = 0;
$staffNames = [];
$teacherNames = [];
$studentNames = [];

$result = $conn->query("SELECT first_name, last_name FROM users WHERE role_id = 2");
if ($result) {
    $staffCount = $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        $staffNames[] = $row['first_name'] . ' ' . $row['last_name'];
    }
}

$result = $conn->query("SELECT first_name, last_name FROM users WHERE role_id = 3");
if ($result) {
    $teacherCount = $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        $teacherNames[] = $row['first_name'] . ' ' . $row['last_name'];
    }
}

$result = $conn->query("SELECT first_name, last_name FROM users WHERE role_id = 4");
if ($result) {
    $studentCount = $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        $studentNames[] = $row['first_name'] . ' ' . $row['last_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Admin Dashboard</title>
</head>
<body>
    <nav class="sidebar">
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="../assets/larslogo.png" alt="logo">
                </span>

                <div class="text header-text">
                    <span class="profession">Admin Dashboard</span>
                    <span class="name">Hello <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
            </div>
            <hr>
        </header>

        <div class="menu-bar">
            <div class="menu">
                <ul class="menu-links">
                    <li class="nav-link">
                        <button class="tablinks" id="defaultTab"><a href="admin-dashboard.php" class="tablinks">Dashboard</a></button>
                    </li>

                    <li class="nav-link">
                        <button class="tablinks"><a href="userman.php" class="tablinks">User Management</a></button>
                    </li>        
                    
                    <li class="nav-link">
                        <button class="tablinks"><a href="studacc.php" class="tablinks">Student Accounts</a></button>
                    </li> 

                     <li class="nav-link">
                        <button class="tablinks"><a href="subjman.php" class="tablinks">Subject Management</a></button>
                    </li> 

                     <li class="nav-link">
                        <button class="tablinks"><a href="usrlog.php" class="tablinks">User Logs</a></button>
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
    

    <div class="stats-container">
        <div class="stat">
            <div class="stat-content">
                <h1><?= $staffCount ?></h1>
                <h3>Staff</h3>
            </div>
        </div>

        <div class="stat">
            <div class="stat-content">
                <h1><?= $teacherCount ?></h1>
                <h3>Teachers</h3>
            </div>
        </div>

        <div class="stat">
            <div class="stat-content">
                <h1><?= $studentCount ?></h1>
                <h3>Student</h3>
            </div>
        </div>
    </div>


        <div class="charts-container">
            <!-- User Distribution Chart -->
            <div class="chart-card">
                <h3>User Distribution</h3>
                <canvas id="userDistributionChart"></canvas>
            </div>

            <!-- Grade Level Distribution -->
            <div class="chart-card">
                <h3>Student Grade Distribution</h3>
                <canvas id="gradeDistributionChart"></canvas>
            </div>

            <!-- Recent Activity Chart -->
            <div class="chart-card">
                <h3>User Activity</h3>
                <canvas id="userActivityChart"></canvas>
            </div>
        </div>

        <?php
        // Fetch grade level distribution
        $gradeQuery = "SELECT grade_level, COUNT(*) as count 
                      FROM users 
                      WHERE role_id = 4 
                      GROUP BY grade_level 
                      ORDER BY grade_level";
        $gradeResult = $conn->query($gradeQuery);
        $gradeCounts = [];
        while ($row = $gradeResult->fetch_assoc()) {
            $gradeCounts[$row['grade_level']] = $row['count'];
        }

        // User distribution data
        $userData = [
            'Staff' => $staffCount,
            'Teachers' => $teacherCount,
            'Students' => $studentCount
        ];

        // Sample activity data (you can modify this to fetch real data from your database)
        $activityData = [
            'Logins' => 45,
            'Updates' => 23,
            'New Records' => 15
        ];

        // Convert PHP arrays to JSON for JavaScript
        $gradeCountsJson = json_encode($gradeCounts);
        $userDataJson = json_encode($userData);
        $activityDataJson = json_encode($activityData);
        ?>

        <script>
            // Pass PHP data to JavaScript
            const gradeData = <?= $gradeCountsJson ?>;
            const userData = <?= $userDataJson ?>;
            const activityData = <?= $activityDataJson ?>;
        </script>
        

    </section>

    <script src="dashboard.js"></script>

</body>
</html>
