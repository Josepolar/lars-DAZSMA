<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="teacher-studs.css">
    <title>Teachers Dashboard</title>
</head>
<body>
<nav class="sidebar">
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="assets/larslogo.png" alt="logo">
                </span>

                <div class="text header-text">
                    <span class="profession">Teacher Dashboard</span>
                </div>
            </div>
            <hr>
        </header>

        <div class="menu-bar">
            <div class="menu">
                <ul class="menu-links">
                    <li class="nav-link">
                        <button class="tablinks" id="defaultTab"><a href="teacher-dashboard.php" class="tablinks">Dashboard</a></button>
                    </li>

                    <li class="nav-link">
                        <button class="tablinks"><a href="teacher-acts.php" class="tablinks">Activities</a></button>
                    </li>        
                    
                    <li class="nav-link">
                        <button class="tablinks"><a href="teacher-studs.php" class="tablinks">Students</a></button>
                    </li> 
                    
                </ul>
            </div>

            <div class="bottom-content">
            <li class="nav-link">
                        <button class="tablinks"><a href="logout_admin.php" class="tablinks">Logout</a></button>
                    </li>
            </div>
        </div>
    </nav>

   <section class="home" id="home-section">

    <div class="stats-container">
        
    </div>

    <div class="table-container">
        <div class="table_responsive">
            <h1>CLASS RECORD</h1>
            <hr>
        </div>

        <div class="subject-filters">
    
    <select id="subject-select" name="subject">
        <option value="" disabled selected>Select Subject</option>
        <option value="science">Science</option>
        <option value="math">Math</option>
        <option value="english">English</option>
    </select>
</div>

            
        <div class="table_responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Grade Level</th>
                        <th>Total Points</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Reynold Delizious</td>
                        <td>Minor</td>
                        <td>69</td>
                    </tr>
                </tbody>

               
            </table>
        </div>



                <div class="table_responsive">
                <h1></h1>
            </div>
                


        </div>
        

    </section>

    <script src="teacher-studs.js"></script>

</body>
</html>
