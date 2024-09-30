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
    <title>Download File - FilesWith</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <?php include 'header.php'; ?>
    
    <main class="flex-grow container mx-auto mt-8 p-4">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">Download File</h1>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <div class="flex items-center mb-2">
                    <i class="fas fa-file-alt text-blue-500 mr-2 text-xl"></i>
                    <p class="font-semibold text-blue-700"><?php echo htmlspecialchars($fileName); ?></p>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                    <div>
                        <p><i class="fas fa-hdd mr-2"></i> <strong>File Size:</strong> <?php echo formatSizeUnits($fileSize); ?></p>
                    </div>
                    <div>
                        <p><i class="fas fa-download mr-2"></i> <strong>Downloads:</strong> <?php echo htmlspecialchars($downloadCount); ?></p>
                    </div>
                </div>
            </div>
            <a href="download.php?code=<?php echo urlencode($code); ?>&file_id=<?php echo urlencode($fileId); ?>" 
               class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg inline-flex items-center transition duration-300">
                <i class="fas fa-download mr-2"></i>
                Download File
            </a>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>