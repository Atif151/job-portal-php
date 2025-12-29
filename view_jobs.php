<?php
session_start();
require_once 'database.php';

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'job_seeker') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle save/unsave action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = intval($_POST['job_id']);
    
    if (isset($_POST['save_job'])) {
        // Check if job is already saved
        $check_stmt = $conn->prepare("SELECT * FROM saved_jobs WHERE user_id = ? AND job_id = ?");
        $check_stmt->bind_param("ii", $user_id, $job_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            // Save the job only if not already saved
            $stmt = $conn->prepare("INSERT INTO saved_jobs (user_id, job_id, saved_date) VALUES (?, ?, NOW())");
            $stmt->bind_param("ii", $user_id, $job_id);
            
            if ($stmt->execute()) {
                $message = "The job is saved";
            }
        } else {
            $message = "Job is already saved";
        }
        $check_stmt->close();
    } elseif (isset($_POST['unsave_job'])) {
        // Unsave the job
        $stmt = $conn->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?");
        $stmt->bind_param("ii", $user_id, $job_id);
        
        if ($stmt->execute()) {
            $message = "Job removed from saved list";
        }
    }
}

// Get count of saved jobs
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM saved_jobs WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$saved_count = $stmt->get_result()->fetch_assoc()['count'];

// Fetch all jobs with saved status
$stmt = $conn->prepare("SELECT j.*, c.company_name, jc.description as category,
                        (SELECT COUNT(*) FROM saved_jobs WHERE user_id = ? AND job_id = j.job_id) as is_saved
                        FROM job j
                        LEFT JOIN employee e ON j.user_id = e.user_id
                        LEFT JOIN company c ON e.company_id = c.company_id
                        LEFT JOIN job_category jc ON j.category_id = jc.category_id
                        WHERE j.deadline >= CURDATE()
                        ORDER BY j.job_id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Jobs</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 30px;
            margin: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        h1 {
            color: #333;
        }
        
        .top-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .saved-info {
            font-size: 14px;
            color: #666;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
        }
        
        .saved-info strong {
            color: #667eea;
            font-size: 16px;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .jobs-grid {
            display: grid;
            gap: 25px;
        }
        
        .job-card {
            background: #fafafa;
            border-radius: 6px;
            padding: 25px;
            border-left: 4px solid #ddd;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left-color: #667eea;
        }
        
        .job-header {
            margin-bottom: 15px;
        }
        
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .category {
            color: #666;
            font-size: 14px;
        }
        
        .job-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }
        
        .detail-value {
            font-size: 15px;
            color: #333;
            font-weight: 500;
        }
        
        .description {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .description-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .description-text {
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .job-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 500;
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
        
        .btn-save {
            background: #28a745;
            color: white;
        }
        
        .btn-save:hover {
            background: #218838;
        }
        
        .btn-saved {
            background: #ffc107;
            color: #333;
        }
        
        .btn-saved:hover {
            background: #e0a800;
        }
        
        .btn-link {
            background: #17a2b8;
            color: white;
        }
        
        .btn-link:hover {
            background: #138496;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-state h2 {
            color: #666;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="nav-left">
                <a href="job_seeker_dashboard.php" class="nav-brand">Job Portal</a>
            </div>
            <div class="nav-right">
                <a href="view_jobs.php" class="nav-link">Browse Jobs</a>
                <a href="my_application.php" class="nav-link">My Applications</a>
                <a href="saved_jobs.php" class="nav-link">Saved Jobs (<?php echo $saved_count; ?>)</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="logout.php" class="nav-link logout">Logout</a>
            </div>
        </nav>
        
        <div class="page-header">
            <h1>Available Jobs</h1>
            <div class="saved-info">
                You have saved <strong><?php echo $saved_count; ?></strong> job<?php echo $saved_count != 1 ? 's' : ''; ?>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (empty($jobs)): ?>
            <div class="empty-state">
                <h2>No Jobs Available</h2>
                <p>There are currently no open positions. Please check back later.</p>
            </div>
        <?php else: ?>
            <div class="jobs-grid">
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-header">
                            <div class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></div>
                            <div class="category"><?php echo htmlspecialchars($job['category']); ?></div>
                        </div>
                        
                        <div class="job-details">
                            <div class="detail-item">
                                <span class="detail-label">Location</span>
                                <span class="detail-value"><?php echo htmlspecialchars($job['location']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Salary</span>
                                <span class="detail-value">$<?php echo number_format($job['salary'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Deadline</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="description">
                            <div class="description-label">Job Description</div>
                            <div class="description-text">
                                <?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 200))); ?>
                                <?php echo strlen($job['description']) > 200 ? '...' : ''; ?>
                            </div>
                        </div>
                        
                        <div class="job-actions">
                            <a href="apply_job.php?job_id=<?php echo $job['job_id']; ?>" class="btn btn-primary">Apply</a>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                <?php if ($job['is_saved'] > 0): ?>
                                    <button type="submit" name="unsave_job" class="btn btn-saved">Saved</button>
                                <?php else: ?>
                                    <button type="submit" name="save_job" class="btn btn-save">Save for Later</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>