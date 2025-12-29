<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "employee") {
    header("Location: login.php");
    exit();
}

require_once "database.php";

$userId = $_SESSION["user_id"];
$jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

// Verify this job belongs to the logged-in employer
$checkOwner = "SELECT j.*, jc.description as category_name 
               FROM job j 
               LEFT JOIN job_category jc ON j.category_id = jc.category_id
               WHERE j.job_id = ? AND j.user_id = ?";
$stmt = mysqli_stmt_init($conn);

if (!mysqli_stmt_prepare($stmt, $checkOwner)) {
    die("SQL Error");
}

mysqli_stmt_bind_param($stmt, "ii", $jobId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$job = mysqli_fetch_assoc($result);

if (!$job) {
    die("Job not found or you don't have permission to view applications for this job.");
}

// Get all applications for this job
$sql = "SELECT a.*, js.name as applicant_name, js.resume, u.email as applicant_email, a.applied_date
        FROM application a
        INNER JOIN job_seeker js ON a.user_id = js.user_id
        INNER JOIN user u ON js.user_id = u.user_id
        WHERE a.job_id = ?
        ORDER BY a.applied_date DESC";

$stmt = mysqli_stmt_init($conn);
if (mysqli_stmt_prepare($stmt, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $jobId);
    mysqli_stmt_execute($stmt);
    $applications = mysqli_stmt_get_result($stmt);
}

// Handle status update
if (isset($_POST['update_status'])) {
    $applicationId = intval($_POST['application_id']);
    $newStatus = $_POST['status'];
    
    $updateSql = "UPDATE application SET status = ? WHERE application_id = ?";
    $updateStmt = mysqli_stmt_init($conn);
    
    if (mysqli_stmt_prepare($updateStmt, $updateSql)) {
        mysqli_stmt_bind_param($updateStmt, "si", $newStatus, $applicationId);
        mysqli_stmt_execute($updateStmt);
        header("Location: view_applications.php?job_id=" . $jobId);
        exit();
    }
}

$applicationCount = mysqli_num_rows($applications);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .job-info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid ;
        }
        .application-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: box-shadow 0.3s;
        }
        
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending {
            background: #ffc107;
            color: #000;
        }
        .status-accepted {
            background: #28a745;
            color: white;
        }
        .status-rejected {
            background: #dc3545;
            color: white;
        }
        .applicant-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .applicant-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .applicant-email {
            color: #666;
            font-size: 14px;
        }
        .resume-section {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .resume-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 8px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="employee_dashboard.php">Find Jobs</a>
            <div class="d-flex">
                <a href="my_jobs.php" class="btn btn-outline-primary me-2">My Jobs</a>
                <a href="create_job.php" class="btn btn-outline-primary me-2">Post New Job</a>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="heading">Applications for Job</h3>
            <a href="my_jobs.php" class="btn btn-secondary">← Back to My Jobs</a>
        </div>
        
        <div class="job-info-box">
            <h5>Job Details</h5>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($job['category_name']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
            <p><strong>Salary:</strong> $<?php echo number_format($job['salary'], 2); ?></p>
            <p><strong>Deadline:</strong> <?php echo date('M d, Y', strtotime($job['deadline'])); ?></p>
            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
        </div>
        
        <h4 class="mb-4">Applications Received (<?php echo $applicationCount; ?>)</h4>
        
        <?php if ($applicationCount == 0): ?>
            <div class="empty-state">
                <h5>No Applications Yet</h5>
                <p>No one has applied to this job yet. Check back later!</p>
            </div>
        <?php else: ?>
            <?php while($app = mysqli_fetch_assoc($applications)): ?>
                <div class="application-card">
                    <div class="applicant-header">
                        <div>
                            <div class="applicant-name">
                                <?php echo htmlspecialchars($app['applicant_name'] ?: 'Name not provided'); ?>
                            </div>
                            <div class="applicant-email">
                                <?php echo htmlspecialchars($app['applicant_email']); ?>
                            </div>
                            <small class="text-muted">
                                Applied on: <?php echo date('M d, Y h:i A', strtotime($app['applied_date'])); ?>
                            </small>
                        </div>
                        <div>
                            <span class="status-badge status-<?php echo strtolower($app['status'] ?: 'pending'); ?>">
                                <?php echo htmlspecialchars($app['status'] ?: 'Pending'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($app['resume']): ?>
                        <div class="resume-section">
                            <div class="resume-label">Resume / Cover Letter:</div>
                            <div><?php echo nl2br(htmlspecialchars($app['resume'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                            <label for="status" class="form-label"><strong>Update Status:</strong></label>
                            <div class="d-flex gap-2">
                                <select name="status" class="form-select" style="width: 200px;">
                                    <option value="Pending" <?php echo ($app['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Accepted" <?php echo ($app['status'] == 'Accepted') ? 'selected' : ''; ?>>Accepted</option>
                                    <option value="Rejected" <?php echo ($app['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="Under Review" <?php echo ($app['status'] == 'Under Review') ? 'selected' : ''; ?>>Under Review</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</body>
</html>