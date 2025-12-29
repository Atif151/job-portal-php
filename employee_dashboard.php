
        
<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "employee") {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employer Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Employer Dashboard</h1>
        <p>Welcome back! Manage your job postings.</p>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5>Create Job Post</h5>
                        <p>Post a new job opening</p>
                        <a href="create_job.php" class="btn btn-primary">Create Job</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5>Manage Jobs</h5>
                        <p>View and edit your posts</p>
                        <a href="my_jobs.php" class="btn btn-primary">Manage</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</body>
</html>