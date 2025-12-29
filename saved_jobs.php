<?php
session_start();
require_once 'database.php';

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'job_seeker') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle unsave action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unsave'])) {
    $job_id = intval($_POST['job_id']);
    
    $stmt = $conn->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $stmt->bind_param("ii", $user_id, $job_id);
    
    if ($stmt->execute()) {
        header("Location: saved_jobs.php?msg=unsaved");
        exit();
    }
}

// Fetch all saved jobs
$stmt = $conn->prepare("SELECT sj.*, j.location, j.salary, j.description, j.deadline, 
                        c.company_name, jc.description as category, j.job_id
                        FROM saved_jobs sj
                        JOIN job j ON sj.job_id = j.job_id
                        LEFT JOIN employee e ON j.user_id = e.user_id
                        LEFT JOIN company c ON e.company_id = c.company_id
                        LEFT JOIN job_category jc ON j.category_id = jc.category_id
                        WHERE sj.user_id = ?
                        ORDER BY sj.saved_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$saved_jobs = $result->fetch_all(MYSQLI_ASSOC);

$saved_count = count($saved_jobs);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Jobs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: white;
            min-height: 100vh;
            padding: 0;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 30px;
            margin: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        h1 {
            color: #333;
        }
        
        .saved-count {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .top-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-state h2 {
            color: #666;
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        .empty-state p {
            color: #999;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .jobs-grid {
            display: grid;
            gap: 20px;
        }
        
        .job-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            border-left: 4px solid #667eea;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .category {
            color: #666;
            font-size: 14px;
        }
        
        .saved-date {
            font-size: 12px;
            color: #999;
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 5px;
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
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Saved Jobs</h1>
            <div class="saved-count">You have saved <?php echo $saved_count; ?> job<?php echo $saved_count != 1 ? 's' : ''; ?></div>
        </div>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'unsaved'): ?>
            <div class="message">Job removed from saved list.</div>
        <?php endif; ?>
        
        <div class="top-actions">
            <a href="view_jobs.php" class="btn btn-secondary">Back to All Jobs</a>
        </div>
        
        <?php if (empty($saved_jobs)): ?>
            <div class="empty-state">
                <h2>No jobs has saved yet</h2>
                <p>Start saving jobs that interest you to view them here later!</p>
                <a href="view_jobs.php" class="btn btn-primary">Browse Available Jobs</a>
            </div>
        <?php else: ?>
            <div class="jobs-grid">
                <?php foreach ($saved_jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-header">
                            <div>
                                <div class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></div>
                                <div class="category"><?php echo htmlspecialchars($job['category']); ?></div>
                            </div>
                            <div class="saved-date">
                                Saved: <?php echo date('M d, Y', strtotime($job['saved_date'])); ?>
                            </div>
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
                                <?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 250))); ?>
                                <?php echo strlen($job['description']) > 250 ? '...' : ''; ?>
                            </div>
                        </div>
                        
                        <div class="job-actions">
                            <a href="apply_job.php?job_id=<?php echo $job['job_id']; ?>" class="btn btn-primary">Apply Now</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                <button type="submit" name="unsave" class="btn btn-danger" 
                                        onclick="return confirm('Remove this job from saved list?');">
                                    Remove from Saved
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>