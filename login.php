<?php
session_start();

// Check ONLY for user_id and user_type (not "user")
if (isset($_SESSION["user_id"]) && isset($_SESSION["user_type"])) {
    if ($_SESSION["user_type"] === "job_seeker") {
        header("Location: view_jobs.php");
        exit();
    } else {
        header("Location: create_job.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Find Jobs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    
</head>
<body>
    <div class="container">
        <h3 class="heading">Login</h3>
        <p>Sign in to your account</p>
        
        <?php
        if (isset($_POST["login"])) {
            $email = $_POST["email"];
            $password = $_POST["password"];
            
            require_once "database.php";
            
            // Use backticks around user table
            $sql = "SELECT * FROM `user` WHERE email = ?";
            $stmt = mysqli_stmt_init($conn);
            
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
                
                if ($user) {
                    if (password_verify($password, $user["password"])) {
                        // Check if user is job_seeker or employee
                        $checkSeeker = "SELECT * FROM job_seeker WHERE user_id = ?";
                        $stmtSeeker = mysqli_stmt_init($conn);
                        if (mysqli_stmt_prepare($stmtSeeker, $checkSeeker)) {
                            mysqli_stmt_bind_param($stmtSeeker, "i", $user["user_id"]);
                            mysqli_stmt_execute($stmtSeeker);
                            $resultSeeker = mysqli_stmt_get_result($stmtSeeker);
                            
                            // Set session variables - user_id is the main one
                            $_SESSION["user_id"] = $user["user_id"];
                            $_SESSION["email"] = $user["email"];
                            
                            if (mysqli_num_rows($resultSeeker) > 0) {
                                $_SESSION["user_type"] = "job_seeker";
                                header("Location: view_jobs.php");
                                exit();
                            } else {
                                $_SESSION["user_type"] = "employee";
                                header("Location: create_job.php");
                                exit();
                            }
                        }
                    } else {
                        echo "<div class='alert alert-danger'>Password does not match</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>Email does not exist</div>";
                }
            }
        }
        ?>
        
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="email"><b>Email</b></label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="form-group">
                <label for="password"><b>Password</b></label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <div class="form-button">
                <input type="submit" class="btn btn-primary" value="Login" name="login">
            </div>
        </form>
        <div class="mt-3">
            <p>Don't have an account? <a href="registration.php">Sign Up</a></p>
        </div>
    </div>
</body>
</html>