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
        $fileName = basename($file['file_name']); // Prevent path traversal
        $filePath = "temp_downloads/$fileName";
        $needToDownload = true;

        // Check if the file exists in temp_downloads and is less than 7 days old
        if (file_exists($filePath)) {
            $fileAge = time() - filemtime($filePath);
            if ($fileAge < 7 * 24 * 60 * 60) { // 7 days in seconds
                $needToDownload = false;
            }
        }

        if ($needToDownload) {
            $accountId = $file['account_id'];
            $accountStmt = $conn->prepare("SELECT * FROM dropbox_accounts WHERE id = ?");
            $accountStmt->bind_param("s", $accountId);
            $accountStmt->execute();
            $accountResult = $accountStmt->get_result();
            $account = $accountResult->fetch_assoc();

            if ($account) {
                $accessToken = $account['access_token'];
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
                file_put_contents($filePath, $response);
            } else {
                echo "Account not found.";
                exit();
            }
        }

        // File download with resume support
        $fileSize = filesize($filePath);
        $offset = 0;
        $length = $fileSize;

        $isResume = false;
        if (isset($_SERVER['HTTP_RANGE'])) {
            $isResume = true;
            // Figure out download piece from range (if set)
            list($param, $range) = explode('=', $_SERVER['HTTP_RANGE']);
            if (strtolower(trim($param)) == 'bytes') {
                list($from, $to) = explode('-', $range);
                $offset = intval($from);
                $length = $fileSize - $offset;
                if (strpos($range, '-') !== false) {
                    $to = intval($to);
                    if ($to > 0) {
                        $length = $to - $offset + 1;
                    }
                }
            }
        }

        // Headers for resume support and speed download
        header("Accept-Ranges: bytes");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Content-Length: $length");

        if ($isResume) {
            header("HTTP/1.1 206 Partial Content");
            header("Content-Range: bytes $offset-" . ($offset + $length - 1) . "/$fileSize");
        }

        // Open the file
        $fp = fopen($filePath, 'rb');

        // Seek to the requested offset
        fseek($fp, $offset);

        // Start buffered download
        $buffer = 8192;
        $timer = microtime(true);
        $throttle = 1024 * 1024; // 1 MB per second

        $sentBytes = 0;
        $downloadCompleted = false;
        while (!feof($fp) && ($sentBytes < $length)) {
            $readLength = min($buffer, $length - $sentBytes);
            $data = fread($fp, $readLength);
            $sentBytes += strlen($data);

            echo $data;
            flush();

            // Throttle speed
            if ($sentBytes % $throttle == 0) {
                $elapsedTime = microtime(true) - $timer;
                if ($elapsedTime < 1) {
                    usleep((1 - $elapsedTime) * 1000000);
                }
                $timer = microtime(true);
            }

            if ($sentBytes >= $length) {
                $downloadCompleted = true;
            }
        }

        // Close the file
        fclose($fp);

        // Update last download timestamp, expiration timestamp, and download count
        $lastDownload = date('Y-m-d H:i:s');
        $expiration = date('Y-m-d H:i:s', strtotime('+7 days'));

        if ($downloadCompleted && !$isResume) {
            // Increment download count only for completed, non-resumed downloads
            $stmt = $conn->prepare("UPDATE files SET last_download = ?, expiration = ?, download_count = download_count + 1 WHERE id = ?");
            $stmt->bind_param("sss", $lastDownload, $expiration, $fileId);
        } else {
            // Update timestamps without incrementing download count for resumed or incomplete downloads
            $stmt = $conn->prepare("UPDATE files SET last_download = ?, expiration = ? WHERE id = ?");
            $stmt->bind_param("sss", $lastDownload, $expiration, $fileId);
        }
        $stmt->execute();

        // Log the download attempt
        $downloadStatus = $downloadCompleted ? 'completed' : 'incomplete';
        $resumeStatus = $isResume ? 'resumed' : 'new';
        $logStmt = $conn->prepare("INSERT INTO download_logs (file_id, download_status, resume_status, downloaded_bytes, timestamp) VALUES (?, ?, ?, ?, ?)");
        $logStmt->bind_param("sssss", $fileId, $downloadStatus, $resumeStatus, $sentBytes, $lastDownload);
        $logStmt->execute();

        exit();
    } else {
        echo "File not found.";
        exit();
    }
}

function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    }     elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        return $bytes;
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
    <header class="bg-blue-600 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
    <a href="/"><h1 class="text-2xl font-bold">FilesWith</h1></a>
        <nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="text-white hover:text-blue-200 mr-4">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </a>
                <a href="logout.php" class="text-white hover:text-blue-200">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            <?php else: ?>
                <a href="login.php" class="text-white hover:text-blue-200">
                    <i class="fas fa-lock mr-2"></i> Login
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>
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