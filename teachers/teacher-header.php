<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    header('Location: teacher-login.php');
    exit();
}
?>
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
                    <button class="tablinks"><a href="teacher-acts.php" class="tablinks">Activities</a></button>
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

<div class="user-info">
    <span>Welcome, <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Teacher'; ?></span>
</div>