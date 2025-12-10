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
    <link rel="icon" type="image/png" href="../assets/tablogo.png">
    <link rel="stylesheet" href="teacher-studs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
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
                        <button class="tablinks active"><a href="teacher-studs.php" class="tablinks">Students</a></button>
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
    
    <button class="bulk-export-btn" onclick="showBulkExportModal()">
        <i class="fas fa-file-excel"></i> Bulk Export
    </button>
</div>

            
        <div class="table_responsive">
            <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Grade Level</th>
                        <th>Total Points</th>
                        <th>Average Grade (%)</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <tr>
                        <td colspan="4" style="text-align: center; color: #666;">
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

    <!-- Bulk Export Modal -->
    <div id="bulkExportModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBulkExportModal()">&times;</span>
            <h2>Bulk Export Grades</h2>
            <form id="bulkExportForm">
                <label for="gradeLevel">Select Grade Level:</label>
                <select id="gradeLevel" name="grade_level" required>
                    <option value="" disabled selected>Choose a grade level</option>
                    <option value="7">Grade 7</option>
                    <option value="8">Grade 8</option>
                    <option value="9">Grade 9</option>
                    <option value="10">Grade 10</option>
                    <option value="11">Grade 11</option>
                    <option value="12">Grade 12</option>
                </select>
                
                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="closeBulkExportModal()">Cancel</button>
                    <button type="submit" class="create-btn">Export</button>
                </div>
            </form>
        </div>
    </div>


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
                console.log('API Response:', data); // Debug log
                if (data.success) {
                    if (data.debug) {
                        console.log('Debug Info:', data.debug);
                    }
                    populateStudentsTable(data.students);
                } else {
                    console.error('Error loading students:', data.message);
                    const tbody = document.getElementById('studentsTableBody');
                    tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: #dc3545;">${data.message}</td></tr>`;
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                const tbody = document.getElementById('studentsTableBody');
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #dc3545;">Error loading students. Please try again.</td></tr>';
            });
    }
    
    function populateStudentsTable(students) {
        const tbody = document.getElementById('studentsTableBody');
        tbody.innerHTML = '';
        
        if (students.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #666;">No students found for this subject</td></tr>';
            return;
        }
        
        // Debug: log first student data
        if (students.length > 0) {
            console.log('First student data:', {
                name: students[0].first_name + ' ' + students[0].last_name,
                total_points: students[0].total_points,
                avg_grade: students[0].avg_grade,
                raw_avg: students[0].avg_grade
            });
        }
        
        students.forEach(student => {
            const row = document.createElement('tr');
            // Ensure total_points is displayed as an integer with no decimals
            const totalPoints = Math.round(student.total_points || 0);
            const avgGrade = (student.avg_grade || 0).toFixed(1);
            row.innerHTML = `
                <td data-label="Student Name">${student.first_name} ${student.last_name}</td>
                <td data-label="Grade Level">Grade ${student.grade_level}</td>
                <td data-label="Total Points">${totalPoints}</td>
                <td data-label="Average Grade (%)">${avgGrade}%</td>
            `;
            tbody.appendChild(row);
        });
    }
    
    function clearStudentsTable() {
        const tbody = document.getElementById('studentsTableBody');
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #666;">Select a subject to view students</td></tr>';
    }
    
    // Bulk Export Modal Functions
    function showBulkExportModal() {
        document.getElementById('bulkExportModal').style.display = 'block';
    }
    
    function closeBulkExportModal() {
        document.getElementById('bulkExportModal').style.display = 'none';
        document.getElementById('bulkExportForm').reset();
    }
    
    // Handle bulk export form submission
    document.getElementById('bulkExportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const gradeLevel = document.getElementById('gradeLevel').value;
        
        if (!gradeLevel) {
            alert('Please select a grade level');
            return;
        }
        
        // Redirect to export endpoint
        window.location.href = `teacher-bulk-operations.php?action=bulk_export_grades&grade_level=${gradeLevel}`;
        
        // Close modal after initiating download
        setTimeout(() => {
            closeBulkExportModal();
        }, 500);
    });
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('bulkExportModal');
        if (event.target === modal) {
            closeBulkExportModal();
        }
    }
    </script>

</body>
</html>

