<?php
session_start();
// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2) {
    header("Location: staff-dashboard.php");
    exit();
}
include '../Database/database.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $query = "SELECT user_id, password, first_name, last_name FROM users WHERE email = ? AND role_id = 2";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role_id'] = 2;
            $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
            $log_query = "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, 'Login', ?)";
            $log_stmt = $pdo->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt->execute([$user['user_id'], $ip]);
            header("Location: staff-dashboard.php");
            exit();
        } else {
            $error = 'Invalid password';
        }
    } else {
        $error = 'Email not found or you do not have staff privileges';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login</title>
    <link rel="stylesheet" href="staff-login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>STAFF</h2>
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
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="login-btn">Login</button>
            </form>
        </div>
    </div>

</body>
</html>
