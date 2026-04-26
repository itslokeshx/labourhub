<?php
$host     = "localhost";
$user     = "root";
$password = "";
$database = "hiring_portal";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
