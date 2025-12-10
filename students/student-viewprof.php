<?php
session_start();
// Redirect to login if not logged in as student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    header('Location: stud-login.php');
    exit();
}

include '../Database/database.php';

// Handle profile image upload
$uploadMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $img = $_FILES['profile_image'];
    if ($img['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $targetDir = '../uploads/profile_images/';
            // Remove old images for this user
            foreach (glob($targetDir . 'student_' . $_SESSION['user_id'] . '_*.jpg') as $old) {
                unlink($old);
            }
            $newName = 'student_' . $_SESSION['user_id'] . '_' . time() . '.jpg';
            $targetFile = $targetDir . $newName;
            // Convert to jpg if needed
            if ($ext === 'png') {
                if (function_exists('imagecreatefrompng')) {
                    $imgRes = @imagecreatefrompng($img['tmp_name']);
                    if ($imgRes) {
                        imagejpeg($imgRes, $targetFile, 90);
                        imagedestroy($imgRes);
                        $uploadMsg = 'Profile image updated!';
                    } else {
                        $uploadMsg = 'Failed to process PNG image.';
                    }
                } else {
                    $uploadMsg = 'PNG upload requires the PHP GD extension. Please enable GD or upload a JPG image.';
                }
            } else {
                if (move_uploaded_file($img['tmp_name'], $targetFile)) {
                    $uploadMsg = 'Profile image updated!';
                } else {
                    $uploadMsg = 'Failed to upload image.';
                }
            }
        } else {
            $uploadMsg = 'Only JPG and PNG files allowed.';
        }
    } else {
        $uploadMsg = 'Image upload failed.';
    }
}

// Handle password change
$passMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];
    if (strlen($newPass) < 6) {
        $passMsg = 'Password must be at least 6 characters.';
    } elseif ($newPass !== $confirmPass) {
        $passMsg = 'Passwords do not match.';
    } else {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update->execute([$hash, $_SESSION['user_id']]);
        $passMsg = 'Password updated!';
    }
}

// Fetch current student info
$stmt = $pdo->prepare("SELECT user_id, first_name, last_name, section, grade_level, password FROM users WHERE user_id = ? AND role_id = 4");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header('Location: stud-login.php');
    exit();
}

// Profile image logic
$profileImgPath = '../uploads/profile_images/student_' . $user['user_id'] . '_*.jpg';
$profileImgFiles = glob($profileImgPath);
if (count($profileImgFiles) > 0) {
    // Convert server path to web path (remove leading '../')
    $profileImgWeb = str_replace('..', '', $profileImgFiles[0]);
    $profileImgWeb = ltrim($profileImgWeb, '/');
    $profileImg = $profileImgWeb;
    $imgCacheBuster = '?t=' . filemtime($profileImgFiles[0]);
} else {
    $profileImg = 'default-profile.png';
    $imgCacheBuster = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/tablogo.png">
    <title>View Profile</title>
    <link rel="stylesheet" href="student-viewprof.css">
</head>
<body>
    <div class="profile-container">
        <a href="student-home.php" class="back-btn">← Back</a>
        <br>
        <?php if ($uploadMsg): ?>
            <div style="color:green; font-size:13px; margin-bottom:5px;"> <?php echo htmlspecialchars($uploadMsg); ?> </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 4 && $_SESSION['user_id'] == $user['user_id']): ?>
        <div class="profile-pic">
            <img src="/<?php echo htmlspecialchars($profileImg . $imgCacheBuster); ?>" alt="Profile Picture" id="profileImage" style="object-fit:cover;">
            <form method="post" enctype="multipart/form-data" style="margin-top:10px;">
                <input type="file" name="profile_image" id="upload" accept="image/*" style="display:none;">
                <label for="upload" class="upload-btn">Change Photo</label>
                <button type="submit" class="upload-btn" style="margin-left:10px; background:#eaf2fb; color:#004b9c;">Save</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="profile-details">
            <h2 id="fullname"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
            <p><strong>Password:</strong> <span class="hidden-pass">••••••••</span></p>
            <form method="post" style="margin:10px 0 0 0;">
                <input type="password" name="new_password" placeholder="New password" minlength="6" required style="margin-bottom:5px; width:80%; padding:5px; border-radius:8px; border:1px solid #ccc;">
                <input type="password" name="confirm_password" placeholder="Confirm password" minlength="6" required style="margin-bottom:5px; width:80%; padding:5px; border-radius:8px; border:1px solid #ccc;">
                <button type="submit" class="upload-btn" style="background:#003570;">Change Password</button>
                <?php if ($passMsg): ?>
                    <div style="color:<?php echo (strpos($passMsg,'updated')!==false)?'green':'red'; ?>; font-size:13px; margin-top:5px;"> <?php echo htmlspecialchars($passMsg); ?> </div>
                <?php endif; ?>
            </form>
            <p><strong>Section:</strong> <?php echo htmlspecialchars($user['section']); ?></p>
            <p><strong>Grade Level:</strong> <?php echo htmlspecialchars($user['grade_level']); ?></p>
        </div>
    </div>

    <script>
    // Preview selected image
    document.getElementById('upload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('profileImage').src = ev.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
    </script>
    <script src="student-viewprof.js"></script>
</body>
</html>
