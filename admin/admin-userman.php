<?php

session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: admin-login.php');
    exit();
}
require_once '../log_activity.php';
include '../Database/database.php';

$message = '';

// Add/Edit user/teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role_id = ($type === 'user') ? 2 : (($type === 'teacher') ? 3 : 0);

    if (isset($_POST['edit_index'])) {
        // Update
        $user_id = intval($_POST['edit_index']);
        if ($user_id && $role_id) {
            $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, password=? WHERE user_id=? AND role_id=?");
            $stmt->execute([$firstname, $lastname, $email, $password, $user_id, $role_id]);
            // Log the edit action
            log_activity('Edited ' . ($type === 'user' ? 'Staff' : 'Teacher'), $user_id);
            header('Location: admin-userman.php');
            exit();
        }
    } else {
        // Add
        if ($firstname && $lastname && $role_id) {
            $username = strtolower($firstname . $lastname . rand(1000,9999));
            $email = $email ?: strtolower($username . '@lars.edu.ph');
            $plainPassword = $password ?: substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role_id, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $plainPassword, $email, $role_id, $firstname, $lastname]);
            $new_user_id = $pdo->lastInsertId();

            // Log the add action
            log_activity(($type === 'user') ? 'Added Staff' : 'Added Teacher', $new_user_id);

            header('Location: admin-userman.php');
            exit();
        }
    }
}

// Delete user/teacher
if (isset($_GET['delete']) && isset($_GET['type'])) {
    $type = $_GET['type'];
    $role_id = ($type === 'user') ? 2 : (($type === 'teacher') ? 3 : 0);
    $user_id = intval($_GET['delete']);
    if ($user_id && $role_id) {
        // Start transaction
        $pdo->beginTransaction();
        try {
            // First delete related records from teacher_subjects if it's a teacher
            if ($role_id === 3) {
                $stmt = $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?");
                $stmt->execute([$user_id]);
            }

            // Log the delete action before deleting user_logs and user
            // Log the delete action
            log_activity(($type === 'user') ? 'Deleted Staff' : 'Deleted Teacher', $user_id);

            // Delete logs where user is user_id
            $stmt = $pdo->prepare("DELETE FROM user_logs WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Delete logs where user is affected_user_id
            $stmt = $pdo->prepare("DELETE FROM user_logs WHERE affected_user_id = ?");
            $stmt->execute([$user_id]);

            // Finally delete the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id=? AND role_id=?");
            $stmt->execute([$user_id, $role_id]);

            // Commit the transaction
            $pdo->commit();
            header('Location: admin-userman.php');
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $message = "Error: Unable to delete user. " . $e->getMessage();
        }
    }
}

// Fetch users and teachers
$users = [];
$teachers = [];
$result = $pdo->query("SELECT * FROM users WHERE role_id=2");
$users = $result->fetchAll(PDO::FETCH_ASSOC);
$result = $pdo->query("SELECT * FROM users WHERE role_id=3");
$teachers = $result->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-userman.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
    </style>
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
                    <span class="name">Hello [NAME]</span>
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
    

    <div class="stats-container">
        <?php if ($message) { echo '<div style="color:green;">'.$message.'</div>'; } ?>
    </div>


        <div class="table-container">


 <div class="table_responsive">
    <table>
        <tr>
            <th>
                <div class="th-content">
                    <span>STAFF</span>
                    <button class="table-btn" onclick="openModal('staffModal')">ADD STAFF</button>
                </div>
            </th>
            <th>
                <div class="th-content">
                    <span>TEACHERS</span>
                    <button class="table-btn" onclick="openModal('teachersModal')">ADD TEACHERS</button>
                </div>
            </th>
        </tr>
    </table>
</div>





<!-- Staff Modal -->
<div id="staffModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('staffModal')">&times;</span>
        <h2>Add Staff</h2>
        <hr>
        <form method="post" onsubmit="return validateEmail(this)">
            <input type="hidden" name="type" value="user">
            <label>Firstname</label>
            <input type="text" name="firstname" placeholder="Enter firstname" required>

            <label>Lastname</label>
            <input type="text" name="lastname" placeholder="Enter lastname" required>

            <label>Email</label>
            <input type="email" name="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" placeholder="Will be generated as username@lars.edu.ph">
            <small style="color: #6c757d; font-size: 0.875em;">Email will be automatically generated as username@lars.edu.ph</small>
            <div class="email-error" style="color: #dc3545; display: none; font-size: 0.875em;">Please include an '@' in the email address.</div>

            <label>Password</label>
            <div class="password-container">
                <input type="password" name="password" id="staffPassword" placeholder="Enter password">
                <span class="password-toggle" onclick="togglePasswordVisibility('staffPassword')">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <div class="modal-footer">
                <button type="submit" class="create-btn">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Staff Modal -->
<div id="editStaffModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editStaffModal')">&times;</span>
        <h2>Edit Staff</h2>
        <hr>
        <form method="post" id="editStaffForm">
            <input type="hidden" name="type" value="user">
            <input type="hidden" name="edit_index" id="editStaffIndex">
            <label>Firstname</label>
            <input type="text" name="firstname" id="editStaffFirstname" required>

            <label>Lastname</label>
            <input type="text" name="lastname" id="editStaffLastname" required>

            <label>Email</label>
            <input type="email" name="email" id="editStaffEmail" required>

            <label>Password (Leave empty to keep current)</label>
            <div class="password-container">
                <input type="password" name="password" id="editStaffPassword">
                <span class="password-toggle" onclick="togglePasswordVisibility('editStaffPassword')">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <div class="modal-footer">
                <button type="submit" class="create-btn">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Teachers Modal -->
<div id="teachersModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('teachersModal')">&times;</span>
        <h2>Add Teacher</h2>
        <hr>
        <form method="post" onsubmit="return validateEmail(this)">
            <input type="hidden" name="type" value="teacher">
            <label>Firstname</label>
            <input type="text" name="firstname" placeholder="Enter firstname" required>

            <label>Lastname</label>
            <input type="text" name="lastname" placeholder="Enter lastname" required>

            <label>Email</label>
            <input type="email" name="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" placeholder="Will be generated as username@lars.edu.ph">
            <small style="color: #6c757d; font-size: 0.875em;">Email will be automatically generated as username@lars.edu.ph</small>
            <div class="email-error" style="color: #dc3545; display: none; font-size: 0.875em;">Please include an '@' in the email address.</div>

            <label>Password</label>
            <div class="password-container">
                <input type="password" name="password" id="teacherPassword" placeholder="Enter password">
                <span class="password-toggle" onclick="togglePasswordVisibility('teacherPassword')">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <div class="modal-footer">
                <button type="submit" class="create-btn">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div id="editTeacherModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editTeacherModal')">&times;</span>
        <h2>Edit Teacher</h2>
        <hr>
        <form method="post" id="editTeacherForm">
            <input type="hidden" name="type" value="teacher">
            <input type="hidden" name="edit_index" id="editTeacherIndex">
            <label>Firstname</label>
            <input type="text" name="firstname" id="editTeacherFirstname" required>

            <label>Lastname</label>
            <input type="text" name="lastname" id="editTeacherLastname" required>

            <label>Email</label>
            <input type="email" name="email" id="editTeacherEmail" required>

            <label>Password (Leave empty to keep current)</label>
            <div class="password-container">
                <input type="password" name="password" id="editTeacherPassword">
                <span class="password-toggle" onclick="togglePasswordVisibility('editTeacherPassword')">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <div class="modal-footer">
                <button type="submit" class="create-btn">Update</button>
            </div>
        </form>
    </div>
</div>






                <div class="table_creds">
    <div class="table_credss"> 
        <table>
            <tr>
                <!-- First main column -->
                <th>
                    <div class="th-content">
                        <table class="inner-table">
                            <thead>
                                <tr>
                                    <th>Firstname</th>
                                    <th>Lastname</th>
                                    <th>Email</th>
                                    <th>Password</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $i => $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['first_name']) ?></td>
                                    <td><?= htmlspecialchars($user['last_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <div class='password-field'>
                                            <input type='password' value='<?= htmlspecialchars($user['password']) ?>' readonly>
                                            <button class='toggle-password' onclick='toggleTablePassword(this)'>
                                                <i class='fas fa-eye'></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn edit-btn" onclick="editEntry('user', <?= $user['user_id'] ?>)">Edit</button>
                                        <button class="btn delete-btn" onclick="confirmDelete('user', <?= $user['user_id'] ?>)">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </th>

                <!-- Second main column -->
                <th>
                    <div class="th-content">
                        <table class="inner-table">
                            <thead>
                                <tr>
                                    <th>Firstname</th>
                                    <th>Lastname</th>
                                    <th>Email</th>
                                    <th>Password</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $i => $teacher): ?>
                                <tr>
                                    <td><?= htmlspecialchars($teacher['first_name']) ?></td>
                                    <td><?= htmlspecialchars($teacher['last_name']) ?></td>
                                    <td><?= htmlspecialchars($teacher['email']) ?></td>
                                    <td>
                                        <div class='password-field'>
                                            <input type='password' value='<?= htmlspecialchars($teacher['password']) ?>' readonly>
                                            <button class='toggle-password' onclick='toggleTablePassword(this)'>
                                                <i class='fas fa-eye'></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn edit-btn" onclick="editEntry('teacher', <?= $teacher['user_id'] ?>)">Edit</button>
                                        <button class="btn delete-btn" onclick="confirmDelete('teacher', <?= $teacher['user_id'] ?>)">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </th>
            </tr>
        </table>
    </div>
</div>
                


        </div>
        

    </section>

    <script>
    // Data for instant edit modal
    var users = <?php echo json_encode($users); ?>;
    var teachers = <?php echo json_encode($teachers); ?>;

    function confirmDelete(type, index) {
        if (confirm('Are you sure you want to delete this ' + (type === 'user' ? 'staff' : 'teacher') + '?')) {
            try {
                window.location.href = 'admin-userman.php?delete=' + index + '&type=' + type;
            } catch (error) {
                console.error("Error navigating to delete URL:", error);
                alert("Error trying to delete. Please try again.");
            }
        }
    }

    function editEntry(type, userId) {
        if (type === 'user') {
            var user = users.find(function(u) { return u.user_id == userId; });
            if (!user) return;
            document.getElementById('editStaffIndex').value = user.user_id;
            document.getElementById('editStaffFirstname').value = user.first_name;
            document.getElementById('editStaffLastname').value = user.last_name;
            document.getElementById('editStaffEmail').value = user.email;
            document.getElementById('editStaffPassword').value = '';
            document.getElementById('editStaffModal').style.display = 'block';
        } else {
            var teacher = teachers.find(function(t) { return t.user_id == userId; });
            if (!teacher) return;
            document.getElementById('editTeacherIndex').value = teacher.user_id;
            document.getElementById('editTeacherFirstname').value = teacher.first_name;
            document.getElementById('editTeacherLastname').value = teacher.last_name;
            document.getElementById('editTeacherEmail').value = teacher.email;
            document.getElementById('editTeacherPassword').value = '';
            document.getElementById('editTeacherModal').style.display = 'block';
        }
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
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
    </script>
    <script src="admin-userman.js"></script>
    <script>
    function validateEmail(form) {
        const emailInput = form.querySelector('input[type="email"]');
        const errorDiv = form.querySelector('.email-error');
        
        if (emailInput.value && !emailInput.value.includes('@')) {
            errorDiv.style.display = 'block';
            emailInput.focus();
            return false;
        }
        
        errorDiv.style.display = 'none';
        return true;
    }
    </script>

</body>
</html>
