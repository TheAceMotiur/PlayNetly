<?php
// Configuration
$config = [
    'cleanup_interval_minutes' => 60,
    'db_host' => '207.244.240.126',
    'db_user' => 'fileswith_com',
    'db_password' => 'AmiMotiur27@',
    'db_name' => 'fileswith_com',
    'directory' => 'uploads'
];

// Directory to clean up
$directory = $config['directory'];
$maxAgeMinutes = $config['cleanup_interval_minutes']; // Maximum age in minutes

// Database connection
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Remove files from uploads directory
$files = glob("$directory/*");
$currentTime = time();
foreach ($files as $file) {
    if (is_file($file)) {
        $fileAgeMinutes = ($currentTime - filemtime($file)) / 60;
        if ($fileAgeMinutes > $maxAgeMinutes) {
            unlink($file);
        }
    }
}

// Remove files from Dropbox and database
$result = $conn->query("SELECT * FROM files WHERE TIMESTAMPDIFF(MINUTE, upload_time, NOW()) > $maxAgeMinutes AND download_count = 0");
if ($result) {
    while ($file = $result->fetch_assoc()) {
        $fileId = $file['id'];
        $accountId = $file['account_id'];
        $fileName = $file['file_name'];

        // Get Dropbox account details
        $accountResult = $conn->query("SELECT * FROM dropbox_accounts WHERE id = '$accountId'");
        if ($accountResult) {
            $account = $accountResult->fetch_assoc();
            $accessToken = $account['access_token'];

            // Delete file from Dropbox
            $url = 'https://api.dropboxapi.com/2/files/delete_v2';
            $headers = [
                "Authorization: Bearer $accessToken",
                "Content-Type: application/json"
            ];
            $data = json_encode(["path" => "/$fileName"]);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            // Delete file record from database
            $conn->query("DELETE FROM files WHERE id = '$fileId'");
        }
    }
}

$conn->close();
?>