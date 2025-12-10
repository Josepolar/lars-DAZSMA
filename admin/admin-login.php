<?php
session_start();
// Check if admin is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
    header("Location: admin-dashboard.php");
    exit();
}
include '../Database/database.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $query = "SELECT user_id, password, first_name, last_name FROM users WHERE email = ? AND role_id = 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role_id'] = 1;
            $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['first_name'] = $user['first_name'];
            $log_query = "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, 'Login', ?)";
            $log_stmt = $pdo->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt->execute([$user['user_id'], $ip]);
            header("Location: admin-dashboard.php");
            exit();
        } else {
            $error = 'Invalid password';
        }
    } else {
        $error = 'Email not found or you do not have admin privileges';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/tablogo.png">
    <title>Admin Login</title>
    <link rel="stylesheet" href="admin-login.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <img src="../assets/larslogo.png" alt="LARS Logo" style="display:block;margin:0 auto 18px auto;width:80px;height:auto;">
            <h2>ADMINISTRATOR</h2>
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