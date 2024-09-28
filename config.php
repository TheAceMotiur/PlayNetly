<?php
$host = "207.244.240.126";
$db_user = "playnetl_y";
$db_password = "AmiMotiur27@";
$db_name = "playnetl_y";

// Create connection
$conn = new mysqli($host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>