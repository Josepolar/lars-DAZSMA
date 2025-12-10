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
    <link rel="icon" type="image/png" href="../assets/tablogo.png">
    <link rel="stylesheet" href="teacher-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Teacher Dashboard</title>
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
                    <span class="name">Hello <?php $firstName = ''; if (!empty($_SESSION['first_name'])) { $firstName = $_SESSION['first_name']; } elseif (!empty($_SESSION['name'])) { $parts = explode(' ', trim($_SESSION['name'])); $firstName = $parts[0]; } echo htmlspecialchars($firstName); ?></span>
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
    
    <div class="stats-container">
        <div class="stat">
            <div class="stat-content">
                <h1>W E L C O M E !</h1>
                <h3 id="teacher-name">Loading...</h3>
            </div>
        </div>

        <div class="stat">
            <div class="stat-content">
                <h1 id="total-students">0</h1>
                <h3>Total Students</h3>
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

        <!-- Recent Activity Chart (placeholder) -->
        <div class="chart-card">
            <h3>User Activity</h3>
            <div style="text-align:center; color:#aaa;">Coming soon</div>
        </div>
    </div>


    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Fetch teacher dashboard stats and render charts
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Starting dashboard fetch...');
        
        // First, test simple endpoint
        fetch('simple-test.php?test=1')
            .then(res => res.json())
            .then(data => {
                console.log('Simple test result:', data);
            })
            .catch(err => console.error('Simple test failed:', err));
        
        fetch('teacher-activities-backend.php?action=dashboard_stats')
            .then(res => {
                console.log('Response status:', res.status);
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.text(); // Get as text first to see what we're getting
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                    if (data.error) {
                        console.error('Backend error:', data.error);
                        document.getElementById('teacher-name').textContent = 'Error: ' + data.error;
                        return;
                    }
                    
                    if (!data.success && data.message) {
                        console.error('Backend message:', data.message);
                        document.getElementById('teacher-name').textContent = data.message;
                        return;
                    }
                    
                    // Set teacher name
                    document.getElementById('teacher-name').textContent = data.teacher_name || 'Unknown';
                    // Set total students
                    document.getElementById('total-students').textContent = data.total_students || 0;

                    // User Distribution Pie
                    if (data.user_distribution && data.user_distribution.counts.length > 0) {
                        const userChart = new Chart(document.getElementById('userDistributionChart'), {
                            type: 'pie',
                            data: {
                                labels: data.user_distribution.labels,
                                datasets: [{
                                    data: data.user_distribution.counts,
                                    backgroundColor: ['#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f']
                                }]
                            },
                            options: {
                                responsive: true, 
                                plugins: {
                                    legend: {position: 'bottom'}
                                }
                            }
                        });
                    } else {
                        document.getElementById('userDistributionChart').parentElement.innerHTML = 
                            '<h3>User Distribution</h3><div style="text-align:center; color:#aaa;">No data available</div>';
                    }

                    // Grade Distribution Pie
                    if (data.grade_distribution && data.grade_distribution.counts.length > 0) {
                        const gradeChart = new Chart(document.getElementById('gradeDistributionChart'), {
                            type: 'pie',
                            data: {
                                labels: data.grade_distribution.labels,
                                datasets: [{
                                    data: data.grade_distribution.counts,
                                    backgroundColor: ['#f1c40f', '#e67e22', '#16a085', '#2980b9', '#8e44ad']
                                }]
                            },
                            options: {
                                responsive: true, 
                                plugins: {
                                    legend: {position: 'bottom'}
                                }
                            }
                        });
                    } else {
                        document.getElementById('gradeDistributionChart').parentElement.innerHTML = 
                            '<h3>Student Grade Distribution</h3><div style="text-align:center; color:#aaa;">No data available</div>';
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response was:', text);
                    document.getElementById('teacher-name').textContent = 'Parse Error';
                    document.getElementById('total-students').textContent = 'Error';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                document.getElementById('teacher-name').textContent = 'Network Error: ' + error.message;
                document.getElementById('total-students').textContent = 'Error';
            });
    });
    </script></body>
</html>
