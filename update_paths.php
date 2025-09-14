<?php
// Script to update file paths after reorganization

// Function to update file paths in a directory
function updatePaths($directory, $patterns) {
    $files = glob($directory . '/*.php');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $originalContent = $content;
        
        foreach ($patterns as $old => $new) {
            $content = str_replace($old, $new, $content);
        }
        
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            echo "Updated: " . basename($file) . "\n";
        }
    }
}

// Define path replacement patterns for each directory
$adminPatterns = [
    'href="logout.php"' => 'href="../logout.php"',
    'src="assets/' => 'src="../assets/',
    'Location: admin-login.php' => 'Location: admin-login.php', // Keep same directory
];

$teacherPatterns = [
    'href="logout.php"' => 'href="../logout.php"',
    'src="assets/' => 'src="../assets/',
    'Location: teacher-login.php' => 'Location: teacher-login.php', // Keep same directory
];

$staffPatterns = [
    'href="logout.php"' => 'href="../logout.php"',
    'src="assets/' => 'src="../assets/',
    'Location: staff-login.php' => 'Location: staff-login.php', // Keep same directory
];

$studentPatterns = [
    'href="logout.php"' => 'href="../logout.php"',
    'src="assets/' => 'src="../assets/',
    'Location: stud-login.php' => 'Location: stud-login.php', // Keep same directory
];

echo "Updating admin files...\n";
updatePaths('admin', $adminPatterns);

echo "Updating teacher files...\n";
updatePaths('teachers', $teacherPatterns);

echo "Updating staff files...\n";
updatePaths('staff', $staffPatterns);

echo "Updating student files...\n";
updatePaths('students', $studentPatterns);

echo "Path updates completed!\n";
?>