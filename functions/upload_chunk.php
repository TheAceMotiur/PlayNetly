<?php
use Spatie\Dropbox\Client as DropboxClient;

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once '../config.php';
require_once '../vendor/autoload.php';

// Increase execution time for large file uploads
set_time_limit(600);

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Invalid file upload.');
    }

    $chunkNumber = intval($_POST['chunkNumber']);
    $totalChunks = intval($_POST['totalChunks']);
    $fileName = $_FILES['file']['name'];
    $tmpName = $_FILES['file']['tmp_name'];

    $uploadDir = '/uploads/'; // Make sure this directory exists and is writable
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

        // Get file size
        $fileSize = filesize($finalFilePath);

        // Get Dropbox account
        $account = getDropboxAccountWithSpace($fileSize);
        if (!$account) {
            throw new Exception("No Dropbox account has enough space for this file. Required: $fileSize bytes.");
        }

        $accessToken = $account['access_token'];

        // Upload file to Dropbox
        $dropbox = new DropboxClient($accessToken);
        $stream = fopen($finalFilePath, 'r');
        if ($stream === false) {
            throw new Exception('Failed to open file for reading.');
        }

        try {
            $uploadResult = $dropbox->upload("/$fileName", $stream);
            fclose($stream);
            unlink($finalFilePath); // Delete the temporary file
        } catch (\Exception $e) {
            fclose($stream);
            unlink($finalFilePath); // Delete the temporary file
            throw new Exception('Dropbox upload failed: ' . $e->getMessage());
        }

        // Save file metadata to database
        $fileId = saveFileMetadata($fileName, $fileSize, $account['id']);
        if (!$fileId) {
            throw new Exception('Error saving file metadata.');
        }

        // Generate unique code and save
        $uniqueCode = generateUniqueCode();
        saveFileCode($fileId, $uniqueCode);

        // Generate download link
        $domain = $_SERVER['HTTP_HOST'];
        $downloadLink = "http://$domain/download.php?code=$uniqueCode";
        if (!filter_var($downloadLink, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid download link generated.');
        }

        echo json_encode([
            'success' => true,
            'message' => 'File upload complete',
            'download_link' => $downloadLink
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => "Chunk $chunkNumber of $totalChunks uploaded successfully"
        ]);
    }

} catch (Exception $e) {
    error_log("Error in upload process: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
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