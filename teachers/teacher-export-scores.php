<?php
session_start();
require_once '../Database/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    die('Unauthorized access');
}

$teacher_id = $_SESSION['user_id'];
$report_type = $_GET['report_type'] ?? 'top10_all';
$game_type = $_GET['game_type'] ?? 'all';
$grade = $_GET['grade'] ?? 'all';
$section = $_GET['section'] ?? 'all';

// Get teacher name
$stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE user_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);
$teacher_name = $teacher['name'] ?? 'Teacher';

// Build WHERE conditions
$grade_condition = $grade !== 'all' ? "AND u.grade_level = '$grade'" : '';
$section_condition = $section !== 'all' ? "AND u.section = '$section'" : '';

// Get report data based on type
$report_data = [];

if ($report_type === 'top10_all' || $report_type === 'full_report') {
    // Top 10 Overall
    $query = "
        SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) as student_name,
               u.grade_level, u.section,
               scores.game_title, scores.game_type, scores.total_score, scores.percentage, scores.completed_at
        FROM (
            SELECT gs.student_id, ga.title as game_title, 'Quiz' as game_type, 
                   gs.total_score, 
                   CASE WHEN gs.total_questions > 0 THEN ROUND((gs.total_correct / gs.total_questions) * 100, 1) ELSE 0 END as percentage,
                   gs.completed_at
            FROM game_sessions gs
            INNER JOIN game_activities ga ON gs.game_id = ga.game_id
            WHERE ga.teacher_id = ? AND gs.completed_at IS NOT NULL
            " . ($game_type === 'all' || $game_type === 'quiz' ? '' : 'AND 1=0') . "
            UNION ALL
            SELECT ms.student_id, mg.title as game_title, 'Matching' as game_type,
                   ms.total_score, 
                   CASE WHEN ms.total_pairs > 0 THEN ROUND((ms.total_correct / ms.total_pairs) * 100, 1) ELSE 0 END as percentage,
                   ms.completed_at
            FROM matching_sessions ms
            INNER JOIN matching_games mg ON ms.matching_game_id = mg.matching_game_id
            WHERE mg.teacher_id = ? AND ms.completed_at IS NOT NULL
            " . ($game_type === 'all' || $game_type === 'matching' ? '' : 'AND 1=0') . "
            UNION ALL
            SELECT ts.student_id, tg.title as game_title, 'Typing' as game_type,
                   ts.total_score, ts.accuracy as percentage,
                   ts.completed_at
            FROM typing_sessions ts
            INNER JOIN typing_games tg ON ts.typing_game_id = tg.typing_game_id
            WHERE tg.teacher_id = ? AND ts.completed_at IS NOT NULL
            " . ($game_type === 'all' || $game_type === 'typing' ? '' : 'AND 1=0') . "
        ) as scores
        INNER JOIN users u ON scores.student_id = u.user_id
        WHERE u.role_id = 4 $grade_condition $section_condition
        ORDER BY scores.total_score DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$teacher_id, $teacher_id, $teacher_id]);
    $report_data['top_10_all'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($report_type === 'top10_section' || $report_type === 'full_report') {
    // Get sections
    $sections_query = "
        SELECT DISTINCT CONCAT('Grade ', u.grade_level, COALESCE(CONCAT(' - ', u.section), '')) as section_key,
               u.grade_level, u.section
        FROM users u
        INNER JOIN subjects s ON u.grade_level = s.grade_level
        INNER JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
        WHERE ts.teacher_id = ? AND u.role_id = 4
        $grade_condition $section_condition
        ORDER BY u.grade_level, u.section
    ";
    
    $stmt = $pdo->prepare($sections_query);
    $stmt->execute([$teacher_id]);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $report_data['sections'] = [];
    
    foreach ($sections as $sec) {
        $sec_grade_cond = "AND u.grade_level = '{$sec['grade_level']}'";
        $sec_section_cond = $sec['section'] ? "AND u.section = '{$sec['section']}'" : "AND (u.section IS NULL OR u.section = '')";
        
        $section_query = "
            SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) as student_name,
                   scores.game_title, scores.game_type, scores.total_score, scores.completed_at
            FROM (
                SELECT gs.student_id, ga.title as game_title, 'Quiz' as game_type, 
                       gs.total_score, gs.completed_at
                FROM game_sessions gs
                INNER JOIN game_activities ga ON gs.game_id = ga.game_id
                WHERE ga.teacher_id = ? AND gs.completed_at IS NOT NULL
                " . ($game_type === 'all' || $game_type === 'quiz' ? '' : 'AND 1=0') . "
                UNION ALL
                SELECT ms.student_id, mg.title as game_title, 'Matching' as game_type,
                       ms.total_score, ms.completed_at
                FROM matching_sessions ms
                INNER JOIN matching_games mg ON ms.matching_game_id = mg.matching_game_id
                WHERE mg.teacher_id = ? AND ms.completed_at IS NOT NULL
                " . ($game_type === 'all' || $game_type === 'matching' ? '' : 'AND 1=0') . "
                UNION ALL
                SELECT ts.student_id, tg.title as game_title, 'Typing' as game_type,
                       ts.total_score, ts.completed_at
                FROM typing_sessions ts
                INNER JOIN typing_games tg ON ts.typing_game_id = tg.typing_game_id
                WHERE tg.teacher_id = ? AND ts.completed_at IS NOT NULL
                " . ($game_type === 'all' || $game_type === 'typing' ? '' : 'AND 1=0') . "
            ) as scores
            INNER JOIN users u ON scores.student_id = u.user_id
            WHERE u.role_id = 4 $sec_grade_cond $sec_section_cond
            ORDER BY scores.total_score DESC
            LIMIT 10
        ";
        
        $stmt = $pdo->prepare($section_query);
        $stmt->execute([$teacher_id, $teacher_id, $teacher_id]);
        $report_data['sections'][$sec['section_key']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Generate PDF-friendly HTML
$current_date = date('F d, Y');
$report_title = $report_type === 'top10_all' ? 'Top 10 Highest Scores - All Grades' : 
               ($report_type === 'top10_section' ? 'Top 10 Highest Scores - Per Section' : 'Complete Score Report');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $report_title; ?> - LARS Score Report</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: white;
        }
        
        .report-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #004b9c;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header img {
            height: 60px;
            margin-bottom: 10px;
        }
        
        .header h1 {
            color: #004b9c;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header h2 {
            color: #666;
            font-size: 16px;
            font-weight: normal;
        }
        
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .meta-info div {
            text-align: center;
        }
        
        .meta-info .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        
        .meta-info .value {
            font-size: 14px;
            font-weight: bold;
            color: #004b9c;
        }
        
        .section-title {
            background: linear-gradient(135deg, #004b9c, #0066cc);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 25px 0 15px;
            font-size: 16px;
        }
        
        .section-title i {
            margin-right: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        th {
            background: #004b9c;
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        tr:hover {
            background: #e3f2fd;
        }
        
        .rank-1 { color: #FFD700; font-weight: bold; font-size: 16px; }
        .rank-2 { color: #C0C0C0; font-weight: bold; font-size: 15px; }
        .rank-3 { color: #CD7F32; font-weight: bold; font-size: 14px; }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .badge-quiz { background: #e3f2fd; color: #1976d2; }
        .badge-matching { background: #e8f5e9; color: #388e3c; }
        .badge-typing { background: #fff3e0; color: #f57c00; }
        .badge-grade { background: #004b9c; color: white; }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #004b9c;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0,75,156,0.3);
            z-index: 1000;
        }
        
        .print-btn:hover {
            background: #003d7a;
        }
        
        @media print {
            .print-btn { display: none; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .section-title { break-after: avoid; }
            table { break-inside: avoid; }
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-data i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <button class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Print / Save as PDF
    </button>
    
    <div class="report-container">
        <div class="header">
            <h1>🎮 LARS Game Score Report</h1>
            <h2><?php echo $report_title; ?></h2>
        </div>
        
        <div class="meta-info">
            <div>
                <div class="label">Teacher</div>
                <div class="value"><?php echo htmlspecialchars($teacher_name); ?></div>
            </div>
            <div>
                <div class="label">Report Date</div>
                <div class="value"><?php echo $current_date; ?></div>
            </div>
            <div>
                <div class="label">Game Type</div>
                <div class="value"><?php echo $game_type === 'all' ? 'All Games' : ucfirst($game_type); ?></div>
            </div>
            <div>
                <div class="label">Filter</div>
                <div class="value"><?php echo $grade === 'all' ? 'All Grades' : 'Grade ' . $grade; ?><?php echo $section !== 'all' ? ' - ' . $section : ''; ?></div>
            </div>
        </div>
        
        <?php if ($report_type === 'top10_all' || $report_type === 'full_report'): ?>
        <div class="section-title">
            <i class="fas fa-trophy"></i> Top 10 Highest Scores - All Grades
        </div>
        
        <?php if (!empty($report_data['top_10_all'])): ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">Rank</th>
                    <th>Student Name</th>
                    <th>Grade & Section</th>
                    <th>Game</th>
                    <th style="text-align: right;">Score</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data['top_10_all'] as $index => $score): ?>
                <tr>
                    <td style="text-align: center;" class="<?php echo $index < 3 ? 'rank-' . ($index + 1) : ''; ?>">
                        <?php 
                        if ($index === 0) echo '🥇';
                        elseif ($index === 1) echo '🥈';
                        elseif ($index === 2) echo '🥉';
                        else echo ($index + 1);
                        ?>
                    </td>
                    <td><strong><?php echo htmlspecialchars($score['student_name']); ?></strong></td>
                    <td>
                        <span class="badge badge-grade">
                            Grade <?php echo $score['grade_level']; ?><?php echo $score['section'] ? ' - ' . $score['section'] : ''; ?>
                        </span>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($score['game_title']); ?>
                        <span class="badge badge-<?php echo strtolower($score['game_type']); ?>">
                            <?php echo $score['game_type']; ?>
                        </span>
                    </td>
                    <td style="text-align: right;"><strong><?php echo $score['total_score']; ?> pts</strong></td>
                    <td><?php echo date('M d, Y', strtotime($score['completed_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-data">
            <i class="fas fa-chart-bar"></i>
            <p>No score data available</p>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($report_type === 'top10_section' || $report_type === 'full_report'): ?>
        <?php if (!empty($report_data['sections'])): ?>
        <?php foreach ($report_data['sections'] as $section_key => $scores): ?>
        <?php if (!empty($scores)): ?>
        <div class="section-title">
            <i class="fas fa-users"></i> <?php echo htmlspecialchars($section_key); ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">Rank</th>
                    <th>Student Name</th>
                    <th>Game</th>
                    <th style="text-align: right;">Score</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scores as $index => $score): ?>
                <tr>
                    <td style="text-align: center;" class="<?php echo $index < 3 ? 'rank-' . ($index + 1) : ''; ?>">
                        <?php 
                        if ($index === 0) echo '🥇';
                        elseif ($index === 1) echo '🥈';
                        elseif ($index === 2) echo '🥉';
                        else echo ($index + 1);
                        ?>
                    </td>
                    <td><strong><?php echo htmlspecialchars($score['student_name']); ?></strong></td>
                    <td>
                        <?php echo htmlspecialchars($score['game_title']); ?>
                        <span class="badge badge-<?php echo strtolower($score['game_type']); ?>">
                            <?php echo $score['game_type']; ?>
                        </span>
                    </td>
                    <td style="text-align: right;"><strong><?php echo $score['total_score']; ?> pts</strong></td>
                    <td><?php echo date('M d, Y', strtotime($score['completed_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="no-data">
            <i class="fas fa-chart-bar"></i>
            <p>No section score data available</p>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <div class="footer">
            <p>Generated by LARS (Learning Activity Recording System) on <?php echo $current_date; ?></p>
            <p>This report contains confidential student performance data. Handle with care.</p>
        </div>
    </div>
</body>
</html>
