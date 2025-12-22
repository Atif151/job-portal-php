<?php
session_start();

$selectedType = $_POST["user_type"] ?? "";

if (isset($_SESSION["user"])) {
    header("Location: view_jobs.php");
    exit();
}
?>

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Find Jobs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

</head>
<body>
    <div class="container">
        <h3 class="heading">Sign Up</h3>
        <p>Create an account</p>
        
        <?php
        if(isset($_POST["submit"])){
            $email = $_POST["email"];
            $password = $_POST["password"] ?? "";
            $repeatPassword = $_POST["repeat-password"] ?? "";

            $userType = $_POST["user_type"];
            $name = $_POST["name"];

            $errors = array();
            
            if (empty($email) || empty($password) || empty($repeatPassword) || empty($userType) || empty($name)) {
                array_push($errors, "All fields are required");
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                array_push($errors, "Email is not valid");
            }
            if ($selectedType != "") {

            if (empty($password) || empty($repeatPassword)) {
            $errors[] = "Password fields are required";}

            if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";}

            if ($password !== $repeatPassword) {
            $errors[] = "Password does not match";}
}

            
            // Check company name for employers
            if ($userType == "employee" && empty($_POST["company_name"])) {
                array_push($errors, "Company name is required for employers");
            }
            
            require_once "database.php";
            
            // Use backticks around user table
            $sql = "SELECT * FROM `user` WHERE email = ?";
            $stmt = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $rowCount = mysqli_num_rows($result);
                
                if ($rowCount > 0){
                    array_push($errors, "Email already exists!");
                }
            }
            
            if (count($errors) > 0) {
                foreach($errors as $error){
                    echo "<div class='alert alert-danger'>$error</div>";
                }
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert into user table with backticks
                $sql = "INSERT INTO `user` (email, password) VALUES (?, ?)";
                $stmt = mysqli_stmt_init($conn);
                
                if (mysqli_stmt_prepare($stmt, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ss", $email, $passwordHash);
                    mysqli_stmt_execute($stmt);
                    
                    $userId = mysqli_insert_id($conn);
                    
                    // Insert into job_seeker or employee table
                    if ($userType == "job_seeker") {
                        $sql = "INSERT INTO job_seeker (user_id, name) VALUES (?, ?)";
                        $stmt = mysqli_stmt_init($conn);
                        if (mysqli_stmt_prepare($stmt, $sql)) {
                            mysqli_stmt_bind_param($stmt, "is", $userId, $name);
                            mysqli_stmt_execute($stmt);
                        }
                    } else {
                        $companyName = $_POST["company_name"];
                        
                        // Check if company exists
                        $sql = "SELECT company_id FROM company WHERE company_name = ?";
                        $stmt = mysqli_stmt_init($conn);
                        if (mysqli_stmt_prepare($stmt, $sql)) {
                            mysqli_stmt_bind_param($stmt, "s", $companyName);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            if (mysqli_num_rows($result) > 0) {
                                $row = mysqli_fetch_assoc($result);
                                $companyId = $row['company_id'];
                            } else {
                                // Create new company
                                $sql = "INSERT INTO company (company_name) VALUES (?)";
                                $stmt = mysqli_stmt_init($conn);
                                if (mysqli_stmt_prepare($stmt, $sql)) {
                                    mysqli_stmt_bind_param($stmt, "s", $companyName);
                                    mysqli_stmt_execute($stmt);
                                    $companyId = mysqli_insert_id($conn);
                                }
                            }
                            
                            // Insert into employee table
                            $sql = "INSERT INTO employee (user_id, company_id) VALUES (?, ?)";
                            $stmt = mysqli_stmt_init($conn);
                            if (mysqli_stmt_prepare($stmt, $sql)) {
                                mysqli_stmt_bind_param($stmt, "ii", $userId, $companyId);
                                mysqli_stmt_execute($stmt);
                            }
                        }
                    }
                    
                    echo "<div class='alert alert-success'>You are registered successfully. <a href='login.php'>Login here</a></div>";
                } else {
                    die("OOPS!!! Something went wrong");
                }
            }
        }
        
        // Get the selected user type to show appropriate fields
        $selectedType = isset($_POST["user_type"]) ? $_POST["user_type"] : "";
        ?>
        
        <form action="registration.php" method="post">
            <div class="form-group">
                <label for="name"><b>Full Name</b></label>
                <input type="text" class="form-control" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email"><b>Email</b></label>
                <input type="email" class="form-control" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="user_type"><b>I am a:</b></label>
                <select class="form-control" name="user_type" required>
                    <option value="">Select...</option>
                    <option value="job_seeker" <?php echo $selectedType == "job_seeker" ? "selected" : ""; ?>>Job Seeker</option>
                    <option value="employee" <?php echo $selectedType == "employee" ? "selected" : ""; ?>>Employer</option>
                </select>
            </div>
            
            <?php if ($selectedType == "employee") { ?>
            <div class="form-group">
                <label for="company_name"><b>Company Name</b></label>
                <input type="text" class="form-control" name="company_name" value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>" required>
            </div>
            <?php } ?>
            
            <?php if ($selectedType != "") { ?>
            <div class="form-group">
                <label for="password"><b>Password</b></label>
                <input type="password" class="form-control" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="repeat-password"><b>Repeat Password</b></label>
                <input type="password" class="form-control" name="repeat-password" required>
            </div>
            <?php } ?>
            
            <div class="form-button">
                <input type="submit" class="btn btn-primary" value="<?php echo $selectedType == '' ? 'Continue' : 'Sign Up'; ?>" name="submit">
            </div>
        </form>
        <div class="mt-3">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>
        