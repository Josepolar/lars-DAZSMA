<?php
session_start();
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
    <link rel="stylesheet" href="teacher-studs.css">
    <title>Teachers Dashboard</title>
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
                        <button class="tablinks"><a href="../logout.php" class="tablinks">Logout</a></button>
                    </li>
            </div>
        </div>
    </nav>

   <section class="home" id="home-section">

    <div class="stats-container">
        
    </div>

    <div class="table-container">
        <div class="table_responsive">
            <h1>CLASS RECORD</h1>
            <hr>
        </div>

        <div class="subject-filters">
    
    <select id="subject-select" name="subject">
        <option value="" disabled selected>Select Subject</option>
        <!-- Subjects will be loaded here dynamically -->
    </select>
</div>

            
        <div class="table_responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Grade Level</th>
                        <th>Total Points</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <tr>
                        <td colspan="3" style="text-align: center; color: #666;">
                            Select a subject to view students
                        </td>
                    </tr>
                </tbody>

               
            </table>
        </div>



                <div class="table_responsive">
                <h1></h1>
            </div>
                


        </div>
        

    </section>


    <script src="teacher-studs.js"></script>
    <script>
    let teacherSubjects = [];
    
    // Fetch subjects for this teacher and populate the dropdown
    document.addEventListener('DOMContentLoaded', function() {
        fetch('teacher-activities-backend.php?action=get_teacher_subjects')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.subjects) {
                    teacherSubjects = data.subjects;
                    const select = document.getElementById('subject-select');
                    // Remove all except the first option
                    while (select.options.length > 1) select.remove(1);
                    data.subjects.forEach(subj => {
                        const opt = document.createElement('option');
                        opt.value = subj.subject_id;
                        opt.textContent = subj.subject_name + ' (Grade ' + subj.grade_level + ')';
                        select.appendChild(opt);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading subjects:', error);
            });
            
        // Add event listener for subject selection
        document.getElementById('subject-select').addEventListener('change', function() {
            const subjectId = this.value;
            if (subjectId) {
                loadStudentsForSubject(subjectId);
            } else {
                clearStudentsTable();
            }
        });
    });
    
    function loadStudentsForSubject(subjectId) {
        const selectedSubject = teacherSubjects.find(s => s.subject_id == subjectId);
        if (!selectedSubject) return;
        
        fetch(`teacher-activities-backend.php?action=get_students_for_subject&subject_id=${subjectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateStudentsTable(data.students);
                } else {
                    console.error('Error loading students:', data.message);
                    clearStudentsTable();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                clearStudentsTable();
            });
    }
    
    function populateStudentsTable(students) {
        const tbody = document.getElementById('studentsTableBody');
        tbody.innerHTML = '';
        
        if (students.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; color: #666;">No students found for this subject</td></tr>';
            return;
        }
        
        students.forEach(student => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${student.first_name} ${student.last_name}</td>
                <td>Grade ${student.grade_level}</td>
                <td>${student.total_points || 0}</td>
            `;
            tbody.appendChild(row);
        });
    }
    
    function clearStudentsTable() {
        const tbody = document.getElementById('studentsTableBody');
        tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; color: #666;">Select a subject to view students</td></tr>';
    }
    </script>

</body>
</html>
