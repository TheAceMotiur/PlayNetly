<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $fileSize = $file['size'];
    $fileName = $file['name'];
    $fileTmpPath = $file['tmp_name'];

    error_log("File upload initiated. File size: $fileSize bytes. File name: $fileName.");

    $account = getDropboxAccountWithSpace($fileSize);
    if ($account) {
        $accessToken = $account['access_token'];
        error_log("Dropbox account found. Account ID: {$account['id']}. Access token: $accessToken.");

        $uploadResult = uploadFileToDropbox($accessToken, $fileTmpPath, $fileName);
        if ($uploadResult && isset($uploadResult['id'])) {
            error_log("File uploaded successfully to Dropbox. Upload result: " . json_encode($uploadResult));

            $fileId = saveFileMetadata($fileName, $fileSize, $account['id']);
            if ($fileId) {
                $uniqueCode = generateUniqueCode();
                saveFileCode($fileId, $uniqueCode);
                $domain = $_SERVER['HTTP_HOST'];
                $_SESSION['download_link'] = "http://$domain/download.php?code=$uniqueCode";
                $_SESSION['success_message'] = "File uploaded successfully!";
                header("Location: ../index.php");
                exit();
            } else {
                error_log("Error saving file metadata.");
                echo "Error saving file metadata.";
            }
        } else {
            error_log("Error uploading file to Dropbox. Upload result: " . json_encode($uploadResult));
            echo "Error uploading file to Dropbox.";
        }
    } else {
        error_log("No Dropbox account has enough space for this file. Required: $fileSize bytes.");
        echo "No Dropbox account has enough space for this file.";
    }
}

function getDropboxAccountWithSpace($fileSize) {
    global $conn;
    $result = $conn->query("SELECT * FROM dropbox_accounts");
    while ($account = $result->fetch_assoc()) {
        $accessToken = $account['access_token'];
        $spaceInfo = getDropboxSpaceInfo($accessToken);
        if ($spaceInfo) {
            $allocated = $spaceInfo['allocation']['allocated'];
            $used = $spaceInfo['used'];
            $available = $allocated - $used;
            error_log("Account ID: {$account['id']} - Allocated: $allocated, Used: $used, Available: $available, Required: $fileSize");
            if ($available >= $fileSize) {
                return $account;
            }
        } else {
            error_log("Failed to retrieve space info for Account ID: {$account['id']}");
        }
    }
    return null;
}

function getDropboxSpaceInfo($accessToken) {
    $url = 'https://api.dropboxapi.com/2/users/get_space_usage';
    $headers = [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "null");  // Send "null" as the payload
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('Curl error: ' . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

function uploadFileToDropbox($accessToken, $fileTmpPath, $fileName) {
    $url = 'https://content.dropboxapi.com/2/files/upload';
    $headers = [
        "Authorization: Bearer $accessToken",
        "Dropbox-API-Arg: " . json_encode(["path" => "/$fileName", "mode" => "add", "autorename" => true, "mute" => false]),
        "Content-Type: application/octet-stream"
    ];
    $fileData = file_get_contents($fileTmpPath);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('Curl error: ' . curl_error($ch));
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    error_log("Dropbox API response code: $httpCode. Response: $response");
    return json_decode($response, true);
}

function saveFileMetadata($fileName, $fileSize, $accountId) {
    global $conn;
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO files (user_id, account_id, file_name, file_size) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        error_log('Prepare failed: ' . $conn->error);
        return false;
    }
    $stmt->bind_param("sisi", $userId, $accountId, $fileName, $fileSize);
    if (!$stmt->execute()) {
        error_log('Execute failed: ' . $stmt->error);
        return false;
    }
    return $stmt->insert_id;
}

function saveFileCode($fileId, $code) {
    global $conn;
    $stmt = $conn->prepare("UPDATE files SET code = ? WHERE id = ?");
    if (!$stmt) {
        error_log('Prepare failed: ' . $conn->error);
        return false;
    }
    $stmt->bind_param("si", $code, $fileId);
    if (!$stmt->execute()) {
        error_log('Execute failed: ' . $stmt->error);
        return false;
    }
    return true;
}

function generateUniqueCode() {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
}

function generateDownloadLink($fileId) {
    $domain = $_SERVER['HTTP_HOST'];
    return "http://$domain/download.php?id=" . urlencode($fileId);
}
?>