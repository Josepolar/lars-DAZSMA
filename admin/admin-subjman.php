<?php
session_start();
require_once '../log_activity.php';
include '../Database/database.php';

// Handle Edit Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subject'])) {
    $subject_id = $_POST['edit_subject_id'];
    $subject_name = $_POST['edit_subject_name'];
    $grade_level = $_POST['edit_grade_level'];
    
    $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ?, grade_level = ? WHERE subject_id = ?");
    if ($stmt->execute([$subject_name, $grade_level, $subject_id])) {
        log_activity('Edited Subject', $subject_id);
        echo "<script>alert('Subject updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating subject');</script>";
    }
}

// Handle Delete Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subject'])) {
    $subject_id = $_POST['subject_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // First delete from teacher_subjects
        $pdo->exec("DELETE FROM teacher_subjects WHERE subject_id = $subject_id");
        
        // Then delete the subject
        $pdo->exec("DELETE FROM subjects WHERE subject_id = $subject_id");
        
        $pdo->commit();
        log_activity('Deleted Subject', $subject_id);
        echo "<script>alert('Subject deleted successfully!');</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error deleting subject');</script>";
    }
}

// Get all subjects with assigned teachers
$query = "SELECT s.subject_id, s.subject_name, s.grade_level, 
                GROUP_CONCAT(CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') as teachers
         FROM subjects s
         LEFT JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
         LEFT JOIN users u ON ts.teacher_id = u.user_id
         GROUP BY s.subject_id
         ORDER BY s.grade_level, s.subject_name";

$subjects = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/tablogo.png">
    <link rel="stylesheet" href="admin-subjman.css">
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
    </div>

    <!-- Edit Subject Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2>Edit Subject</h2>
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="edit_subject_id" id="edit_subject_id">
                
                <label>Subject Name</label>
                <input type="text" name="edit_subject_name" id="edit_subject_name" required>

                <label>Grade Level</label>
                <select name="edit_grade_level" id="edit_grade_level" required>
                    <option value="7">Grade 7</option>
                    <option value="8">Grade 8</option>
                    <option value="9">Grade 9</option>
                    <option value="10">Grade 10</option>
                </select>

                <div class="modal-footer">
                    <button type="submit" name="edit_subject" class="create-btn">Update</button>
                </div>
            </form>
        </div>
    </div>

        <div class="table-container">
            <div class="table_responsive">
                <h1>SUBJECT MANAGEMENT</h1>
                <hr>
            
    </div>

            
                <div class="table_responsive">
            <div class="table-wrapper">
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
            if ($subjects) {
                foreach ($subjects as $row) {
                    echo "<tr>";
                                        echo "<td data-label='Subject Name'>" . htmlspecialchars($row['subject_name']) . "</td>";
                                        echo "<td data-label='Grade Level'>Grade " . htmlspecialchars($row['grade_level']) . "</td>";
                                        echo "<td data-label='Assigned Teacher'>" . ($row['teachers'] ? htmlspecialchars($row['teachers']) : 'No teacher assigned') . "</td>";
                                        echo "<td data-label='Actions'>
                                                        <button class='edit-btn' onclick=\"editSubject(" . $row['subject_id'] . ", '" . htmlspecialchars($row['subject_name']) . "', '" . htmlspecialchars($row['grade_level']) . "')\">Edit</button>
                                                        <button class='delete-btn' onclick=\"deleteSubject(" . $row['subject_id'] . ")\">Delete</button>
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



                <div class="table_responsive">
                <h1></h1>
            </div>
                


        </div>
        

    </section>

    <script src="admin-subjman.js"></script>

</body>
</html>
