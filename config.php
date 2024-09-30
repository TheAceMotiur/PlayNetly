<?php
$host = "207.244.240.126";
$username = "fileswith_com";
$password = "AmiMotiur27@";
$dbname = "fileswith_com";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>