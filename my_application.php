<?php
session_start();
require_once 'database.php';

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'job_seeker') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get count of saved jobs for navigation
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM saved_jobs WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$saved_count = $stmt->get_result()->fetch_assoc()['count'];

// Fetch all applications for this user
$stmt = $conn->prepare("SELECT a.*, j.location, j.salary, j.description, j.deadline, 
                        c.company_name, jc.description as category
                        FROM application a
                        JOIN job j ON a.job_id = j.job_id
                        LEFT JOIN employee e ON j.user_id = e.user_id
                        LEFT JOIN company c ON e.company_id = c.company_id
                        LEFT JOIN job_category jc ON j.category_id = jc.category_id
                        WHERE a.user_id = ?
                        ORDER BY a.applied_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$applications = $result->fetch_all(MYSQLI_ASSOC);

// Handle application withdrawal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    $application_id = intval($_POST['application_id']);
    
    $conn->begin_transaction();
    try {
        // Get job_id before deleting
        $stmt = $conn->prepare("SELECT job_id FROM application WHERE application_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $application_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $app_data = $result->fetch_assoc();
        
        if ($app_data) {
            // Delete from application table
            $stmt = $conn->prepare("DELETE FROM application WHERE application_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $application_id, $user_id);
            $stmt->execute();
            
            // Delete from applies table
            $stmt2 = $conn->prepare("DELETE FROM applies WHERE user_id = ? AND job_id = ?");
            $stmt2->bind_param("ii", $user_id, $app_data['job_id']);
            $stmt2->execute();
            
            $conn->commit();
            header("Location: my_applications.php?msg=withdrawn");
            exit();
        }
    } catch (Exception $e) {
        $conn->rollback();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications</title>
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
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 30px;
            margin: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
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
        
        .applications-grid {
            display: grid;
            gap: 20px;
        }
        
        .application-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #667eea;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-accepted {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .card-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }
        
        .info-value {
            font-size: 14px;
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
        }
        
        .description-text {
            color: #666;
            line-height: 1.6;
        }
        
        .card-footer {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state h2 {
            color: #666;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #999;
            margin-bottom: 30px;
        }
        
        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .stats {
            display: flex;
            gap: 20px;
        }
        
        .stat-item {
            font-size: 14px;
            color: #666;
        }
        
        .stat-item strong {
            color: #667eea;
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
        <h1>My Job Applications</h1>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'withdrawn'): ?>
            <div class="message">Application withdrawn successfully.</div>
        <?php endif; ?>
        
        <div class="top-actions">
            <div class="stats">
                <div class="stat-item">
                    <strong><?php echo count($applications); ?></strong> Total Applications
                </div>
            </div>
            <a href="view_jobs.php" class="btn btn-primary">Browse More Jobs</a>
        </div>
        
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <h2>No Applications Yet</h2>
                <p>You haven't applied to any jobs yet. Start exploring available opportunities!</p>
                <a href="view_jobs.php" class="btn btn-primary">View Available Jobs</a>
            </div>
        <?php else: ?>
            <div class="applications-grid">
                <?php foreach ($applications as $app): ?>
                    <div class="application-card">
                        <div class="card-header">
                            <div>
                                <div class="company-name"><?php echo htmlspecialchars($app['company_name']); ?></div>
                                <div class="info-value"><?php echo htmlspecialchars($app['category']); ?></div>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                <?php echo htmlspecialchars($app['status']); ?>
                            </span>
                        </div>
                        
                        <div class="card-body">
                            <div class="info-item">
                                <span class="info-label">Location</span>
                                <span class="info-value"><?php echo htmlspecialchars($app['location']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Salary</span>
                                <span class="info-value">$<?php echo number_format($app['salary'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Applied On</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($app['applied_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Deadline</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($app['deadline'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="description">
                            <div class="description-label">Job Description</div>
                            <div class="description-text"><?php echo nl2br(htmlspecialchars(substr($app['description'], 0, 200))); ?><?php echo strlen($app['description']) > 200 ? '...' : ''; ?></div>
                        </div>
                        
                        <div class="card-footer">
                            <?php if (strtolower($app['status']) === 'pending'): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to withdraw this application?');" style="display: inline;">
                                    <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                    <button type="submit" name="withdraw" class="btn btn-danger">Withdraw Application</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>