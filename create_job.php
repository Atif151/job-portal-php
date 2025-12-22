<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user_type"] != "employee") {
    header("Location: login.php");
    exit();
}

require_once "database.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Job - Find Jobs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="create_job.php">Find Jobs</a>
            <div class="d-flex">
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION["email"]; ?></span>
                <a href="my_jobs.php" class="btn btn-outline-primary me-2">My Jobs</a>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-5">
        <h3 class="heading">Post a New Job</h3>
        
        <?php
        if (isset($_POST["create_job"])) {
            $description = $_POST["description"];
            $salary = $_POST["salary"];
            $location = $_POST["location"];
            $deadline = $_POST["deadline"];
            $categoryId = $_POST["category_id"];
            $userId = $_SESSION["user"];
            
            $errors = array();
            
            if (empty($description) || empty($salary) || empty($location) || empty($deadline)) {
                array_push($errors, "All fields are required");
            }
            
            if (count($errors) > 0) {
                foreach($errors as $error){
                    echo "<div class='alert alert-danger'>$error</div>";
                }
            } else {
                $sql = "INSERT INTO job (description, salary, location, deadline, user_id, category_id) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_stmt_init($conn);
                
                if (mysqli_stmt_prepare($stmt, $sql)) {
                    mysqli_stmt_bind_param($stmt, "sdssii", $description, $salary, $location, $deadline, $userId, $categoryId);
                    mysqli_stmt_execute($stmt);
                    echo "<div class='alert alert-success'>Job posted successfully!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Failed to post job</div>";
                }
            }
        }
        
        // Get categories
        $sql = "SELECT * FROM job_category";
        $result = mysqli_query($conn, $sql);
        ?>
        
        <form action="create_job.php" method="post">
            <div class="form-group">
                <label for="description"><b>Job Description</b></label>
                <textarea class="form-control" name="description" rows="5" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="category_id"><b>Job Category</b></label>
                <select class="form-control" name="category_id" required>
                    <option value="">Select Category...</option>
                    <?php while($category = mysqli_fetch_assoc($result)) { ?>
                        <option value="<?php echo $category['category_id']; ?>">
                            <?php echo htmlspecialchars($category['description']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="salary"><b>Salary</b></label>
                <input type="number" class="form-control" name="salary" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="location"><b>Location</b></label>
                <input type="text" class="form-control" name="location" required>
            </div>
            
            <div class="form-group">
                <label for="deadline"><b>Application Deadline</b></label>
                <input type="date" class="form-control" name="deadline" required>
            </div>
            
            <div class="form-button">
                <input type="submit" class="btn btn-primary" value="Post Job" name="create_job">
            </div>
        </form>
    </div>
</body>
</html>