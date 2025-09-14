<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile</title>
    <link rel="stylesheet" href="student-viewprof.css">
</head>
<body>
    <div class="profile-container">
        <a href="student-home.php" class="back-btn">← Back</a>
        <br>
        <div class="profile-pic">
            <img src="default-profile.png" alt="Profile Picture" id="profileImage">
            <label for="upload" class="upload-btn">Change Photo</label>
            <input type="file" id="upload" accept="image/*" hidden>
        </div>

        <div class="profile-details">
            <h2 id="fullname">Juan Dela Cruz</h2>
            <p><strong>Password:</strong> <span class="hidden-pass">••••••••</span></p>
            <p><strong>Section:</strong> Emerald</p>
            <p><strong>Grade Level:</strong> 10</p>
        </div>
    </div>

    <script src="student-viewprof.js"></script>
</body>
</html>
