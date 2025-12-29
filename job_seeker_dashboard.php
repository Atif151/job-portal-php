<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "job_seeker") {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Job Seeker Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Job Seeker Dashboard</h1>
        <p>Welcome! Find your next opportunity.</p>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Browse Jobs</h5>
                        <p>View available positions</p>
                        <a href="view_jobs.php" class="btn btn-primary">Browse</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>My Applications</h5>
                        <p>Track your applications</p>
                        <a href="my_application.php" class="btn btn-primary">View</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>My Profile</h5>
                        <p>Update your information</p>
                        <a href="profile.php" class="btn btn-primary">Edit</a>
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