<?php
use Spatie\Dropbox\Client as DropboxClient;

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in.');
    }

    require_once 'config.php';
    require_once '../vendor/autoload.php';

    // Initialize database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    handleUpload();

} catch (Exception $e) {
    error_log("Error in upload process: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleUpload() {
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

    $result = uploadToDropbox($fileTmpPath, $fileName, $fileSize);
    echo json_encode($result);
}

function uploadToDropbox($filePath, $fileName, $fileSize) {

    $downloadLink = "http://$domain/download.php?code=$uniqueCode";
    if (!filter_var($downloadLink, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid download link generated.');
    }

    $_SESSION['download_link'] = $downloadLink;

    return [
        'success' => true,
        'message' => 'File uploaded successfully!',
        'download_link' => $downloadLink
    ];

    global $pdo;

    // Get Dropbox account with available space
    $account = getDropboxAccountWithSpace($fileSize);
    if (!$account) {
        throw new Exception("No Dropbox account has enough space for this file. Required: $fileSize bytes.");
    }

    $accessToken = $account['access_token'];
    error_log("Dropbox account found. Account ID: {$account['id']}. Access token: $accessToken");

    // Initialize Dropbox client
    $dropbox = new DropboxClient($accessToken);

    // Upload file to Dropbox
    $stream = fopen($filePath, 'r');
    if ($stream === false) {
        throw new Exception('Failed to open file for reading.');
    }

    try {
        $uploadResult = $dropbox->upload("/$fileName", $stream, "overwrite");
    } catch (\Exception $e) {
        throw new Exception('Dropbox upload failed: ' . $e->getMessage());
    } finally {
        if (is_resource($stream)) {
            fclose($stream);
        }
    }
    
    error_log("File uploaded successfully to Dropbox. Upload result: " . json_encode($uploadResult));

    // Save file metadata to database
    $fileId = saveFileMetadata($fileName, $fileSize, $account['id']);
    if (!$fileId) {
        throw new Exception('Error saving file metadata.');
    }

    // Generate unique code and save
    $uniqueCode = generateUniqueCode();
    error_log("Generated unique code: " . $uniqueCode);
    saveFileCode($fileId, $uniqueCode);

    // Generate download link
    $domain = $_SERVER['HTTP_HOST'];
    $downloadLink = "http://$domain/download.php?code=$uniqueCode";
    if (!filter_var($downloadLink, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid download link generated.');
    }

    return [
        'success' => true,
        'message' => 'File uploaded successfully!',
        'download_link' => $downloadLink
    ];
}

function getDropboxAccountWithSpace($requiredSpace) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM dropbox_accounts ORDER BY id ASC");
    $stmt->execute();
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($accounts as $account) {
        try {
            $dropbox = new DropboxClient($account['access_token']);
            $spaceUsage = $dropbox->rpcEndpointRequest('users/get_space_usage');
            
            // The space usage is returned in bytes
            $availableSpace = $spaceUsage['allocation']['allocated'] - $spaceUsage['used'];
            
            if ($availableSpace >= $requiredSpace) {
                return $account;
            }
        } catch (\Exception $e) {
            error_log("Error checking space for account {$account['id']}: " . $e->getMessage());
            continue;
        }
    }

    return null;
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