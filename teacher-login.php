<?php
session_start();
// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3) {
    header("Location: teacher-dashboard.php");
    exit();
}

require_once 'log_activity.php';

$conn = new mysqli('localhost', 'root', '', 'lars_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $query = "SELECT user_id, password, first_name, last_name FROM users WHERE email = ? AND role_id = 3";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role_id'] = 3;
            $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            // Log the login activity
            log_activity('Login');
            
            header("Location: teacher-dashboard.php");
            exit();
        } else {
            $error = 'Invalid password';
        }
    } else {
        $error = 'Email not found or you do not have teacher privileges';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login</title>
    <link rel="stylesheet" href="teacher-login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>TEACHER</h2>
            <HR>
            <BR>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <span class="password-toggle" onclick="togglePassword()">
                            <img src="assets/eye.png" alt="toggle password" id="toggleIcon">
                        </span>
                    </div>
                </div>

                <button type="submit" class="login-btn">Login</button>
                
                <script>
                function togglePassword() {
                    const passwordInput = document.getElementById('password');
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                    } else {
                        passwordInput.type = 'password';
                    }
                }
                </script>
            </form>
        </div>
    </div>

</body>
</html>
