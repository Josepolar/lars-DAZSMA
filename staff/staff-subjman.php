<?php
session_start();
require_once 'log_activity.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'lars_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle Add Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subject_name = $conn->real_escape_string($_POST['subject_name']);
    $grade_level = $conn->real_escape_string($_POST['grade_level']);
    
    $sql = "INSERT INTO subjects (subject_name, grade_level) VALUES ('$subject_name', '$grade_level')";
    if ($conn->query($sql)) {
        $subject_id = $conn->insert_id;
        log_activity('Added Subject', $subject_id);
        echo "<script>alert('Subject added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding subject: " . $conn->error . "');</script>";
    }
}

// Handle Delete Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subject'])) {
    $subject_id = $conn->real_escape_string($_POST['subject_id']);
    
    // First check if subject exists
    $subjectCheck = $conn->query("SELECT subject_id FROM subjects WHERE subject_id = '$subject_id'");
    if ($subjectCheck->num_rows == 0) {
        echo "<script>alert('Error: Subject does not exist!');</script>";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First, delete any logs that reference this subject
            $sql_logs = "DELETE FROM user_logs WHERE affected_user_id = '$subject_id'";
            $conn->query($sql_logs);
            
            // Next delete from teacher_subjects
            $sql1 = "DELETE FROM teacher_subjects WHERE subject_id = '$subject_id'";
            $conn->query($sql1);
            
            // Then delete the subject
            $sql2 = "DELETE FROM subjects WHERE subject_id = '$subject_id'";
            $conn->query($sql2);
            
            log_activity('Deleted Subject', $subject_id);
            $conn->commit();
            echo "<script>alert('Subject deleted successfully!');</script>";
        } catch (Exception $e) {
            $conn->rollback();
            // Log the error for debugging
            error_log("Error deleting subject ID $subject_id: " . $e->getMessage());
            echo "<script>alert('Error deleting subject: " . $e->getMessage() . "');</script>";
        }
    }
}

// Handle Assign Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_subject'])) {
    $teacher_id = $conn->real_escape_string($_POST['teacher_id']);
    $subject_id = $conn->real_escape_string($_POST['subject_id']);
    
    // First, verify both teacher and subject exist
    $teacherCheck = $conn->query("SELECT user_id FROM users WHERE user_id = '$teacher_id' AND role_id = 3");
    $subjectCheck = $conn->query("SELECT subject_id FROM subjects WHERE subject_id = '$subject_id'");
    
    if ($teacherCheck->num_rows == 0) {
        echo "<script>alert('Error: Teacher does not exist!');</script>";
    } 
    else if ($subjectCheck->num_rows == 0) {
        echo "<script>alert('Error: Subject does not exist!');</script>";
    }
    // Check if assignment already exists
    else {
        $check = $conn->query("SELECT * FROM teacher_subjects WHERE teacher_id = '$teacher_id' AND subject_id = '$subject_id'");
        if ($check->num_rows > 0) {
            echo "<script>alert('This teacher is already assigned to this subject!');</script>";
        } else {
            $sql = "INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES ('$teacher_id', '$subject_id')";
            if ($conn->query($sql)) {
                log_activity('Assigned Subject to Teacher', $teacher_id);
                echo "<script>alert('Subject assigned successfully!');</script>";
            } else {
                echo "<script>alert('Error assigning subject: " . $conn->error . "');</script>";
            }
        }
    }
}

// Get all teachers
$teacherQuery = "SELECT user_id, first_name, last_name FROM users WHERE role_id = 3 ORDER BY last_name";
$teachers = $conn->query($teacherQuery);

// Get all subjects
$subjectQuery = "SELECT * FROM subjects ORDER BY grade_level, subject_name";
$subjects = $conn->query($subjectQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="subjman.css">
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
                        <button class="tablinks"><a href="userman.php" class="tablinks">User Management</a></button>
                    </li>        

                     <li class="nav-link">
                        <button class="tablinks"><a href="subjman.php" class="tablinks">Subject Management</a></button>
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
            <button class="stat-btn" onclick="openModal('subjectModal')">ADD SUBJECT</button>
        </div>
    </div>

    <div class="stat">
        <div class="stat-content">
            <button class="stat-btn" onclick="openModal('assignModal')">ASSIGN SUBJECT</button>
        </div>
    </div>
</div>



<!-- Add Subject Modal -->
<div id="subjectModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('subjectModal')">&times;</span>
        <h2>Add Subject</h2>
        <form method="POST" action="">
            <label>Subject Name</label>
            <input type="text" name="subject_name" placeholder="Enter subject name" required>

            <label>Grade Level</label>
            <select name="grade_level" required>
                <option value="" disabled selected>Select grade level</option>
                <option value="7">Grade 7</option>
                <option value="8">Grade 8</option>
                <option value="9">Grade 9</option>
                <option value="10">Grade 10</option>
            </select>

            <div class="modal-footer">
                <button type="submit" name="add_subject" class="create-btn">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Subject Modal -->
<div id="assignModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('assignModal')">&times;</span>
        <h2>Assign Subject</h2>
        <form method="POST" action="">
            <label>Teacher</label>
            <select name="teacher_id" required>
                <option value="" disabled selected>Select teacher</option>
                <?php while($teacher = $teachers->fetch_assoc()): ?>
                    <option value="<?= $teacher['user_id'] ?>">
                        <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Subject</label>
            <select name="subject_id" required>
                <option value="" disabled selected>Select subject</option>
                <?php 
                // Reset the subjects result pointer
                $subjects->data_seek(0);
                while($subject = $subjects->fetch_assoc()): 
                ?>
                    <option value="<?= $subject['subject_id'] ?>">
                        <?= htmlspecialchars($subject['subject_name']) ?> (Grade <?= $subject['grade_level'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>

            <div class="modal-footer">
                <button type="submit" name="assign_subject" class="create-btn">Assign</button>
            </div>
        </form>
    </div>
</div>








       <div class="table-container">
  <div class="table_responsive">
    <!-- Table -->
    <table>
      <thead>
        <tr>
          <th>Subject Name</th>
          <th>Grade Level</th>
          <th>Assigned Teacher</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // Query to get subjects with assigned teachers
        $query = "SELECT s.subject_id, s.subject_name, s.grade_level, 
                        GROUP_CONCAT(CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') as teachers
                 FROM subjects s
                 LEFT JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
                 LEFT JOIN users u ON ts.teacher_id = u.user_id
                 GROUP BY s.subject_id
                 ORDER BY s.grade_level, s.subject_name";
        
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['subject_name']) . "</td>";
                echo "<td>Grade " . htmlspecialchars($row['grade_level']) . "</td>";
                echo "<td>" . ($row['teachers'] ? htmlspecialchars($row['teachers']) : 'No teacher assigned') . "</td>";
                echo "<td class='action-btns'>
                        <form method='POST' style='display:inline;'>
                            <input type='hidden' name='subject_id' value='" . $row['subject_id'] . "'>
                            <button type='button' onclick=\"deleteSubject(" . $row['subject_id'] . ")\" class='delete-btn'>Delete</button>
                        </form>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4' style='text-align: center;'>No subjects found</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>


        

    </section>

    <!-- Hidden form for subject deletion -->
    <form id="deleteSubjectForm" method="POST" style="display:none;">
        <input type="hidden" name="subject_id" id="delete_subject_id">
        <input type="hidden" name="delete_subject" value="1">
    </form>

    <script src="subjman.js"></script>

</body>
</html>
