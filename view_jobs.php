<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

// Handle job application
if (isset($_POST["apply_job"])) {
    $jobId = $_POST["job_id"];
    $userId = $_SESSION["user"];
    
    // Check if already applied
    $checkSql = "SELECT * FROM application WHERE job_id = ? AND user_id = ?";
    $checkStmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($checkStmt, $checkSql)) {
        mysqli_stmt_bind_param($checkStmt, "ii", $jobId, $userId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        
        if (mysqli_num_rows($checkResult) > 0) {
            $message = "<div class='alert alert-warning'>You have already applied for this job!</div>";
        } else {
            $sql = "INSERT INTO application (job_id, user_id, status, applied_date) VALUES (?, ?, 'pending', NOW())";
            $stmt = mysqli_stmt_init($conn);
            
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "ii", $jobId, $userId);
                mysqli_stmt_execute($stmt);
                $message = "<div class='alert alert-success'>Application submitted successfully!</div>";
            }
        }
    }
}

// Handle save job
if (isset($_POST["save_job"])) {
    $jobId = $_POST["job_id"];
    $userId = $_SESSION["user"];
    
    // Check if already saved
    $checkSql = "SELECT * FROM saved_jobs WHERE user_id = ? AND job_id = ?";
    $checkStmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($checkStmt, $checkSql)) {
        mysqli_stmt_bind_param($checkStmt, "ii", $userId, $jobId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        
        if (mysqli_num_rows($checkResult) > 0) {
            $message = "<div class='alert alert-warning'>Job already saved!</div>";
        } else {
            $sql = "INSERT INTO saved_jobs (user_id, job_id, saved_date) VALUES (?, ?, NOW())";
            $stmt = mysqli_stmt_init($conn);
            
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "ii", $userId, $jobId);
                mysqli_stmt_execute($stmt);
                $message = "<div class='alert alert-success'>Job saved successfully!</div>";
            }
        }
    }
}

// Get all jobs with company info
$sql = "SELECT j.*, c.company_name, cat.description as category_name 
        FROM job j 
        JOIN employee e ON j.user_id = e.user_id 
        JOIN company c ON e.company_id = c.company_id 
        LEFT JOIN job_category cat ON j.category_id = cat.category_id 
        WHERE j.deadline >= CURDATE()
        ORDER BY j.job_id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Jobs - Find Jobs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .job-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: rgba(100,100,111,0.2) 0px 7px 29px 0px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="view_jobs.php">Find Jobs</a>
            <div class="d-flex">
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION["email"]; ?></span>
                <a href="my_applications.php" class="btn btn-outline-primary me-2">My Applications</a>
                <a href="saved_jobs.php" class="btn btn-outline-secondary me-2">Saved Jobs</a>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-5">
        <h3 class="heading">Available Jobs</h3>
        
        <?php if (isset($message)) echo $message; ?>
        
        <div class="row">
            <?php 
            if (mysqli_num_rows($result) > 0) {
                while($job = mysqli_fetch_assoc($result)) { 
            ?>
                <div class="col-md-12">
                    <div class="job-card">
                        <h5><?php echo htmlspecialchars($job['company_name']); ?></h5>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($job['category_name']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
                        <p><strong>Salary:</strong> $<?php echo number_format($job['salary'], 2); ?></p>
                        <p><strong>Deadline:</strong> <?php echo date('F d, Y', strtotime($job['deadline'])); ?></p>
                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                        
                        <div class="d-flex gap-2">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                <button type="submit" name="apply_job" class="btn btn-primary">Apply Now</button>
                            </form>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                <button type="submit" name="save_job" class="btn btn-secondary">Save for Later</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php 
                }
            } else {
                echo "<div class='alert alert-info'>No jobs available at the moment.</div>";
            }
            ?>
        </div>
    </div>
</body>
</html>