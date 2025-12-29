<?php
session_start();
require_once 'database.php';

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'job_seeker') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

// Get count of saved jobs for navigation
$stmt_count = $conn->prepare("SELECT COUNT(*) as count FROM saved_jobs WHERE user_id = ?");
$stmt_count->bind_param("i", $user_id);
$stmt_count->execute();
$saved_count = $stmt_count->get_result()->fetch_assoc()['count'];

if ($job_id <= 0) {
    header("Location: view_jobs.php");
    exit();
}

// Fetch job details
$stmt = $conn->prepare("SELECT j.*, c.company_name, jc.description as category, e.user_id as employer_id 
                        FROM job j 
                        LEFT JOIN employee e ON j.user_id = e.user_id
                        LEFT JOIN company c ON e.company_id = c.company_id
                        LEFT JOIN job_category jc ON j.category_id = jc.category_id
                        WHERE j.job_id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();

if (!$job) {
    header("Location: view_jobs.php");
    exit();
}

// Check if already applied
$check_stmt = $conn->prepare("SELECT * FROM application WHERE job_id = ? AND user_id = ?");
$check_stmt->bind_param("ii", $job_id, $user_id);
$check_stmt->execute();
$already_applied = $check_stmt->get_result()->num_rows > 0;

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_applied) {
    $conn->begin_transaction();
    
    try {
        // Insert into application table
        $stmt = $conn->prepare("INSERT INTO application (job_id, user_id, status, applied_date) VALUES (?, ?, 'Pending', NOW())");
        $stmt->bind_param("ii", $job_id, $user_id);
        $stmt->execute();
        
        // Insert into applies table
        $stmt2 = $conn->prepare("INSERT INTO applies (user_id, job_id, applied_date) VALUES (?, ?, NOW())");
        $stmt2->bind_param("ii", $user_id, $job_id);
        $stmt2->execute();
        
        // Create notification for employer
        $notif_message = "New application received for job ID: " . $job_id;
        $stmt3 = $conn->prepare("INSERT INTO notification (user_id, date, message) VALUES (?, NOW(), ?)");
        $stmt3->bind_param("is", $job['employer_id'], $notif_message);
        $stmt3->execute();
        
        $conn->commit();
        $message = "Application submitted successfully!";
        $already_applied = true;
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error submitting application. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Job</title>
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
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 30px;
            margin: 20px auto;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .job-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        
        .job-details h2 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .confirmation-box {
            background: #e7f3ff;
            border: 2px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .confirmation-box p {
            margin-bottom: 15px;
            color: #333;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <a href="view_jobs.php" class="nav-brand">Job Portal</a>
        </div>
        <div class="nav-right">
            <a href="view_jobs.php" class="nav-link">Browse Jobs</a>
            <a href="my_applications.php" class="nav-link">My Applications</a>
            <a href="saved_jobs.php" class="nav-link">Saved Jobs (<?php echo $saved_count; ?>)</a>
            <a href="profile.php" class="nav-link">Profile</a>
            <a href="logout.php" class="nav-link logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <h1>Apply for Job</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="job-details">
            <h2>Job Details</h2>
            <div class="detail-row">
                <span class="detail-label">Company:</span>
                <span class="detail-value"><?php echo htmlspecialchars($job['company_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Location:</span>
                <span class="detail-value"><?php echo htmlspecialchars($job['location']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Category:</span>
                <span class="detail-value"><?php echo htmlspecialchars($job['category']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Salary:</span>
                <span class="detail-value">$<?php echo number_format($job['salary'], 2); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Deadline:</span>
                <span class="detail-value"><?php echo date('F d, Y', strtotime($job['deadline'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Description:</span>
                <span class="detail-value"><?php echo nl2br(htmlspecialchars($job['description'])); ?></span>
            </div>
        </div>
        
        <?php if ($already_applied): ?>
            <div class="message warning">
                You have already applied for this job. Check your applications page for status updates.
            </div>
        <?php else: ?>
            <div class="confirmation-box">
                <p><strong>Ready to apply?</strong></p>
                <p>By clicking "Submit Application", you confirm that you want to apply for this position. Your profile information and resume will be sent to the employer.</p>
            </div>
            
            <form method="POST">
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                    <a href="view_jobs.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
        
        <?php if ($already_applied): ?>
            <div class="button-group">
                <a href="my_applications.php" class="btn btn-success">View My Applications</a>
                <a href="view_jobs.php" class="btn btn-secondary">Back to Jobs</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>