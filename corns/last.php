<?php
require_once '../config.php'; // Adjusted the path to 'config.php' assuming it's in the same directory

function removeFileFromDropbox($accessToken, $fileName) {
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
    return json_decode($response, true);
}

$xMinutes = 1; // Set the number of minutes after which files should be removed
$expirationTime = date('Y-m-d H:i:s', strtotime("-$xMinutes minutes"));

$query = "SELECT * FROM files WHERE last_download < '$expirationTime'";
$result = $conn->query($query);

while ($file = $result->fetch_assoc()) {
    $fileId = $file['id'];
    $fileName = $file['file_name'];
    $accountId = $file['account_id'];

    // Remove file from Dropbox
    $accountResult = $conn->query("SELECT * FROM dropbox_accounts WHERE id = '$accountId'");
    $account = $accountResult->fetch_assoc();
    $accessToken = $account['access_token'];
    $dropboxResponse = removeFileFromDropbox($accessToken, $fileName);

    if (isset($dropboxResponse['error'])) {
        echo "Failed to remove file from Dropbox: " . json_encode($dropboxResponse) . "\n";
        echo "File name: $fileName\n";
        continue;
    }

    // Remove file from temp_downloads directory
    $tempFilePath = "../temp_downloads/$fileName";
    if (file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }

    // Remove file record from database
    $conn->query("DELETE FROM files WHERE id = '$fileId'");
}

echo "Old files removed successfully.";
?>