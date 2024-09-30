<?php
// cleanup.php

// Database configuration
$host = "207.244.240.126";
$username = "fileswith_com";
$password = "AmiMotiur27@";
$dbname = "fileswith_com";

// Configuration
$tempFolder = 'temp_downloads'; // Path to your temporary downloads folder

// Connect to the database
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current timestamp
$currentTime = date('Y-m-d H:i:s');

// Fetch expired files from the database
// Adjust the table name and column names as per your database structure
$query = "SELECT file_name FROM files WHERE expiration < ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $currentTime);
$stmt->execute();
$result = $stmt->get_result();

$removedCount = 0;
$errorCount = 0;

while ($row = $result->fetch_assoc()) {
    $fileName = $row['file_name'];
    $filePath = $tempFolder . '/' . $fileName;

    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            $removedCount++;
            echo "Removed expired file: $fileName\n";
        } else {
            $errorCount++;
            echo "Error removing file: $fileName\n";
        }
    } else {
        echo "File not found in temp folder: $fileName\n";
    }
}

$stmt->close();
$conn->close();

echo "Cleanup completed. Removed $removedCount files. Encountered $errorCount errors.\n";

// Log the cleanup results
$logMessage = date('Y-m-d H:i:s') . " - Cleanup: Removed $removedCount files. Errors: $errorCount\n";
file_put_contents('cleanup_log.txt', $logMessage, FILE_APPEND);