<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get count of saved jobs for navigation (only for job seekers)
$saved_count = 0;
if ($user_type === 'job_seeker') {
    $stmt_count = $conn->prepare("SELECT COUNT(*) as count FROM saved_jobs WHERE user_id = ?");
    $stmt_count->bind_param("i", $user_id);
    $stmt_count->execute();
    $saved_count = $stmt_count->get_result()->fetch_assoc()['count'];
}

$message = '';
$error = '';

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM `user` WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch job seeker specific data
$job_seeker = [
    'name' => '',
    'resume' => ''
];

$skills = [];

if ($user_type === 'job_seeker') {

    $stmt = $conn->prepare("SELECT name, resume FROM job_seeker WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $job_seeker['name']   = $row['name']   ?? '';
        $job_seeker['resume'] = $row['resume'] ?? '';
    }

    // Fetch skills
    $stmt = $conn->prepare("SELECT skill_id, name FROM skill WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $skills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}



// Fetch employee specific data
if ($user_type === 'employee') {
    $stmt = $conn->prepare("SELECT e.*, c.* FROM employee e 
                           JOIN company c ON e.company_id = c.company_id 
                           WHERE e.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user_type === 'job_seeker') {
        $name   = trim($_POST['name'] ?? '');
        $resume = trim($_POST['resume'] ?? '');
        
        
        // Check if job_seeker record exists
        $check_stmt = $conn->prepare("SELECT user_id FROM job_seeker WHERE user_id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $exists = $check_stmt->get_result()->num_rows > 0;
        
        if ($exists) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE job_seeker SET name = ?, resume = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $name, $resume, $user_id);
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO job_seeker (user_id, name, resume) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $name, $resume);
        }
        
        if ($stmt->execute()) {
            $message = "Profile updated successfully!";
            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM job_seeker WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $job_seeker = $result->fetch_assoc();
            if (!$job_seeker) {
                $job_seeker = ['name' => '', 'resume' => ''];
            }
        } else {
            $error = "Error updating profile.";
        }
    }
    
    // Handle skill addition
    if (isset($_POST['add_skill'])) {
        $skill_name = trim($_POST['skill_name']);
        if (!empty($skill_name)) {
            $stmt = $conn->prepare("INSERT INTO skill (name, user_id) VALUES (?, ?)");
            $stmt->bind_param("si", $skill_name, $user_id);
            if ($stmt->execute()) {
                $message = "Skill added successfully!";
                // Refresh skills
                $stmt = $conn->prepare("SELECT * FROM skill WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $skills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
    
    // Handle skill deletion
    if (isset($_POST['delete_skill'])) {
        $skill_id = intval($_POST['skill_id']);
        $stmt = $conn->prepare("DELETE FROM skill WHERE skill_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $skill_id, $user_id);
        if ($stmt->execute()) {
            $message = "Skill removed successfully!";
            // Refresh skills
            $stmt = $conn->prepare("SELECT * FROM skill WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $skills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE `user` SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                if ($stmt->execute()) {
                    $message = "Password changed successfully!";
                } else {
                    $error = "Error changing password.";
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding: 0;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .nav-brand {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            text-decoration: none;
        }
        
        .nav-right {
            display: flex;
            gap: 25px;
            align-items: center;
        }
        
        .nav-link {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            font-size: 14px;
        }
        
        .nav-link:hover {
            color: #667eea;
        }
        
        .nav-link.logout {
            color: #dc3545;
        }
        
        .nav-link.logout:hover {
            color: #c82333;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 30px;
            margin: 20px auto;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .user-email {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background: white;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: white;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .section {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-danger {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            font-size: 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .btn-danger:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .skill-tag {
            background: white;
            color: #333;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .skill-form {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .skill-form input {
            flex: 1;
        }
        
        .info-display {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
        }
        
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        
        .info-value {
            flex: 1;
            color: #333;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <a href="view_jobs.php" class="nav-brand">Job Portal</a>
        </div>
        <div class="nav-right">
            <?php if ($user_type === 'job_seeker'): ?>
                <a href="view_jobs.php" class="nav-link">Browse Jobs</a>
                <a href="my_applications.php" class="nav-link">My Applications</a>
                <a href="saved_jobs.php" class="nav-link">Saved Jobs (<?php echo $saved_count; ?>)</a>
            <?php else: ?>
                <a href="create_job.php" class="nav-link">Post a Job</a>
                <a href="my_jobs.php" class="nav-link">My Jobs</a>
            <?php endif; ?>
            <a href="profile.php" class="nav-link">Profile</a>
            <a href="logout.php" class="nav-link logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <h1>My Profile</h1>
        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($user_type === 'job_seeker'): ?>
            <!-- Job Seeker Profile -->
            <div class="section">
                <div class="section-title">Personal Information</div>
                <form method="POST">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($job_seeker['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Resume / Bio</label>
                        <textarea name="resume"><?php echo htmlspecialchars($job_seeker['resume'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
            
            <div class="section">
                <div class="section-title">My Skills</div>
                <?php if (!empty($skills)): ?>
                    <div class="skills-container">
                        <?php foreach ($skills as $skill): ?>
                            <div class="skill-tag">
                                <span><?php echo htmlspecialchars($skill['name']); ?></span>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                                    <button type="submit" name="delete_skill" class="btn-danger" 
                                            onclick="return confirm('Remove this skill?');">×</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #666; margin-bottom: 15px;">No skills added yet.</p>
                <?php endif; ?>
                
                <form method="POST" class="skill-form">
                    <input type="text" name="skill_name" placeholder="Enter a skill" required>
                    <button type="submit" name="add_skill" class="btn btn-primary">Add Skill</button>
                </form>
            </div>
            
        <?php elseif ($user_type === 'employee'): ?>
            <!-- Employee Profile -->
            <div class="section">
                <div class="section-title">Company Information</div>
                <div class="info-display">
                    <div class="info-row">
                        <span class="info-label">Company Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['company_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Description:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['description'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Change Password Section -->
        <div class="section">
            <div class="section-title">Change Password</div>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
            </form>
        </div>
        
        <div class="action-buttons">
            <?php if ($user_type === 'job_seeker'): ?>
                <a href="view_jobs.php" class="btn btn-secondary">Browse Jobs</a>
                <a href="my_applications.php" class="btn btn-secondary">My Applications</a>
            <?php else: ?>
                <a href="create_job.php" class="btn btn-secondary">Post a Job</a>
                <a href="my_jobs.php" class="btn btn-secondary">My Jobs</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>