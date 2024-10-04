<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config.php';

$fileId = $_GET['id'];
$result = $conn->query("SELECT * FROM files WHERE id = '$fileId' AND user_id = '{$_SESSION['user_id']}'");
$file = $result->fetch_assoc();

if ($file) {
    $accountId = $file['account_id'];
    $accountResult = $conn->query("SELECT * FROM dropbox_accounts WHERE id = '$accountId'");
    $account = $accountResult->fetch_assoc();
    $accessToken = $account['access_token'];
    $fileName = $file['file_name'];

    // Delete from Dropbox
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

    // Delete from temp_downloads folder
    $tempFilePath = "../temp_downloads/$fileName";
    if (file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }

    // Delete from database
    $conn->query("DELETE FROM files WHERE id = '$fileId'");

    header("Location: /dashboard.php");
    exit();
} else {
    echo "File not found.";
}
?>