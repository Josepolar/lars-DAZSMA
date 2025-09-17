<?php
// log_activity.php
// Usage: include this file and call log_activity($action, $affected_user_id)

// Always resolve include relative to this file so it works from subfolders
require_once __DIR__ . '/Database/database.php';

function log_activity($action, $affected_user_id = null) {
    global $pdo;
    try {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            return false; // Only log if user is logged in
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Verify if affected_user_id exists if provided
        if ($affected_user_id !== null) {
            $checkUser = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $checkUser->execute([$affected_user_id]);
            $result = $checkUser->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                // User doesn't exist, set to null
                $affected_user_id = null;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, action, affected_user_id, action_timestamp) VALUES (?, ?, ?, NOW())");
        $success = $stmt->execute([$user_id, $action, $affected_user_id]);
        
        return $success;
    } catch (Exception $e) {
        error_log("Exception in log_activity: " . $e->getMessage());
        return false;
    }
}
?>
