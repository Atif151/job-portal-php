<?php
$dbHost = "localhost";
$dbUser = "root";              // Your MySQL username
$dbPassword = "";              // Your MySQL password
$dbName = "find_jobs";         // Your database name

$conn = mysqli_connect($dbHost, $dbUser, $dbPassword, $dbName);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>