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
    <link rel="stylesheet" href="teacher-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Teacher Dashboard</title>
</head>
<body>
    <nav class="sidebar">
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="assets/larslogo.png" alt="logo">
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
                        <button class="tablinks" id="defaultTab"><a href="teacher-dashboard.php" class="tablinks">Dashboard</a></button>
                    </li>

                    <li class="nav-link">
                        <button class="tablinks"><a href="teacher-acts.php" class="tablinks">Activities</a></button>
                    </li>        
                    
                    <li class="nav-link">
                        <button class="tablinks"><a href="teacher-studs.php" class="tablinks">Students</a></button>
                    </li> 
                    
                </ul>
            </div>

            <div class="bottom-content">
            <li class="nav-link">
                        <button class="tablinks"><a href="logout_admin.php" class="tablinks">Logout</a></button>
                    </li>
            </div>
        </div>
    </nav>

   <section class="home" id="home-section">
    
    <div class="stats-container">
        <div class="stat">
            <div class="stat-content">
                <h1>W E L C O M E !</h1>
                <h3>Name of Teacher</h3>
            </div>
        </div>

        <div class="stat">
            <div class="stat-content">
                <h1>0</h1>
                <h3>Total Students</h3>
            </div>
        </div>

    </div>


        <div class="charts-container">
            <!-- User Distribution Chart -->
            <div class="chart-card">
                <h3>User Distribution</h3>
            </div>

            <!-- Grade Level Distribution -->
            <div class="chart-card">
                <h3>Student Grade Distribution</h3>
            </div>

            <!-- Recent Activity Chart -->
            <div class="chart-card">
                <h3>User Activity</h3>
            </div>
        </div>


    </section>

    <script src="teacher-dashboard.js"></script>

</body>
</html>
