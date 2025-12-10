<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/tablogo.png">
    <link rel="stylesheet" href="admin-studacc.css">
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
                    <span class="name">Hello <?php $firstName = ''; if (!empty($_SESSION['first_name'])) { $firstName = $_SESSION['first_name']; } elseif (!empty($_SESSION['name'])) { $parts = explode(' ', trim($_SESSION['name'])); $firstName = $parts[0]; } echo htmlspecialchars($firstName); ?></span>
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
     require_once '../log_activity.php';
     include '../Database/database.php';

    // Handle Edit Student
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student'])) {
        $user_id = $_POST['user_id'];
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $username = $_POST['username'];
        $email = $username . "@lars.edu.ph";
        $grade = $_POST['grade'];
        
        if (!empty($_POST['password'])) {
            $password = $_POST['password'];
            $stmt = $pdo->prepare("UPDATE users SET 
                first_name = ?,
                last_name = ?,
                username = ?,
                email = ?,
                grade_level = ?,
                password = ?
                WHERE user_id = ? AND role_id = 4");
            $stmt->execute([$firstname, $lastname, $username, $email, $grade, $password, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET 
                first_name = ?,
                last_name = ?,
                username = ?,
                email = ?,
                grade_level = ?
                WHERE user_id = ? AND role_id = 4");
            $stmt->execute([$firstname, $lastname, $username, $email, $grade, $user_id]);
        }

        if ($stmt->rowCount() > 0) {
            log_activity('Edited Student', $user_id);
            echo "<script>alert('Student updated successfully!'); window.location.href=window.location.pathname;</script>";
            exit;
        } else {
            echo "<script>alert('Error updating student');</script>";
        }
    }

    // Handle Delete Student
    if (isset($_POST['delete_student'])) {
        $user_id = $_POST['user_id'];
        
        try {
            // Start a transaction
            $pdo->beginTransaction();
            
            // Log the deletion first
            log_activity('Deleted Student', $user_id);
            
            // Delete logs where the student is the user
            $stmt = $pdo->prepare("DELETE FROM user_logs WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Delete logs where the student is the affected user
            $stmt = $pdo->prepare("DELETE FROM user_logs WHERE affected_user_id = ?");
            $stmt->execute([$user_id]);
            
            // Finally delete the student
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role_id = 4");
            $stmt->execute([$user_id]);
            
            // Commit the transaction
            $pdo->commit();
            
            echo "<script>alert('Student deleted successfully!'); window.location.href=window.location.pathname;</script>";
            exit;
        } catch (Exception $e) {
            // Roll back the transaction in case of error
            $pdo->rollBack();
            echo "<script>console.error('Error: " . $e->getMessage() . "'); alert('Error deleting student');</script>";
        }
    }

    // Get total number of students
    $countQuery = "SELECT COUNT(*) as total FROM users WHERE role_id = 4";
    $countResult = $pdo->query($countQuery);
    $totalStudents = $countResult->fetch(PDO::FETCH_ASSOC)['total'];
    ?>

    <div class="stats-container">
        <div class="stat-card">
            <h3>Total Students</h3>
            <p><?php echo $totalStudents; ?></p>
        </div>
    </div>

    <div class="table-container">
        <div class="table_responsive">
            <h1>STUDENT CREDENTIALS</h1>
            <hr>
        </div>

        <div class="search-container">
            <input type="text" id="studentSearch" placeholder="Search by name, email, or ID..." class="search-input">
            <i class="fas fa-search search-icon"></i>
        </div>

        <div class="grade-filters">
            <button class="filter-btn active" data-grade="all">All Grades</button>
            <button class="filter-btn" data-grade="7">Grade 7</button>
            <button class="filter-btn" data-grade="8">Grade 8</button>
            <button class="filter-btn" data-grade="9">Grade 9</button>
            <button class="filter-btn" data-grade="10">Grade 10</button>
        </div>
            
        <div class="table_responsive">
            <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Email</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Username</th>
                        <th>Grade Level</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="student-list">
                    <?php
                    // Fetch students
                    $query = "SELECT user_id, email, first_name, last_name, username, grade_level 
                             FROM users 
                             WHERE role_id = 4 
                             ORDER BY grade_level, last_name, first_name";
                    $result = $pdo->query($query);
                    $students = $result->fetchAll(PDO::FETCH_ASSOC);

                    if ($students) {
                        foreach ($students as $row) {
                            echo "<tr data-grade='{$row['grade_level']}'>";
                            echo "<td data-label='Student ID'>" . htmlspecialchars($row['user_id']) . "</td>";
                            echo "<td data-label='Email'>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td data-label='First Name'>" . htmlspecialchars($row['first_name']) . "</td>";
                            echo "<td data-label='Last Name'>" . htmlspecialchars($row['last_name']) . "</td>";
                            echo "<td data-label='Username'>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td data-label='Grade Level'>Grade " . htmlspecialchars($row['grade_level']) . "</td>";
                            echo "<td data-label='Actions'>";
                            echo "<button class='edit-btn' onclick='openEditModal({$row['user_id']})'>Edit</button>";
                            echo "<button class='delete-btn' onclick='deleteStudent({$row['user_id']})'>Delete</button>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='no-data'>No students found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            </div>
        </div>



                <div class="table_responsive">
                <h1></h1>
            </div>
                


        </div>
        

    </section>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Student</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editStudentForm" method="POST">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <div class="input-group">
                        <label for="edit_firstname">First Name:</label>
                        <input type="text" id="edit_firstname" name="firstname" required>
                    </div>

                    <div class="input-group">
                        <label for="edit_lastname">Last Name:</label>
                        <input type="text" id="edit_lastname" name="lastname" required>
                    </div>

                    <div class="input-group">
                        <label for="edit_username">Username:</label>
                        <input type="text" id="edit_username" name="username" required>
                        <small class="input-help">Email will be automatically generated as username@lars.edu.ph</small>
                    </div>

                    <div class="input-group">
                        <label for="edit_grade">Grade Level:</label>
                        <select id="edit_grade" name="grade" required>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                            <option value="10">Grade 10</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="edit_password">Password (leave blank to keep current):</label>
                        <div class="password-container">
                            <input type="password" id="edit_password" name="password">
                            <span class="password-toggle" onclick="togglePasswordVisibility('edit_password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="edit_student" class="save-btn">Update Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .search-container {
            margin: 20px 0;
            position: relative;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .search-input {
            width: 100%;
            padding: 12px 40px 12px 20px;
            border: 2px solid #ddd;
            border-radius: 25px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            outline: none;
        }

        .search-input:focus {
            border-color: #4a90e2;
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
        }

        .input-group input, .input-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }

        .button-group {
            margin-top: 20px;
            text-align: right;
        }

        .save-btn {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-btn:hover {
            background-color: #218838;
        }

        .input-help {
            display: block;
            color: #6c757d;
            font-size: 0.85em;
            margin-top: 4px;
            font-style: italic;
        }
    </style>

    <script>
        // Search functionality
        document.getElementById('studentSearch').addEventListener('input', function(e) {
            const searchValue = e.target.value.toLowerCase();
            const studentRows = document.querySelectorAll('.student-list tr');
            const currentGrade = document.querySelector('.filter-btn.active').dataset.grade;

            studentRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const grade = row.dataset.grade;
                const matchesSearch = text.includes(searchValue);
                const matchesGrade = currentGrade === 'all' || grade === currentGrade;
                
                row.style.display = matchesSearch && matchesGrade ? '' : 'none';
            });
        });

        function openEditModal(userId) {
            // Fetch student data
            fetch(`../get_student.php?id=${userId}`)
                .then(response => response.json())
                .then(student => {
                    document.getElementById('edit_user_id').value = student.user_id;
                    document.getElementById('edit_firstname').value = student.first_name;
                    document.getElementById('edit_lastname').value = student.last_name;
                    document.getElementById('edit_username').value = student.username;
                    document.getElementById('edit_grade').value = student.grade_level;
                    document.getElementById('editStudentModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching student data');
                });
        }

        function closeEditModal() {
            document.getElementById('editStudentModal').style.display = 'none';
        }

        function deleteStudent(userId) {
            if (confirm('Are you sure you want to delete this student?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="delete_student" value="1">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="admin-studacc.js"></script>

</body>
</html>
