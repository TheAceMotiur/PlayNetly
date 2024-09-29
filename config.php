<?php
$host = "207.244.240.126";
$username = "playnetl_y";
$password = "AmiMotiur27@";
$dbname = "playnetl_y";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>