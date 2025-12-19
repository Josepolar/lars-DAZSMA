<?php
session_start();
require_once '../log_activity.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/png" href="../assets/tablogo.png">
    <link rel="stylesheet" href="staff-userman.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .archive-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .archive-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }

        .archive-header h1 {
            margin: 0;
            color: #333;
        }

        .archive-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .stat-card.pending {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card.archived {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .archive-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #333;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }

        .archive-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .archive-table thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .archive-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }

        .archive-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }

        .archive-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-archived {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeaa7;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
        }

        .checkbox-item input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
            cursor: pointer;
        }

        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }

        .grade-selection {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .grade-btn {
            padding: 8px 16px;
            border: 2px solid #dee2e6;
            background-color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .grade-btn.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .grade-btn:hover {
            border-color: #007bff;
        }

        .export-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .export-card {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .export-card:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.1);
            background-color: #f8f9fa;
        }

        .export-card i {
            font-size: 24px;
            color: #007bff;
            margin-bottom: 10px;
        }

        .export-card-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .export-card-desc {
            font-size: 12px;
            color: #6c757d;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading-spinner.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

        .modal.show {
            display: block;
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-header h2 {
            margin: 0;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
    </style>
    <title>Student Archive - Staff Dashboard</title>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleMobileSidebar()" style="display:none;">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay" onclick="toggleMobileSidebar()"></div>
    <nav class="sidebar">
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="../assets/larslogo.png" alt="logo">
                </span>
                <div class="text header-text">
                    <span class="profession">Staff Dashboard</span>
                    <span class="name">Hello <?php $firstName = ''; if (!empty($_SESSION['first_name'])) { $firstName = $_SESSION['first_name']; } elseif (!empty($_SESSION['name'])) { $parts = explode(' ', trim($_SESSION['name'])); $firstName = $parts[0]; } echo htmlspecialchars($firstName); ?></span>
                </div>
            </div>
            <hr>
        </header>

        <div class="menu-bar">
            <div class="menu">
                <ul class="menu-links">
                    <li class="nav-link">
                        <button class="tablinks"><a href="staff-dashboard.php" class="tablinks">Dashboard</a></button>
                    </li>
                    <li class="nav-link">
                        <button class="tablinks"><a href="staff-userman.php" class="tablinks">User Management</a></button>
                    </li>
                    <li class="nav-link">
                        <button class="tablinks"><a href="staff-subjman.php" class="tablinks">Subject Management</a></button>
                    </li>
                    <li class="nav-link">
                        <button class="tablinks"><a href="staff-archive.php" class="tablinks" style="color: #007bff; font-weight: bold;">Student Archive</a></button>
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
        <div class="archive-container">
            <div class="archive-header">
                <div>
                    <h1><i class="fas fa-archive"></i> Student Archive Management</h1>
                    <p style="color: #666; margin-top: 5px;">Export, archive, and transition student records for new school years</p>
                </div>
            </div>

            <?php
            include '../Database/database.php';

            // Get statistics
            $totalStudents = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role_id = 4")->fetch(PDO::FETCH_ASSOC)['cnt'];
            $archivedRecords = $pdo->query("SELECT COUNT(*) as cnt FROM student_archives")->fetch(PDO::FETCH_ASSOC)['cnt'];
            $pendingArchives = $pdo->query("SELECT COUNT(*) as cnt FROM student_archives WHERE archive_status = 'pending'")->fetch(PDO::FETCH_ASSOC)['cnt'];
            ?>

            <div class="archive-stats">
                <div class="stat-card">
                    <i class="fas fa-users" style="font-size: 24px;"></i>
                    <div class="stat-number"><?php echo $totalStudents; ?></div>
                    <div class="stat-label">Active Students</div>
                </div>
                <div class="stat-card archived">
                    <i class="fas fa-box" style="font-size: 24px;"></i>
                    <div class="stat-number"><?php echo $archivedRecords; ?></div>
                    <div class="stat-label">Archived Records</div>
                </div>
                <div class="stat-card pending">
                    <i class="fas fa-clock" style="font-size: 24px;"></i>
                    <div class="stat-number"><?php echo $pendingArchives; ?></div>
                    <div class="stat-label">Pending Archives</div>
                </div>
            </div>

            <!-- Export Students Section -->
            <div class="archive-section">
                <div class="section-title">
                    <i class="fas fa-download"></i> Export Student Records
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Export Options:</strong> Export student records in various formats for backup and transition purposes.
                </div>

                <div class="form-group">
                    <label>Select Grade Levels to Export</label>
                    <div class="grade-selection">
                        <button class="grade-btn active" onclick="toggleGrade(this, '7')">
                            <i class="fas fa-check"></i> Grade 7
                        </button>
                        <button class="grade-btn" onclick="toggleGrade(this, '8')">
                            <i class="fas fa-check"></i> Grade 8
                        </button>
                        <button class="grade-btn" onclick="toggleGrade(this, '9')">
                            <i class="fas fa-check"></i> Grade 9
                        </button>
                        <button class="grade-btn" onclick="toggleGrade(this, '10')">
                            <i class="fas fa-check"></i> Grade 10
                        </button>
                        <button class="grade-btn" onclick="toggleAllGrades()">
                            <i class="fas fa-check"></i> All Grades
                        </button>
                    </div>
                    <input type="hidden" id="selectedGrades" value="7">
                </div>

                <div class="export-options">
                    <div class="export-card" onclick="exportStudents('csv')">
                        <div><i class="fas fa-file-csv"></i></div>
                        <div class="export-card-title">Export as CSV</div>
                        <div class="export-card-desc">Standard comma-separated format</div>
                    </div>
                    <div class="export-card" onclick="exportStudents('excel')">
                        <div><i class="fas fa-file-excel"></i></div>
                        <div class="export-card-title">Export as Excel</div>
                        <div class="export-card-desc">Microsoft Excel format (.xlsx)</div>
                    </div>
                    <div class="export-card" onclick="exportStudents('json')">
                        <div><i class="fas fa-file-code"></i></div>
                        <div class="export-card-title">Export as JSON</div>
                        <div class="export-card-desc">JSON format for integration</div>
                    </div>
                    <div class="export-card" onclick="exportStudents('pdf')">
                        <div><i class="fas fa-file-pdf"></i></div>
                        <div class="export-card-title">Export as PDF</div>
                        <div class="export-card-desc">Portable document format</div>
                    </div>
                </div>

                <div class="btn-group">
                    <button class="btn btn-primary" onclick="performExport()">
                        <i class="fas fa-download"></i> Export Selected Records
                    </button>
                    <button class="btn btn-secondary" onclick="openModal('advancedExportModal')">
                        <i class="fas fa-cog"></i> Advanced Options
                    </button>
                </div>
            </div>

            <!-- Archive and Transition Section -->
            <div class="archive-section">
                <div class="section-title">
                    <i class="fas fa-exchange-alt"></i> Archive & Transition Students
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> This action will archive the current students and prepare them for enrollment in the next school year.
                </div>

                <form method="POST" id="archiveForm" onsubmit="handleArchiveSubmit(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="currentYear">Current School Year</label>
                            <input type="text" id="currentYear" name="current_year" placeholder="e.g., 2024-2025" required>
                        </div>
                        <div class="form-group">
                            <label for="nextYear">Next School Year</label>
                            <input type="text" id="nextYear" name="next_year" placeholder="e.g., 2025-2026" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Grade Levels to Promote</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="grade7" name="promote_grades" value="7" checked>
                                <label for="grade7">Grade 7 → Grade 8</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="grade8" name="promote_grades" value="8" checked>
                                <label for="grade8">Grade 8 → Grade 9</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="grade9" name="promote_grades" value="9" checked>
                                <label for="grade9">Grade 9 → Grade 10</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="grade10" name="promote_grades" value="10">
                                <label for="grade10">Grade 10 (Graduation/Removal)</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="archiveNotes">Archive Notes</label>
                        <textarea id="archiveNotes" name="archive_notes" placeholder="Add any notes about this archive (optional)..." rows="4"></textarea>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="confirmArchive" name="confirm_archive" required>
                        <label for="confirmArchive">I understand this will archive all selected students and prepare them for the next school year</label>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-archive"></i> Archive & Transition Students
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset Form
                        </button>
                    </div>
                </form>
            </div>

            <!-- Archive History Section -->
            <div class="archive-section">
                <div class="section-title">
                    <i class="fas fa-history"></i> Archive History
                </div>

                <div class="table-responsive">
                    <table class="archive-table">
                        <thead>
                            <tr>
                                <th>Archive ID</th>
                                <th>School Year</th>
                                <th>Students Archived</th>
                                <th>Status</th>
                                <th>Archived Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $archiveHistory = $pdo->query("
                                SELECT archive_id, school_year, COUNT(*) as student_count, archive_status, archived_date 
                                FROM student_archives 
                                GROUP BY archive_id 
                                ORDER BY archived_date DESC 
                                LIMIT 10
                            ");
                            
                            if ($archiveHistory && $archiveHistory->rowCount() > 0) {
                                while ($row = $archiveHistory->fetch(PDO::FETCH_ASSOC)) {
                                    $statusClass = $row['archive_status'] === 'completed' ? 'status-archived' : 'status-pending';
                                    echo "<tr>";
                                    echo "<td>#" . htmlspecialchars($row['archive_id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['school_year']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['student_count']) . "</td>";
                                    echo "<td><span class='status-badge $statusClass'>" . ucfirst($row['archive_status']) . "</span></td>";
                                    echo "<td>" . date('M d, Y', strtotime($row['archived_date'])) . "</td>";
                                    echo "<td>
                                        <button class='btn btn-secondary' onclick='viewArchiveDetails(" . $row['archive_id'] . ")' style='padding: 5px 10px; font-size: 12px;'>
                                            <i class='fas fa-eye'></i> View
                                        </button>
                                    </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align: center; padding: 20px; color: #999;'>No archive history found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Advanced Export Modal -->
    <div id="advancedExportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Advanced Export Options</h2>
                <span class="close" onclick="closeModal('advancedExportModal')">&times;</span>
            </div>
            <form id="advancedExportForm" onsubmit="handleAdvancedExport(event)">
                <div class="form-group">
                    <label for="exportFormat">Export Format</label>
                    <select id="exportFormat" name="export_format" required>
                        <option value="csv">CSV</option>
                        <option value="excel">Excel</option>
                        <option value="json">JSON</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Fields to Include</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="includeEmail" name="fields" value="email" checked>
                            <label for="includeEmail">Email Address</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="includeGrade" name="fields" value="grade" checked>
                            <label for="includeGrade">Grade Level</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="includeSection" name="fields" value="section" checked>
                            <label for="includeSection">Section</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="includePassword" name="fields" value="password">
                            <label for="includePassword">Password</label>
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> Export with Advanced Options
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('advancedExportModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Archive Details Modal -->
    <div id="archiveDetailsModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>Archive Details</h2>
                <span class="close" onclick="closeModal('archiveDetailsModal')">&times;</span>
            </div>
            <div id="archiveDetailsContent"></div>
        </div>
    </div>

    <!-- Students List Modal -->
    <div id="studentsListModal" class="modal">
        <div class="modal-content" style="max-width: 900px; max-height: 80vh; overflow-y: auto;">
            <div class="modal-header">
                <h2 id="studentsListTitle">Students List</h2>
                <span class="close" onclick="closeModal('studentsListModal')">&times;</span>
            </div>
            <div id="studentsListContent"></div>
        </div>
    </div>

    <!-- Loading Spinner Modal -->
    <div id="loadingModal" class="modal">
        <div class="modal-content" style="text-align: center; border: none; box-shadow: none;">
            <div class="loading-spinner active">
                <div class="spinner"></div>
                <p id="loadingText">Processing your request...</p>
            </div>
        </div>
    </div>

    <script>
        let selectedExportFormat = 'csv';

        function toggleGrade(button, grade) {
            button.classList.toggle('active');
            updateSelectedGrades();
        }

        function toggleAllGrades() {
            const buttons = document.querySelectorAll('.grade-btn:not(:last-child)');
            const allBtn = document.querySelector('.grade-btn:last-child');
            
            if (allBtn.classList.contains('active')) {
                buttons.forEach(btn => btn.classList.remove('active'));
                allBtn.classList.remove('active');
            } else {
                buttons.forEach(btn => btn.classList.add('active'));
                allBtn.classList.add('active');
            }
            updateSelectedGrades();
        }

        function updateSelectedGrades() {
            const grades = [];
            document.querySelectorAll('.grade-btn:not(:last-child).active').forEach(btn => {
                const grade = btn.textContent.match(/\d+/)[0];
                grades.push(grade);
            });
            document.getElementById('selectedGrades').value = grades.length > 0 ? grades.join(',') : '';
        }

        function exportStudents(format) {
            selectedExportFormat = format;
            const grades = document.getElementById('selectedGrades').value;
            if (!grades) {
                alert('Please select at least one grade level');
                return;
            }
            performExport();
        }

        function performExport() {
            const grades = document.getElementById('selectedGrades').value;
            if (!grades) {
                alert('Please select at least one grade level');
                return;
            }

            showLoading('Exporting student records...');
            
            const formData = new FormData();
            formData.append('action', 'export');
            formData.append('format', selectedExportFormat);
            formData.append('grades', grades);

            fetch('staff-archive-api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        hideLoading();
                        try {
                            const error = JSON.parse(text);
                            alert('Error: ' + error.message);
                        } catch (e) {
                            alert('Error exporting students: ' + text);
                        }
                        throw new Error('Export failed');
                    });
                }
                
                const contentType = response.headers.get('content-type') || '';
                // Check if it's a file download (CSV, Excel, PDF, JSON)
                if (contentType.includes('text/csv') || 
                    contentType.includes('application/') || 
                    contentType.includes('application/pdf')) {
                    return response.blob().then(blob => {
                        hideLoading();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `students_export_${Date.now()}.${selectedExportFormat}`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    });
                } else {
                    return response.text().then(text => {
                        hideLoading();
                        try {
                            const error = JSON.parse(text);
                            alert('Error: ' + error.message);
                        } catch (e) {
                            alert('Error exporting students: ' + text);
                        }
                    });
                }
            })
            .catch(error => {
                hideLoading();
                alert('Error exporting students: ' + error.message);
            });
        }

        function handleAdvancedExport(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const format = formData.get('export_format');
            const grades = document.getElementById('selectedGrades').value;
            const fields = formData.getAll('fields');

            if (!grades) {
                alert('Please select at least one grade level');
                return;
            }

            showLoading('Exporting with advanced options...');

            const data = new FormData();
            data.append('action', 'export');
            data.append('format', format);
            data.append('grades', grades);
            data.append('fields', JSON.stringify(fields));

            fetch('staff-archive-api.php', {
                method: 'POST',
                body: data
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        hideLoading();
                        closeModal('advancedExportModal');
                        try {
                            const error = JSON.parse(text);
                            alert('Error: ' + error.message);
                        } catch (e) {
                            alert('Error exporting students: ' + text);
                        }
                        throw new Error('Export failed');
                    });
                }
                
                const contentType = response.headers.get('content-type') || '';
                // Check if it's a file download (CSV, Excel, PDF, JSON)
                if (contentType.includes('text/csv') || 
                    contentType.includes('application/') || 
                    contentType.includes('application/pdf')) {
                    return response.blob().then(blob => {
                        hideLoading();
                        closeModal('advancedExportModal');
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `students_export_${Date.now()}.${format}`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    });
                } else {
                    return response.text().then(text => {
                        hideLoading();
                        closeModal('advancedExportModal');
                        try {
                            const error = JSON.parse(text);
                            alert('Error: ' + error.message);
                        } catch (e) {
                            alert('Error exporting students: ' + text);
                        }
                    });
                }
            })
            .catch(error => {
                hideLoading();
                alert('Error exporting students: ' + error.message);
            });
        }

        function handleArchiveSubmit(event) {
            event.preventDefault();
            
            const currentYear = document.getElementById('currentYear').value;
            const nextYear = document.getElementById('nextYear').value;
            const promoteGrades = Array.from(document.querySelectorAll('input[name="promote_grades"]:checked')).map(e => e.value);
            const notes = document.getElementById('archiveNotes').value;

            if (!currentYear || !nextYear) {
                alert('Please fill in both school years');
                return;
            }

            if (promoteGrades.length === 0) {
                alert('Please select at least one grade level to promote');
                return;
            }

            if (!confirm(`Are you sure you want to archive students from ${currentYear} and transition them to ${nextYear}? This action cannot be undone.`)) {
                return;
            }

            showLoading('Archiving and transitioning students...');

            const formData = new FormData();
            formData.append('action', 'archive');
            formData.append('current_year', currentYear);
            formData.append('next_year', nextYear);
            formData.append('promote_grades', JSON.stringify(promoteGrades));
            formData.append('notes', notes);

            fetch('staff-archive-api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert(`Successfully archived and transitioned ${data.count} students!\n\nArchive ID: ${data.archive_id}`);
                    document.getElementById('archiveForm').reset();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                hideLoading();
                alert('Error archiving students: ' + error.message);
            });
        }

        function viewArchiveDetails(archiveId) {
            showLoading('Loading archive details...');

            const formData = new FormData();
            formData.append('action', 'get_details');
            formData.append('archive_id', archiveId);

            fetch('staff-archive-api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    displayArchiveDetails(data);
                    openModal('archiveDetailsModal');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                hideLoading();
                alert('Error loading archive details: ' + error.message);
            });
        }

        function displayArchiveDetails(data) {
            const content = document.getElementById('archiveDetailsContent');
            content.innerHTML = `
                <div style="padding: 20px;">
                    <div style="margin-bottom: 20px;">
                        <p><strong>Archive ID:</strong> ${data.archive_id}</p>
                        <p><strong>School Year:</strong> ${data.school_year}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-archived">${data.archive_status}</span></p>
                        <p><strong>Students Archived:</strong> ${data.student_count}</p>
                        <p><strong>Archived Date:</strong> ${new Date(data.archived_date).toLocaleDateString()}</p>
                        ${data.notes ? `<p><strong>Notes:</strong> ${data.notes}</p>` : ''}
                    </div>
                    
                    <div style="border-top: 1px solid #dee2e6; padding-top: 20px;">
                        <h4>Grade Distribution</h4>
                        <table style="width: 100%; border-collapse: collapse;">
                            ${data.grade_distribution.map(item => `
                                <tr style="border-bottom: 1px solid #dee2e6;">
                                    <td style="padding: 8px;">Grade ${item.grade_level || item.grade || 'N/A'}</td>
                                    <td style="padding: 8px; text-align: right;">${item.count} students</td>
                                    <td style="padding: 8px; text-align: right;">
                                        <button class="btn btn-primary" onclick="viewStudentsByGrade(${data.archive_id}, '${item.grade_level || item.grade || ''}')" style="padding: 5px 10px; font-size: 12px;">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </table>
                    </div>

                    <div style="text-align: right; margin-top: 20px;">
                        <button class="btn btn-primary" onclick="viewAllStudents(${data.archive_id})" style="margin-right: 10px;">
                            <i class="fas fa-users"></i> View All Students
                        </button>
                        <button class="btn btn-secondary" onclick="closeModal('archiveDetailsModal')">Close</button>
                    </div>
                </div>
            `;
        }

        function openModal(id) {
            document.getElementById(id).classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        function showLoading(text) {
            document.getElementById('loadingText').textContent = text;
            openModal('loadingModal');
        }

        function hideLoading() {
            closeModal('loadingModal');
        }

        // Mobile Menu Toggle
        function toggleMobileSidebar() {
            document.querySelector('.sidebar').classList.toggle('show-mobile');
            document.querySelector('.sidebar-overlay').classList.toggle('show');
        }

        function checkMobileMenu() {
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (window.innerWidth <= 576) {
                toggle.style.display = 'flex';
            } else {
                toggle.style.display = 'none';
                document.querySelector('.sidebar').classList.remove('show-mobile');
                document.querySelector('.sidebar-overlay').classList.remove('show');
            }
        }

        function viewAllStudents(archiveId) {
            showLoading('Loading students...');
            
            const formData = new FormData();
            formData.append('action', 'get_students');
            formData.append('archive_id', archiveId);

            fetch('staff-archive-api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    displayStudentsList(data.students, 'All Students');
                    closeModal('archiveDetailsModal');
                    openModal('studentsListModal');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                hideLoading();
                alert('Error loading students: ' + error.message);
            });
        }

        function viewStudentsByGrade(archiveId, gradeLevel) {
            showLoading('Loading students...');
            
            const formData = new FormData();
            formData.append('action', 'get_students');
            formData.append('archive_id', archiveId);
            formData.append('grade_level', gradeLevel);

            fetch('staff-archive-api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    displayStudentsList(data.students, `Grade ${gradeLevel} Students`);
                    closeModal('archiveDetailsModal');
                    openModal('studentsListModal');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                hideLoading();
                alert('Error loading students: ' + error.message);
            });
        }

        function displayStudentsList(students, title) {
            const content = document.getElementById('studentsListContent');
            const titleElement = document.getElementById('studentsListTitle');
            titleElement.textContent = title;
            
            if (!students || students.length === 0) {
                content.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">No students found</div>';
                return;
            }

            content.innerHTML = `
                <div style="padding: 20px;">
                    <div style="margin-bottom: 15px; color: #666;">
                        <strong>Total: ${students.length} student(s)</strong>
                    </div>
                    <div class="table-responsive">
                        <table class="archive-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Grade</th>
                                    <th>Section</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${students.map((student, index) => `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${student.first_name} ${student.last_name}</td>
                                        <td>${student.username}</td>
                                        <td>${student.email || 'N/A'}</td>
                                        <td>${student.grade_level || 'N/A'}</td>
                                        <td>${student.section || 'N/A'}</td>
                                        <td>
                                            <span class="status-badge ${student.action === 'promoted' ? 'status-archived' : 'status-pending'}">
                                                ${student.action === 'promoted' ? 'Promoted' : 'Graduated'}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: right; margin-top: 20px;">
                        <button class="btn btn-secondary" onclick="closeModal('studentsListModal')">Close</button>
                    </div>
                </div>
            `;
        }

        window.addEventListener('resize', checkMobileMenu);
        window.addEventListener('load', checkMobileMenu);
    </script>
</body>
</html>
