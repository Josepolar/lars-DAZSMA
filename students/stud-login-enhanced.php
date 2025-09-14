<?php
// Enhanced Student Login with Additional Security and Validation
session_start();

// Configuration
$max_login_attempts = 5;
$lockout_time = 15 * 60; // 15 minutes in seconds

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 4) {
    header('Location: home.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'lars_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Function to check if IP is temporarily locked
function isIPLocked($conn, $ip) {
    global $max_login_attempts, $lockout_time;
    
    $check_query = "SELECT COUNT(*) as attempts, MAX(action_timestamp) as last_attempt 
                    FROM user_logs 
                    WHERE ip_address = ? AND action = 'Failed Login' 
                    AND action_timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('si', $ip, $lockout_time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['attempts'] >= $max_login_attempts;
}

// Function to log login attempts
function logLoginAttempt($conn, $user_id, $action, $ip) {
    $log_query = "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($log_query);
    $stmt->bind_param('iss', $user_id, $action, $ip);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

$error = '';
$ip = $_SERVER['REMOTE_ADDR'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if IP is temporarily locked
    if (isIPLocked($conn, $ip)) {
        $error = 'Too many failed login attempts. Please try again in 15 minutes.';
    } else {
        $login_input = trim($conn->real_escape_string($_POST['email'])); // Can be email or username
        $password = $_POST['password'];
        
        // Input validation
        if (empty($login_input) || empty($password)) {
            $error = 'Please fill in all fields';
        } elseif (strlen($login_input) < 3) {
            $error = 'Username/email must be at least 3 characters';
        } elseif (strlen($password) < 3) {
            $error = 'Password must be at least 3 characters';
        } else {
            // Query to find user by email OR username for students (role_id = 4)
            $query = "SELECT user_id, password, first_name, last_name, username, created_at, grade_level 
                      FROM users 
                      WHERE (email = ? OR username = ?) AND role_id = 4";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $login_input, $login_input);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                // Check password (supports both plain text and hashed passwords)
                if ($password === $user['password'] || password_verify($password, $user['password'])) {
                    // Successful login
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role_id'] = 4;
                    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['grade_level'] = $user['grade_level'];
                    $_SESSION['login_time'] = time();
                    
                    // Log successful login
                    logLoginAttempt($conn, $user['user_id'], 'Login', $ip);
                    
                    // Clear any previous failed attempts for this IP
                    $clear_query = "DELETE FROM user_logs 
                                   WHERE ip_address = ? AND action = 'Failed Login' 
                                   AND action_timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)";
                    $clear_stmt = $conn->prepare($clear_query);
                    $clear_stmt->bind_param('si', $ip, $lockout_time);
                    $clear_stmt->execute();
                    $clear_stmt->close();
                    
                    $stmt->close();
                    $conn->close();
                    
                    header("Location: home.php");
                    exit();
                } else {
                    // Failed login - log attempt
                    if ($user['user_id']) {
                        logLoginAttempt($conn, $user['user_id'], 'Failed Login', $ip);
                    } else {
                        logLoginAttempt($conn, 0, 'Failed Login', $ip); // Use 0 for unknown user
                    }
                    $error = 'Invalid password';
                }
            } else {
                // User not found - log attempt with unknown user
                logLoginAttempt($conn, 0, 'Failed Login', $ip);
                $error = 'Invalid email/username or you do not have student privileges';
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LARS - Student Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    <style>
        .login-info {
            background: rgba(255, 255, 255, 0.9);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .login-info h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .login-info p {
            margin: 5px 0;
            color: #666;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
        }
        .security-note {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="login-container">
    <a href="index.php">
        <img src="../assets/lars.png" alt="LARS Logo" class="lars-img">
    </a>
    
    <div class="login-info">
        <h4>Student Portal Access</h4>
        <p>Use your username or email provided by your teacher</p>
        <p><strong>Need help?</strong> Contact your teacher or school staff</p>
    </div>
    
    <?php if ($error): ?>
        <div class="error-message">
            <strong>Login Failed:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Username or Email</label>
            <input type="text" id="email" name="email" 
                   placeholder="Enter your username or email" 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                   required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" 
                   placeholder="Enter your password" required>
        </div>
        
        <button type="submit"><span>Login to Dashboard</span></button>
        
        <div class="security-note">
            <p>🔒 Your login attempts are monitored for security</p>
        </div>
    </form>
</div>

<script>
// Add some basic client-side validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    form.addEventListener('submit', function(e) {
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        
        if (email.length < 3) {
            alert('Username/email must be at least 3 characters long');
            e.preventDefault();
            emailInput.focus();
            return;
        }
        
        if (password.length < 3) {
            alert('Password must be at least 3 characters long');
            e.preventDefault();
            passwordInput.focus();
            return;
        }
    });
    
    // Auto-focus on first empty field
    if (!emailInput.value) {
        emailInput.focus();
    } else if (!passwordInput.value) {
        passwordInput.focus();
    }
});
</script>

</body>
</html>