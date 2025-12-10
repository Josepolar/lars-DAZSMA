<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: admin-login.php');
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
    <link rel="stylesheet" href="admin-usrlog.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?php echo time(); ?>">
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
                        <button class="tablinks"><a href="admin-userman.php" class="tablinks">User Management</a></button>
                    </li>        
                    
                    <li class="nav-link">
                        <button class="tablinks"><a href="admin-studacc.php" class="tablinks">Student Accounts</a></button>
                    </li> 

                     <li class="nav-link">
                        <button class="tablinks"><a href="admin-subjman.php" class="tablinks">Subject Management</a></button>
                    </li> 

                     <li class="nav-link">
                        <button class="tablinks"><a href="admin-usrlog.php" class="tablinks">User Logs</a></button>
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
    <?php
    // Database connection
    include '../Database/database.php';

    // Get total logs count
    $totalQuery = "SELECT COUNT(*) as total FROM user_logs";
    $totalResult = $pdo->query($totalQuery);
    $totalLogs = $totalResult->fetch(PDO::FETCH_ASSOC)['total'];

    // Get today's logs count
    $todayQuery = "SELECT COUNT(*) as today FROM user_logs WHERE DATE(action_timestamp) = CURDATE()";
    $todayResult = $pdo->query($todayQuery);
    $todayLogs = $todayResult->fetch(PDO::FETCH_ASSOC)['today'];
    ?>

    <div class="stats-container">
        <div class="stat-card">
            <h3>Total Logs</h3>
            <p><?php echo $totalLogs; ?></p>
        </div>
        <div class="stat-card">
            <h3>Today's Activity</h3>
            <p><?php echo $todayLogs; ?></p>
        </div>
    </div>

    <div class="table-container">
        <div class="table_responsive">
            <h1>USER LOGS</h1>
            <hr>
            <div class="filters">
                <div class="search-container">
                    <input type="text" id="searchLogs" placeholder="Search logs..." class="search-input">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <div class="date-filter">
                    <select id="timeFilter">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                </div>
                <div class="user-filter">
                    <select id="roleFilter">
                        <option value="all">All Users</option>
                        <option value="1">Admin</option>
                        <option value="2">Staff</option>
                        <option value="3">Teacher</option>
                        <option value="4">Student</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="logs-container">
            <div class="timeline">
                <?php
                $query = "SELECT ul.*, 
                         u1.username, u1.first_name, u1.last_name, r1.role_name,
                         u2.first_name as affected_first_name, u2.last_name as affected_last_name,
                         r2.role_name as affected_role_name
                         FROM user_logs ul 
                         JOIN users u1 ON ul.user_id = u1.user_id 
                         JOIN roles r1 ON u1.role_id = r1.role_id 
                         LEFT JOIN users u2 ON ul.affected_user_id = u2.user_id
                         LEFT JOIN roles r2 ON u2.role_id = r2.role_id
                         ORDER BY ul.action_timestamp DESC 
                         LIMIT 100";
                $result = $pdo->query($query);

                $currentDate = '';
                if ($result) {
                    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($rows as $row) {
                        $logDate = date('Y-m-d', strtotime($row['action_timestamp']));
                        $logTime = date('h:i:s A', strtotime($row['action_timestamp']));
                        
                        if ($currentDate != $logDate) {
                            if ($currentDate != '') {
                                echo "</div>"; // Close previous date group
                            }
                            $currentDate = $logDate;
                            echo "<div class='date-group'>";
                            echo "<h3>" . date('F d, Y', strtotime($logDate)) . "</h3>";
                        }
                        ?>
                        <div class="log-entry" data-role="<?php echo $row['role_name']; ?>">
                            <div class="log-time"><?php echo $logTime; ?></div>
                            <div class="log-content">
                                <span class="user-name"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                                <span class="user-role"><?php echo htmlspecialchars($row['role_name']); ?></span>
                                <span class="log-action">
                                    <?php
                                    if ($row['action'] == 'Login' || $row['action'] == 'Logout') {
                                        echo htmlspecialchars($row['action']);
                                    } else if ($row['affected_first_name'] && $row['affected_last_name']) {
                                        echo htmlspecialchars($row['action'] . ' ' . 
                                             $row['affected_role_name'] . ': ' . 
                                             $row['affected_first_name'] . ' ' . 
                                             $row['affected_last_name']);
                                    } else {
                                        echo htmlspecialchars($row['action']);
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <?php
                    }
                    echo "</div>"; // Close last date group
                } else {
                    echo "<p class='no-logs'>No logs found</p>";
                }
                ?>
            </div>
        </div>

            <!-- Daily Logs Modal -->
            <div id="logsModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2 id="selectedDate">User Logs for </h2>
                    <div class="table_responsive">
                        <div class="table-wrapper">
                        <table id="dailyLogsTable">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody id="dailyLogsBody">
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
</div>
                


        </div>
        

    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchLogs');
            const timeFilter = document.getElementById('timeFilter');
            const roleFilter = document.getElementById('roleFilter');

            function filterLogs() {
                const searchTerm = searchInput.value.toLowerCase();
                const timeValue = timeFilter.value;
                const roleValue = roleFilter.value.toLowerCase();
                const logEntries = document.querySelectorAll('.log-entry');

                logEntries.forEach(entry => {
                    const content = entry.textContent.toLowerCase();
                    const role = entry.dataset.role.toLowerCase();
                    let timeMatch = true;

                    // Time filtering logic
                    if (timeValue !== 'all') {
                        const entryDate = new Date(entry.closest('.date-group').querySelector('h3').textContent);
                        const today = new Date();
                        
                        switch(timeValue) {
                            case 'today':
                                timeMatch = entryDate.toDateString() === today.toDateString();
                                break;
                            case 'yesterday':
                                const yesterday = new Date(today);
                                yesterday.setDate(yesterday.getDate() - 1);
                                timeMatch = entryDate.toDateString() === yesterday.toDateString();
                                break;
                            case 'week':
                                const weekAgo = new Date(today);
                                weekAgo.setDate(weekAgo.getDate() - 7);
                                timeMatch = entryDate >= weekAgo;
                                break;
                            case 'month':
                                const monthAgo = new Date(today);
                                monthAgo.setMonth(monthAgo.getMonth() - 1);
                                timeMatch = entryDate >= monthAgo;
                                break;
                        }
                    }

                    const matchesSearch = content.includes(searchTerm);
                    const matchesRole = roleValue === 'all' || role.includes(roleValue);

                    entry.style.display = matchesSearch && matchesRole && timeMatch ? '' : 'none';
                });

                // Show/hide date groups based on visible entries
                document.querySelectorAll('.date-group').forEach(group => {
                    const hasVisibleEntries = Array.from(group.querySelectorAll('.log-entry'))
                        .some(entry => entry.style.display !== 'none');
                    group.style.display = hasVisibleEntries ? '' : 'none';
                });
            }

            searchInput.addEventListener('input', filterLogs);
            timeFilter.addEventListener('change', filterLogs);
            roleFilter.addEventListener('change', filterLogs);
        });
    </script>
    <script src="admin-usrlog.js"></script>
</body>
</html>
