<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Fetch user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];

// Fetch user files
$result = $conn->query("SELECT * FROM files WHERE user_id = '$userId'");
$files = $result->fetch_all(MYSQLI_ASSOC);

// Function to convert bytes to megabytes
function formatSizeUnits($bytes) {
    return number_format($bytes / 1048576, 2) . ' MB';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-4">User Dashboard</h1>
        <div class="bg-white p-4 rounded shadow mb-4">
            <h2 class="text-2xl mb-2">Welcome, <?php echo htmlspecialchars($userName); ?>!</h2>
            <p>Your email: <?php echo htmlspecialchars($userEmail); ?></p>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <h2 class="text-2xl mb-4">Your Files</h2>
            <table class="min-w-full bg-white">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">File Name</th>
                        <th class="py-2 px-4 border-b">File Size</th>
                        <th class="py-2 px-4 border-b">Download Count</th>
                        <th class="py-2 px-4 border-b">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                    <tr>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($file['file_name']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo formatSizeUnits($file['file_size']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($file['download_count']); ?></td>
                        <td class="py-2 px-4 border-b">
                        <a href="http://localhost/download.php?code=<?php echo urlencode($file['code']); ?>" class="text-blue-500">Download</a>
                            <a href="functions/delete_file.php?id=<?php echo urlencode($file['id']); ?>" class="text-red-500">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>