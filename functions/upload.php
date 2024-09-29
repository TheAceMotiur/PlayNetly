<?php
use Spatie\Dropbox\Client as DropboxClient;

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Capture all output
ob_start();

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in.');
    }

    require_once '../config.php';
    require_once '../vendor/autoload.php';

    // Initialize database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // Check if it's a chunked upload
    if (isset($_POST['chunkNumber']) && isset($_POST['totalChunks'])) {
        handleChunkedUpload();
    } else {
        handleRegularUpload();
    }

} catch (Exception $e) {
    $output = ob_get_clean();
    error_log("Error in upload process: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => $output . "\n" . $e->getTraceAsString()
    ]);
}

function handleChunkedUpload() {
    $chunkNumber = intval($_POST['chunkNumber']);
    $totalChunks = intval($_POST['totalChunks']);
    $fileName = $_FILES['file']['name'];
    $tmpName = $_FILES['file']['tmp_name'];

    $uploadDir = '../uploads/'; // Make sure this directory exists and is writable
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $chunkName = "{$fileName}.part{$chunkNumber}";

    if (!move_uploaded_file($tmpName, $uploadDir . $chunkName)) {
        throw new Exception('Failed to save chunk.');
    }

    if ($chunkNumber == $totalChunks) {
        // All chunks received, combine them
        $finalFilePath = $uploadDir . $fileName;
        $finalFile = fopen($finalFilePath, 'wb');
        
        for ($i = 1; $i <= $totalChunks; $i++) {
            $chunkFile = $uploadDir . "{$fileName}.part{$i}";
            $chunk = file_get_contents($chunkFile);
            fwrite($finalFile, $chunk);
            unlink($chunkFile); // Delete the chunk file
        }
        fclose($finalFile);

        // Now upload the combined file to Dropbox
        $fileSize = filesize($finalFilePath);
        uploadToDropbox($finalFilePath, $fileName, $fileSize);

        // Clean up the temporary file
        unlink($finalFilePath);

        echo json_encode([
            'success' => true,
            'message' => 'File upload complete'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => "Chunk $chunkNumber of $totalChunks uploaded successfully"
        ]);
    }
}

function handleRegularUpload() {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Invalid file upload.');
    }

    $file = $_FILES['file'];
    $fileSize = $file['size'];
    $fileName = $file['name'];
    $fileTmpPath = $file['tmp_name'];

    error_log("File upload initiated. File size: $fileSize bytes. File name: $fileName");

    // Check file size (1GB limit)
    if ($fileSize > 1 * 1024 * 1024 * 1024) {
        throw new Exception('File size exceeds the 1GB limit.');
    }

    uploadToDropbox($fileTmpPath, $fileName, $fileSize);
}

function uploadToDropbox($filePath, $fileName, $fileSize) {
    global $pdo;

    // Get Dropbox account
    $account = getDropboxAccountWithSpace($fileSize);
    if (!$account) {
        throw new Exception("No Dropbox account has enough space for this file. Required: $fileSize bytes.");
    }

    $accessToken = $account['access_token'];
    error_log("Dropbox account found. Account ID: {$account['id']}. Access token: $accessToken");

    // Upload file to Dropbox
    $dropbox = new DropboxClient($accessToken);
    
    $stream = fopen($filePath, 'r');
    if ($stream === false) {
        throw new Exception('Failed to open file for reading.');
    }

    try {
        $uploadResult = $dropbox->upload("/$fileName", $stream);
        fclose($stream);
    } catch (\Exception $e) {
        fclose($stream);
        throw new Exception('Dropbox upload failed: ' . $e->getMessage());
    }
    
    error_log("File uploaded successfully to Dropbox. Upload result: " . json_encode($uploadResult));

    // Save file metadata to database
    try {
        $fileId = saveFileMetadata($fileName, $fileSize, $account['id']);
        if (!$fileId) {
            throw new Exception('Error saving file metadata.');
        }

        // Generate unique code and save
        $uniqueCode = generateUniqueCode();
        error_log("Generated unique code: " . $uniqueCode);
        saveFileCode($fileId, $uniqueCode);
    } catch (\PDOException $e) {
        throw new Exception('Database operation failed: ' . $e->getMessage());
    }

    // Generate download link
    $domain = $_SERVER['HTTP_HOST'];
    $downloadLink = "http://$domain/download.php?code=$uniqueCode";
    if (!filter_var($downloadLink, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid download link generated.');
    }

    $_SESSION['download_link'] = $downloadLink;
    $_SESSION['success_message'] = "File uploaded successfully!";

    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully!',
        'download_link' => $downloadLink
    ]);
}

function getDropboxAccountWithSpace($requiredSpace) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM dropbox_accounts ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function saveFileMetadata($fileName, $fileSize, $accountId) {
    global $pdo;
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("INSERT INTO files (user_id, account_id, file_name, file_size) VALUES (:user_id, :account_id, :file_name, :file_size)");
    $stmt->execute([
        'user_id' => $userId,
        'account_id' => $accountId,
        'file_name' => $fileName,
        'file_size' => $fileSize
    ]);
    return $pdo->lastInsertId();
}

function generateUniqueCode() {
    return substr(md5(uniqid(mt_rand(), true)), 0, 10);
}

function saveFileCode($fileId, $code) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE files SET code = :code WHERE id = :file_id");
    $stmt->execute([
        'code' => $code,
        'file_id' => $fileId
    ]);
}
?>