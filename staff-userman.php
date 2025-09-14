<?php
session_start();
require_once 'log_activity.php';
// Redirect to login if session is missing or expired
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header('Location: staff-login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="staff-userman.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .user-credential {
            font-family: 'Consolas', monospace;
            background-color: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            color: #495057;
            font-size: 0.9em;
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .input-help {
            display: block;
            color: #6c757d;
            font-size: 0.85em;
            margin-top: 4px;
            font-style: italic;
        }
    </style>
    <title>Staff Dashboard</title>
</head>
<body>
    <nav class="sidebar">
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="assets/larslogo.png" alt="logo">
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
                        <button class="tablinks"><a href="logout.php" class="tablinks">Logout</a></button>
                    </li>
            </div>
        </div>
    </nav>


    

    <?php
    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'lars_db');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Check if grade_level column exists, if not add it
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'grade_level'");
    if ($checkColumn->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN grade_level VARCHAR(2) DEFAULT NULL");
    }

    // Handle Add Teacher
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
        $fname = $conn->real_escape_string($_POST['teacher_fname'] ?? '');
        $lname = $conn->real_escape_string($_POST['teacher_lname'] ?? '');
        $username = $conn->real_escape_string($_POST['teacher_username'] ?? '');
        // Create email based on username
        $email = $username . "@lars.edu.ph";
        $password = $_POST['teacher_password'] ?? '';  // Store password as plain text
        $role_id = 3; // Teacher

        // Check if username already exists
        $check = $conn->query("SELECT user_id FROM users WHERE username = '$username'");
        if ($check && $check->num_rows > 0) {
            echo "<script>alert('Username already exists. Please choose another.');</script>";
        } else {
            $sql = "INSERT INTO users (first_name, last_name, username, email, password, role_id) 
                    VALUES ('$fname', '$lname', '$username', '$email', '$password', $role_id)";
            if ($conn->query($sql)) {
                log_activity('Added Teacher', $conn->insert_id);
                echo "<script>alert('Teacher added successfully!'); window.location.href=window.location.pathname;</script>";
                exit;
            } else {
                echo "<script>alert('Error adding teacher: {$conn->error}');</script>";
            }
        }
    }

    // Handle Delete User
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
        $user_id = $conn->real_escape_string($_POST['user_id']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First delete from teacher_subjects
            $sql_subjects = "DELETE FROM teacher_subjects WHERE teacher_id = '$user_id'";
            $conn->query($sql_subjects);

            // Delete logs where user is user_id
            $sql_logs1 = "DELETE FROM user_logs WHERE user_id = '$user_id'";
            $conn->query($sql_logs1);

            // Delete logs where user is affected_user_id
            $sql_logs2 = "DELETE FROM user_logs WHERE affected_user_id = '$user_id'";
            $conn->query($sql_logs2);

            // Then delete the user
            $sql_user = "DELETE FROM users WHERE user_id = '$user_id'";
            $conn->query($sql_user);
            log_activity('Deleted User', $user_id);
            // If we get here, commit the transaction
            $conn->commit();
            echo "<script>alert('User deleted successfully!'); window.location.href=window.location.pathname;</script>";
            exit;
        } catch (Exception $e) {
            // If there's an error, rollback the transaction
            $conn->rollback();
            echo "<script>alert('Error deleting user: " . $conn->error . "');</script>";
        }
    }

    // Handle Edit User
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
        $user_id = $conn->real_escape_string($_POST['user_id']);
        $fname = $conn->real_escape_string($_POST['edit_fname']);
        $lname = $conn->real_escape_string($_POST['edit_lname']);
        $username = $conn->real_escape_string($_POST['edit_username']);
        // Update email to match username format
        $email = $username . "@lars.edu.ph";
        $grade = isset($_POST['edit_grade']) ? $conn->real_escape_string($_POST['edit_grade']) : null;
        
        // Check if username already exists for other users
        $check = $conn->query("SELECT user_id FROM users WHERE username = '$username' AND user_id != '$user_id'");
        if ($check && $check->num_rows > 0) {
            echo "<script>alert('Username already exists. Please choose another.');</script>";
        } else {
            $password_sql = "";
            if (!empty($_POST['edit_password'])) {
                $password = $_POST['edit_password'];  // Store password as plain text
                $password_sql = ", password = '$password'";
            }
            
            // Get user's role
            $role_query = "SELECT role_id FROM users WHERE user_id = '$user_id'";
            $role_result = $conn->query($role_query);
            $role_row = $role_result->fetch_assoc();
            $is_student = $role_row['role_id'] == 4;
            
            $grade_sql = $is_student ? ", grade_level = " . ($grade ? "'$grade'" : "NULL") : "";
            
            $sql = "UPDATE users SET first_name = '$fname', last_name = '$lname', 
                    username = '$username', email = '$email' $password_sql $grade_sql 
                    WHERE user_id = '$user_id'";
            
            if ($conn->query($sql)) {
                log_activity('Edited User', $user_id);
                echo "<script>alert('User updated successfully!'); window.location.href=window.location.pathname;</script>";
                exit;
            } else {
                echo "<script>alert('Error updating user: {$conn->error}');</script>";
            }
        }
    }

                    // Handle Bulk Student Upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_add_students'])) {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, "r");
            
            // Read and validate header row
            $header = fgetcsv($handle);
            $expected_headers = ["First Name", "Last Name", "Username", "Email", "Password", "Grade Level"];
            if ($header !== $expected_headers) {
                echo "<script>alert('Invalid CSV format. Please use the provided template.');</script>";
                exit;
            }
            
            $success_count = 0;
            $error_count = 0;
            $errors = [];            // Start transaction
            $conn->begin_transaction();
            
            try {
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 6) {
                        $fname = $conn->real_escape_string($data[0]);
                        $lname = $conn->real_escape_string($data[1]);
                        $username = $conn->real_escape_string($data[2]);
                        $email = $conn->real_escape_string($data[3]);
                        $password = $data[4];  // Store password as plain text
                        $grade = $conn->real_escape_string($data[5]);
                        $role_id = 4;
                        
                        // Validate grade level
                        if (!in_array($grade, ['7', '8', '9', '10'])) {
                            $errors[] = "Invalid grade level '$grade' for student $fname $lname. Must be between 7-10.";
                            $error_count++;
                            continue;
                        }
                        
                        // Check if username exists
                        $check = $conn->query("SELECT user_id FROM users WHERE username = '$username'");
                        if ($check && $check->num_rows > 0) {
                            $errors[] = "Username '$username' already exists";
                            $error_count++;
                            continue;
                        }
                        
                        $sql = "INSERT INTO users (first_name, last_name, username, email, password, role_id, grade_level) 
                                VALUES ('$fname', '$lname', '$username', '$email', '$password', $role_id, '$grade')";
                        if ($conn->query($sql)) {
                            $success_count++;
                        } else {
                            $errors[] = "Error adding student $fname $lname: " . $conn->error;
                            $error_count++;
                        }
                    }
                }
                
                fclose($handle);
                
                if ($success_count > 0) {
                    $conn->commit();
                    $message = "$success_count students added successfully.";
                    if ($error_count > 0) {
                        $message .= "\n$error_count errors occurred:\n" . implode("\n", $errors);
                    }
                    echo "<script>alert('$message'); window.location.href=window.location.pathname;</script>";
                    exit;
                } else {
                    throw new Exception("No students were added. Errors:\n" . implode("\n", $errors));
                }
            } catch (Exception $e) {
                $conn->rollback();
                echo "<script>alert('Error processing CSV file: " . str_replace("'", "\\'", $e->getMessage()) . "');</script>";
            }
        } else {
            echo "<script>alert('Please upload a valid CSV file.');</script>";
        }
    }

    // Handle Add Student
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
        $fname = $conn->real_escape_string($_POST['student_fname'] ?? '');
        $lname = $conn->real_escape_string($_POST['student_lname'] ?? '');
        $username = $conn->real_escape_string($_POST['student_username'] ?? '');
        // Create email based on username
        $email = $username . "@lars.edu.ph";
        $password = $_POST['student_password'] ?? '';  // Store password as plain text
        $grade = $conn->real_escape_string($_POST['student_grade'] ?? '');
        $role_id = 4; // Student
        
        // Check if username already exists
        $check = $conn->query("SELECT user_id FROM users WHERE username = '$username'");
        if ($check && $check->num_rows > 0) {
            echo "<script>alert('Username already exists. Please choose another.');</script>";
        } else {
            $sql = "INSERT INTO users (first_name, last_name, username, email, password, role_id, grade_level) 
                    VALUES ('$fname', '$lname', '$username', '$email', '$password', $role_id, '$grade')";
            if ($conn->query($sql)) {
                log_activity('Added Student', $conn->insert_id);
                echo "<script>alert('Student added successfully!'); window.location.href=window.location.pathname;</script>";
                exit;
            } else {
                echo "<script>alert('Error adding student: {$conn->error}');</script>";
            }
        }
    }

    // Get teacher count
    $teacherCount = 0;
    $result = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role_id = 3");
    if ($result && $row = $result->fetch_assoc()) {
        $teacherCount = $row['cnt'];
    }

    // Get student count
    $studentCount = 0;
    $result = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role_id = 4");
    if ($result && $row = $result->fetch_assoc()) {
        $studentCount = $row['cnt'];
    }
    ?>

    <section class="home" id="home-section">
    





<!-- Teacher Modal -->
<div id="teacherModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('teacherModal')">&times;</span>
        <h2>Add Teacher</h2>
        <HR>
        <form method="POST" action="" autocomplete="off">
            <label>First Name</label>
            <input type="text" name="teacher_fname" placeholder="Enter first name" required>

            <label>Last Name</label>
            <input type="text" name="teacher_lname" placeholder="Enter last name" required>

            <label>Username</label>
            <input type="text" name="teacher_username" placeholder="Enter username" required>
            <small class="input-help">Email will be automatically generated as username@lars.edu.ph</small>

            <label>Password</label>
            <div class="password-container">
                <input type="password" name="teacher_password" id="teacher_password" placeholder="Enter password" required>
                <span class="password-toggle" onclick="togglePasswordVisibility('teacher_password')">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <BR>
            <div class="modal-footer">
                <button type="submit" name="add_teacher" class="create-btn">Create</button>
            </div>
        </form>
    </div>
</div>


<!-- Student Modal -->
<div id="studentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Student</h2>
            <span class="close" onclick="closeModal('studentModal')">&times;</span>
        </div>
        
        <div class="tab-buttons">
            <button type="button" class="tab-btn active" onclick="switchTab('single')">Add Single Student</button>
            <button type="button" class="tab-btn" onclick="switchTab('bulk')">Bulk Upload</button>
        </div>
        
        <div class="modal-body">
            <!-- Single Student Form -->
            <form id="singleStudentForm" method="POST" action="" autocomplete="off">
                <div class="input-group">
                    <label for="student_fname">First Name</label>
                    <input type="text" id="student_fname" name="student_fname" placeholder="Enter first name" required>
                </div>

                <div class="input-group">
                    <label for="student_lname">Last Name</label>
                    <input type="text" id="student_lname" name="student_lname" placeholder="Enter last name" required>
                </div>

                <div class="input-group">
                    <label for="student_grade">Grade Level</label>
                    <select id="student_grade" name="student_grade" required>
                        <option value="">Select Grade Level</option>
                        <option value="7">Grade 7</option>
                        <option value="8">Grade 8</option>
                        <option value="9">Grade 9</option>
                        <option value="10">Grade 10</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="student_username">Username</label>
                    <input type="text" id="student_username" name="student_username" placeholder="Enter username" required>
                    <small class="input-help">Email will be automatically generated as username@lars.edu.ph</small>
                </div>

                <div class="input-group">
                    <label for="student_password">Password</label>
                    <div class="password-container">
                        <input type="password" id="student_password" name="student_password" placeholder="Enter password" required>
                        <span class="password-toggle" onclick="togglePasswordVisibility('student_password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" name="add_student" class="create-btn">Add Student</button>
                </div>
            </form>

            <!-- Bulk Upload Form -->
            <form id="bulkStudentForm" method="POST" action="" enctype="multipart/form-data" style="display: none;">
                <div class="csv-instructions">
                    <h4>Instructions for Bulk Upload</h4>
                    <ol>
                        <li>Download CSV template for:
                            <div class="template-buttons">
                                <button onclick="downloadTemplate(7); return false;">Grade 7</button>
                                <button onclick="downloadTemplate(8); return false;">Grade 8</button>
                                <button onclick="downloadTemplate(9); return false;">Grade 9</button>
                                <button onclick="downloadTemplate(10); return false;">Grade 10</button>
                            </div>
                        </li>
                        <li>Fill in the student details in the CSV format</li>
                        <li>Upload the completed CSV file below</li>
                    </ol>
                    <p class="format-note">Required CSV Format: First Name, Last Name, Username, Email, Password, Grade Level (7-10)</p>
                </div>
                
                <div class="input-group file-upload">
                    <label for="csvFile">Select CSV File</label>
                    <input type="file" name="csv_file" id="csvFile" accept=".csv" required>
                </div>

                <div class="button-group">
                    <button type="submit" name="bulk_add_students" class="create-btn">Upload Students</button>
                </div>
            </form>
        </div>
    </div>
</div>



       <div class="table-container" style="display: flex; gap: 20px;">
    <!-- Teachers Table -->
    <div class="table_responsive" style="flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div class="controls">
            <h3 style="cursor: pointer;" onclick="openModal('teacherTableModal')">Registered Teachers (<?= $teacherCount ?>)</h3>
            <button class="stat-btn" onclick="openModal('teacherModal')">ADD TEACHER</button>
        </div>
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>Teacher Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch teachers
                $teacherQuery = "SELECT user_id, first_name, last_name, email, username, password FROM users WHERE role_id = 3 ORDER BY last_name";
                $teacherResult = $conn->query($teacherQuery);
                if ($teacherResult && $teacherResult->num_rows > 0) {
                    while ($row = $teacherResult->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td><div class='password-field'>
                                <input type='password' value='" . htmlspecialchars($row['password']) . "' readonly>
                                <button class='toggle-password' onclick='toggleTablePassword(this)'>
                                    <i class='fas fa-eye'></i>
                                </button>
                              </div></td>";
                        echo "<td style='white-space: nowrap;'>
                                <button onclick=\"openEditModal('teacher', {$row['user_id']})\" class='action-btn edit-btn'>Edit</button>
                                <button onclick=\"if(confirm('Are you sure you want to delete this teacher?')) deleteUser('teacher', {$row['user_id']})\" class='action-btn delete-btn'>Delete</button>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align: center;'>No teachers registered</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div> 

    <!-- Students Table -->
    <div class="table_responsive" style="flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div class="controls">
            <h3 style="cursor: pointer;" onclick="openModal('studentTableModal')">Registered Students (<?= $studentCount ?>)</h3>
            <button class="stat-btn" onclick="openModal('studentModal')">ADD STUDENT</button>
        </div>
        <div class="grade-filters">
            <button class="filter-btn active" data-grade="all">All Grades</button>
            <button class="filter-btn" data-grade="7">Grade 7</button>
            <button class="filter-btn" data-grade="8">Grade 8</button>
            <button class="filter-btn" data-grade="9">Grade 9</button>
            <button class="filter-btn" data-grade="10">Grade 10</button>
        </div>
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Grade</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="student-list">
                <?php
                // Fetch students
                $studentQuery = "SELECT user_id, first_name, last_name, username, grade_level, password FROM users WHERE role_id = 4 ORDER BY grade_level, last_name";
                $studentResult = $conn->query($studentQuery);
                if ($studentResult && $studentResult->num_rows > 0) {
                    while ($row = $studentResult->fetch_assoc()) {
                        // Generate email from username
                        $email = $row['username'] . "@lars.edu.ph";
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>";
                        echo "<td>Grade " . htmlspecialchars($row['grade_level']) . "</td>";
                        echo "<td><span class='user-credential'>" . htmlspecialchars($row['username']) . "</span></td>";
                        echo "<td><span class='user-credential'>" . htmlspecialchars($email) . "</span></td>";
                        echo "<td><div class='password-field'>
                                <input type='password' value='" . htmlspecialchars($row['password']) . "' readonly>
                                <button class='toggle-password' onclick='toggleTablePassword(this)'>
                                    <i class='fas fa-eye'></i>
                                </button>
                              </div></td>";
                        echo "<td style='white-space: nowrap;'>
                                <button onclick=\"openEditModal('student', {$row['user_id']})\" class='action-btn edit-btn'>Edit</button>
                                <button onclick=\"if(confirm('Are you sure you want to delete this student?')) deleteUser('student', {$row['user_id']})\" class='action-btn delete-btn'>Delete</button>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align: center;'>No students registered</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>


        

    </section>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editUserForm" method="POST">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="input-group">
                        <label for="edit_fname">First Name:</label>
                        <input type="text" id="edit_fname" name="edit_fname" required>
                    </div>
                    <div class="input-group">
                        <label for="edit_lname">Last Name:</label>
                        <input type="text" id="edit_lname" name="edit_lname" required>
                    </div>
                    <div class="input-group">
                        <label for="edit_username">Username:</label>
                        <input type="text" id="edit_username" name="edit_username" required>
                    </div>
                    <div class="input-group">
                        <label for="edit_email">Email:</label>
                        <input type="email" id="edit_email" name="edit_email" required>
                    </div>
                    <div class="input-group">
                        <label for="edit_password">Password (leave blank to keep current):</label>
                        <input type="password" id="edit_password" name="edit_password">
                    </div>
                    <div class="input-group student-grade-field" style="display: none;">
                        <label for="edit_grade">Grade Level:</label>
                        <select id="edit_grade" name="edit_grade">
                            <option value="">Select Grade Level</option>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                            <option value="10">Grade 10</option>
                        </select>
                    </div>
                    <div class="button-group">
                        <button type="submit" name="edit_user">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="staff-userman.js"></script>
    <script>
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

        function toggleTablePassword(button) {
            const passwordInput = button.parentElement.querySelector('input');
            const icon = button.querySelector('i');
            
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

        function searchTeachers(searchText) {
            const tableBody = document.querySelector('#teacherTableModal tbody');
            const rows = tableBody.getElementsByTagName('tr');
            searchText = searchText.toLowerCase();
            
            let hasResults = false;

            for (let row of rows) {
                const name = row.cells[0].textContent.toLowerCase();
                const username = row.cells[2].textContent.toLowerCase();
                
                if (name.includes(searchText) || username.includes(searchText)) {
                    row.style.display = '';
                    hasResults = true;
                } else {
                    row.style.display = 'none';
                }
            }

            // Show/hide no results message
            let noResults = tableBody.querySelector('.no-results');
            if (!hasResults) {
                if (!noResults) {
                    noResults = document.createElement('tr');
                    noResults.className = 'no-results';
                    noResults.innerHTML = '<td colspan="5">No matching teachers found</td>';
                    tableBody.appendChild(noResults);
                }
            } else if (noResults) {
                noResults.remove();
            }
        }

        function searchStudents(searchText) {
            const tableBody = document.querySelector('#studentTableModal tbody');
            const rows = tableBody.getElementsByTagName('tr');
            searchText = searchText.toLowerCase();
            
            let hasResults = false;

            for (let row of rows) {
                const name = row.cells[0].textContent.toLowerCase();
                const grade = row.cells[1].textContent.toLowerCase();
                const username = row.cells[2].textContent.toLowerCase();
                
                if (name.includes(searchText) || grade.includes(searchText) || username.includes(searchText)) {
                    row.style.display = '';
                    hasResults = true;
                } else {
                    row.style.display = 'none';
                }
            }

            // Show/hide no results message
            let noResults = tableBody.querySelector('.no-results');
            if (!hasResults) {
                if (!noResults) {
                    noResults = document.createElement('tr');
                    noResults.className = 'no-results';
                    noResults.innerHTML = '<td colspan="6">No matching students found</td>';
                    tableBody.appendChild(noResults);
                }
            } else if (noResults) {
                noResults.remove();
            }
        }
    </script>
    <style>
        .password-field {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .password-field input {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
            font-family: 'Consolas', monospace;
        }
        .toggle-password {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #6c757d;
        }
        .toggle-password:hover {
            color: #495057;
        }
        .table-modal .modal-content {
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .search-container {
            margin: 20px 0;
            padding: 0 10px;
        }

        .search-container input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .search-container input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .no-results {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }
        
        .modal h3:hover {
            color: #007bff;
            text-decoration: underline;
        }

        .table-modal table {
            width: 100%;
            margin-top: 20px;
        }

        .modal-table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        /* Add a pointing cursor to the clickable headers */
        h3[onclick] {
            cursor: pointer;
        }
        h3[onclick]:hover {
            color: #007bff;
            text-decoration: underline;
        }
    </style>

    <!-- Teacher Table Modal -->
    <div id="teacherTableModal" class="modal table-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Registered Teachers</h2>
                <span class="close" onclick="closeModal('teacherTableModal')">&times;</span>
            </div>
            <div class="search-container">
                <input type="text" id="teacherSearch" placeholder="Search by name..." onkeyup="searchTeachers(this.value)">
            </div>
            <div class="modal-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Teacher Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Reset the teacher result pointer
                        $teacherResult->data_seek(0);
                        if ($teacherResult && $teacherResult->num_rows > 0) {
                            while ($row = $teacherResult->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td><div class='password-field'>
                                        <input type='password' value='" . htmlspecialchars($row['password']) . "' readonly>
                                        <button class='toggle-password' onclick='toggleTablePassword(this)'>
                                            <i class='fas fa-eye'></i>
                                        </button>
                                      </div></td>";
                                echo "<td style='white-space: nowrap;'>
                                        <button onclick=\"openEditModal('teacher', {$row['user_id']})\" class='action-btn edit-btn'>Edit</button>
                                        <button onclick=\"if(confirm('Are you sure you want to delete this teacher?')) deleteUser('teacher', {$row['user_id']})\" class='action-btn delete-btn'>Delete</button>
                                      </td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Student Table Modal -->
    <div id="studentTableModal" class="modal table-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Registered Students</h2>
                <span class="close" onclick="closeModal('studentTableModal')">&times;</span>
            </div>
            <div class="search-container">
                <input type="text" id="studentSearch" placeholder="Search by name or grade level..." onkeyup="searchStudents(this.value)">
            </div>
            <div class="grade-filters" style="margin: 20px 0;">
                <button class="filter-btn active" data-grade="all">All Grades</button>
                <button class="filter-btn" data-grade="7">Grade 7</button>
                <button class="filter-btn" data-grade="8">Grade 8</button>
                <button class="filter-btn" data-grade="9">Grade 9</button>
                <button class="filter-btn" data-grade="10">Grade 10</button>
            </div>
            <div class="modal-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Grade</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Password</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="student-list">
                        <?php
                        // Reset the student result pointer
                        $studentResult->data_seek(0);
                        if ($studentResult && $studentResult->num_rows > 0) {
                            while ($row = $studentResult->fetch_assoc()) {
                                $email = $row['username'] . "@lars.edu.ph";
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>";
                                echo "<td>Grade " . htmlspecialchars($row['grade_level']) . "</td>";
                                echo "<td><span class='user-credential'>" . htmlspecialchars($row['username']) . "</span></td>";
                                echo "<td><span class='user-credential'>" . htmlspecialchars($email) . "</span></td>";
                                echo "<td><div class='password-field'>
                                        <input type='password' value='" . htmlspecialchars($row['password']) . "' readonly>
                                        <button class='toggle-password' onclick='toggleTablePassword(this)'>
                                            <i class='fas fa-eye'></i>
                                        </button>
                                      </div></td>";
                                echo "<td style='white-space: nowrap;'>
                                        <button onclick=\"openEditModal('student', {$row['user_id']})\" class='action-btn edit-btn'>Edit</button>
                                        <button onclick=\"if(confirm('Are you sure you want to delete this student?')) deleteUser('student', {$row['user_id']})\" class='action-btn delete-btn'>Delete</button>
                                      </td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
 