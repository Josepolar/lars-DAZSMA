<?php
require_once 'database.php';

try {
    // Read and execute the SQL file
    $sql = file_get_contents('update_activities_table.sql');
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "Successfully updated activities table.<br>";
    
    // Delete the SQL file after successful execution
    if (unlink('update_activities_table.sql')) {
        echo "Update file deleted successfully.";
    } else {
        echo "Note: Could not delete update file.";
    }
    
} catch (PDOException $e) {
    die("Error executing update: " . $e->getMessage());
}
?>