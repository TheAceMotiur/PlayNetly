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

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
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

    // Get Dropbox account
    $account = getDropboxAccountWithSpace($fileSize);
    if (!$account) {
        throw new Exception("No Dropbox account has enough space for this file. Required: $fileSize bytes.");
    }

    $accessToken = $account['access_token'];
    error_log("Dropbox account found. Account ID: {$account['id']}. Access token: $accessToken");

    // Upload file to Dropbox
    $dropbox = new DropboxClient($accessToken);
    $fileContent = file_get_contents($fileTmpPath);
    if ($fileContent === false) {
        throw new Exception('Failed to read file contents.');
    }

    try {
        $uploadResult = $dropbox->upload("/$fileName", $fileContent, 'add');
    } catch (\Exception $e) {
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

    $output = ob_get_clean();
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully!',
        'download_link' => $downloadLink,
        'debug' => $output
    ]);

} catch (Exception $e) {
    $output = ob_get_clean();
    error_log("Error in upload process: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => $output . "\n" . $e->getTraceAsString()
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