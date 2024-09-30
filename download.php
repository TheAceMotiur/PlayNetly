<?php
require_once 'config.php';
error_reporting(0);
// Retrieve the 10-letter text code from the URL
$code = $_GET['code'];

// Query the database to get the file ID based on the code
$stmt = $conn->prepare("SELECT * FROM files WHERE code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();

if ($file) {
    $fileName = $file['file_name'];
    $fileId = $file['id'];
    $fileSize = $file['file_size'];
    $downloadCount = $file['download_count'];
} else {
    echo "File not found.";
    exit();
}

// Handle the download if the file_id parameter is present
if (isset($_GET['file_id'])) {
    $fileId = $_GET['file_id'];

    // Prepare and execute query to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->bind_param("s", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();

    if ($file) {
        $accountId = $file['account_id'];
        $accountStmt = $conn->prepare("SELECT * FROM dropbox_accounts WHERE id = ?");
        $accountStmt->bind_param("s", $accountId);
        $accountStmt->execute();
        $accountResult = $accountStmt->get_result();
        $account = $accountResult->fetch_assoc();

        if ($account) {
            $accessToken = $account['access_token'];
            $fileName = basename($file['file_name']); // Prevent path traversal
            $url = 'https://content.dropboxapi.com/2/files/download';
            $headers = [
                "Authorization: Bearer $accessToken",
                "Dropbox-API-Arg: " . json_encode(["path" => "/$fileName"])
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Curl error: ' . curl_error($ch);
                curl_close($ch);
                exit();
            }
            curl_close($ch);

            // Save the file to the server
            $filePath = "temp_downloads/$fileName";
            file_put_contents($filePath, $response);

            // Update last download timestamp, expiration timestamp, and download count
            $lastDownload = date('Y-m-d H:i:s');
            $expiration = date('Y-m-d H:i:s', strtotime('+7 days'));
            $stmt = $conn->prepare("UPDATE files SET last_download = ?, expiration = ?, download_count = download_count + 1 WHERE id = ?");
            $stmt->bind_param("sss", $lastDownload, $expiration, $fileId);
            $stmt->execute();

            // Determine the MIME type of the file
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            // Serve the file to the user
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            readfile($filePath);
            exit();
        } else {
            echo "Account not found.";
            exit();
        }
    } else {
        echo "File not found.";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download File</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    <div class="container mx-auto p-4 bg-white shadow-md rounded-lg mt-10">
        <h1 class="text-3xl font-bold mb-4 text-blue-600">Download File</h1>
        <div class="file-info mb-4">
            <p class="mb-2"><strong>File Name:</strong> <?php echo htmlspecialchars($fileName); ?></p>
            <p class="mb-2"><strong>File Size:</strong> <?php echo htmlspecialchars($fileSize); ?> bytes</p>
            <p class="mb-2"><strong>Download Count:</strong> <?php echo htmlspecialchars($downloadCount); ?></p>
        </div>
        <a href="download.php?code=<?php echo urlencode($code); ?>&file_id=<?php echo urlencode($fileId); ?>" class="download-btn inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition duration-300">Download</a>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>