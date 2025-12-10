<?php
session_start();

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header("Location: staff-login.php");
    exit();
}

// Database connection
include '../Database/database.php';

// Get teacher count
$teacherCount = 0;
$result = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role_id = 3");
if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
  $teacherCount = $row['cnt'];
}

// Get student count
$studentCount = 0;
$result = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role_id = 4");
if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
  $studentCount = $row['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/tablogo.png">
    <link rel="stylesheet" href="staff-dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?php echo time(); ?>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <title>Staff Dashboard</title>
</head>
<body>
    <nav class="sidebar">
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="../assets/larslogo.png" alt="logo">
                </span>

                <div class="text header-text">
                    <span class="profession">Staff Dashboard</span>
                    <span class="name">Hello <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
            </div>
            <hr>
        </header>

        <div class="menu-bar">
            <div class="menu">
                <ul class="menu-links">
                    <li class="nav-link">
                        <button class="tablinks" id="defaultTab"><a href="staff-dashboard.php" class="tablinks">Dashboard</a></button>
                    </li>

                    <li class="nav-link">
                        <button class="tablinks"><a href="staff-userman.php" class="tablinks">User Management</a></button>
                    </li>        

                     <li class="nav-link">
                        <button class="tablinks"><a href="staff-subjman.php" class="tablinks">Subject Management</a></button>
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

  <?php
  // Get teacher count
  $result = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role_id = 3");
  if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
    $teacherCount = $row['cnt'];
  }

  // Get student count
  $result = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role_id = 4");
  if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
    $studentCount = $row['cnt'];
  }
  ?>

    </div>


       <div class="charts-container">
            <div class="chart-card">
                <h3>Student Distribution by Grade Level</h3>
                <canvas id="gradeDistributionChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h3>Subject Distribution</h3>
                <canvas id="subjectDistributionChart"></canvas>
            </div>
        </div>

        <?php
        // Fetch grade level distribution
        $gradeQuery = "SELECT grade_level, COUNT(*) as count 
                      FROM users 
                      WHERE role_id = 4 
                      GROUP BY grade_level 
                      ORDER BY grade_level";
        $gradeResult = $pdo->query($gradeQuery);
        $gradeCounts = [];
        while ($row = $gradeResult->fetch(PDO::FETCH_ASSOC)) {
            $gradeCounts[$row['grade_level']] = $row['count'];
        }

        // Fetch subject distribution
        $subjectQuery = "SELECT subjects.subject_name, COUNT(DISTINCT teacher_subjects.teacher_id) as count 
                        FROM subjects 
                        LEFT JOIN teacher_subjects ON subjects.subject_id = teacher_subjects.subject_id 
                        GROUP BY subjects.subject_id";
        $subjectResult = $pdo->query($subjectQuery);
        $subjectCounts = [];
        while ($row = $subjectResult->fetch(PDO::FETCH_ASSOC)) {
            $subjectCounts[$row['subject_name']] = $row['count'];
        }
        ?>

        <script>
            var gradeData = <?php echo json_encode($gradeCounts); ?>;
            var subjectData = <?php echo json_encode($subjectCounts); ?>;
        </script>


        

    </section>

    <script src="staff-dashboard.js"></script>

</body>
</html>
