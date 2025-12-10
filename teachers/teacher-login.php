<?php
session_start();
// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3) {
    header("Location: teacher-dashboard.php");
    exit();
}

// Use shared PDO connection
require_once __DIR__ . '/../Database/database.php';
require_once __DIR__ . '/../log_activity.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    try {
        $stmt = $pdo->prepare("SELECT user_id, password, first_name, last_name FROM users WHERE email = ? AND role_id = 3");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
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
    } catch (Exception $e) {
        $error = 'An error occurred during login.';
        error_log('Teacher login error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/tablogo.png">
    <title>Teacher Login</title>
    <link rel="stylesheet" href="teacher-login.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <img src="../assets/larslogo.png" alt="LARS Logo" style="display:block;margin:0 auto 18px auto;width:80px;height:auto;">
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
                        <!-- <span class="password-toggle" onclick="togglePassword()">
                            <img src="../assets/eye.png" alt="toggle password" id="toggleIcon">
                        </span> -->
                    </div>
                </div>

                <button type="submit" class="login-btn">Login</button>
                
                <!-- <script>
                function togglePassword() {
                    const passwordInput = document.getElementById('password');
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                    } else {
                        passwordInput.type = 'password';
                    }
                }
                </script> -->
            </form>
        </div>
    </div>

</body>
</html>
