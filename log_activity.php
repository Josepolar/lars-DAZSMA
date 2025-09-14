<?php
// log_activity.php
// Usage: include this file and call log_activity($action, $affected_user_id)

function log_activity($action, $affected_user_id = null) {
    try {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            return false; // Only log if user is logged in
        }
        
        $user_id = $_SESSION['user_id'];
        
        $conn = new mysqli('localhost', 'root', '', 'lars_db');
        if ($conn->connect_error) {
            error_log("Database connection error in log_activity: " . $conn->connect_error);
            return false;
        }
        
        // Verify if affected_user_id exists if provided
        if ($affected_user_id !== null) {
            $checkUser = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
            if (!$checkUser) {
                error_log("Error preparing user check query: " . $conn->error);
                $conn->close();
                return false;
            }
            
            $checkUser->bind_param("i", $affected_user_id);
            $checkUser->execute();
            $result = $checkUser->get_result();
            
            if ($result->num_rows === 0) {
                // User doesn't exist, set to null
                $affected_user_id = null;
            }
            
            $checkUser->close();
        }
        
        $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, affected_user_id, action_timestamp) VALUES (?, ?, ?, NOW())");
        if (!$stmt) {
            error_log("Error preparing log insert query: " . $conn->error);
            $conn->close();
            return false;
        }
        
        $stmt->bind_param("isi", $user_id, $action, $affected_user_id);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Error executing log insert: " . $stmt->error);
        }
        
        $stmt->close();
        $conn->close();
        
        return $success;
    } catch (Exception $e) {
        error_log("Exception in log_activity: " . $e->getMessage());
        return false;
    }
}
?>
