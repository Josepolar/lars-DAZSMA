<?php
session_start();
// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 4) {
    header('Location: student-home.php');
    exit();
}
include '../Database/database.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_input = $_POST['email']; // Can be email or username
    $password = $_POST['password'];
    
    // Query to find user by email OR username for students (role_id = 4)
    $query = "SELECT user_id, password, first_name, last_name, username FROM users WHERE (email = ? OR username = ?) AND role_id = 4";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$login_input, $login_input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Check password (supports both plain text and hashed passwords)
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role_id'] = 4;
            $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['username'] = $user['username'];
            
            // Log the login action
            $log_query = "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, 'Login', ?)";
            $log_stmt = $pdo->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'];
            
            if (!$log_stmt->execute([$user['user_id'], $ip])) {
                // Log insertion failed, but don't stop the login process
                error_log("Failed to log student login for user_id: " . $user['user_id']);
            }
            
            header("Location: student-home.php");
            exit();
        } else {
            $error = 'Invalid password';
        }
    } else {
        $error = 'Invalid email/username or you do not have student privileges';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/tablogo.png">
    <title>LARS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stud-login.css">
</head>
<body>
<div class="login-container">
        <a href="../index.php">
            <img src="../assets/lars.png" alt="LARS Logo" class="lars-img">
        </a>
        <?php if ($error): ?>
            <div class="error-message" style="color:red; text-align:center; margin-bottom:10px;"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="email" placeholder="Email or Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit"><span>Login</span></button>
        </form>
    </div>

</body>
</html>
