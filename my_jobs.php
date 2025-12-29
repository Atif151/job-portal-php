<?php
session_start();
// FIX 1: Change from $_SESSION["user"] to $_SESSION["user_id"]
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] != "employee") {
    header("Location: login.php");
    exit();
}

require_once "database.php";

// Handle delete job
if (isset($_POST["delete_job"])) {
    $jobId = $_POST["job_id"];
    // FIX 2: Change from $_SESSION["user"] to $_SESSION["user_id"]
    $userId = $_SESSION["user_id"];
    
    $sql = "DELETE FROM job WHERE job_id = ? AND user_id = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $jobId, $userId);
        mysqli_stmt_execute($stmt);
        $message = "<div class='alert alert-success'>Job deleted successfully!</div>";
    }
}

// FIX 3: Change from $_SESSION["user"] to $_SESSION["user_id"]
$userId = $_SESSION["user_id"];
$sql = "SELECT j.*, cat.description as category_name, 
        (SELECT COUNT(*) FROM application WHERE job_id = j.job_id) as application_count
        FROM job j 
        LEFT JOIN job_category cat ON j.category_id = cat.category_id 
        WHERE j.user_id = ?
        ORDER BY j.job_id DESC";

$stmt = mysqli_stmt_init($conn);
if (mysqli_stmt_prepare($stmt, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs - Find Jobs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <!-- BONUS FIX: Link to dashboard instead of create_job.php -->
            <a class="navbar-brand" href="employee_dashboard.php">Find Jobs</a>
            <div class="d-flex">
                <a href="create_job.php" class="btn btn-outline-primary me-2">Post New Job</a>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-5">
        <h3 class="heading">My Posted Jobs</h3>
        
        <?php if (isset($message)) echo $message; ?>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Description</th>
                    <th>Location</th>
                    <th>Salary</th>
                    <th>Deadline</th>
                    <th>Applications</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (mysqli_num_rows($result) > 0) {
                    while($job = mysqli_fetch_assoc($result)) { 
                ?>
                    <tr>
                        <td><?php echo $job['job_id']; ?></td>
                        <td><?php echo substr(htmlspecialchars($job['description']), 0, 50) . '...'; ?></td>
                        <td><?php echo htmlspecialchars($job['location']); ?></td>
                        <td>$<?php echo number_format($job['salary'], 2); ?></td>
                        <td><?php echo date('M d, Y', strtotime($job['deadline'])); ?></td>
                        <td><?php echo $job['application_count']; ?></td>
                        <td>
                            <a href="view_applications.php?job_id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-info">View Applications</a>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                <button type="submit" name="delete_job" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this job?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php 
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>No jobs posted yet</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>