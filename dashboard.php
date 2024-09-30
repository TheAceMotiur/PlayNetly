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
    <title>User Dashboard - OneNetly</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">OneNetly Dashboard</h1>
            <nav>
                <a href="logout.php" class="text-white hover:text-blue-200">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </nav>
        </div>
    </header>

    <main class="flex-grow container mx-auto mt-8 p-4">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Welcome, <?php echo htmlspecialchars($userName); ?>!</h2>
            <p class="text-gray-600">Email: <?php echo htmlspecialchars($userEmail); ?></p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-4">Your Files</h2>
            <?php if (empty($files)): ?>
                <p class="text-gray-600">You haven't uploaded any files yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="py-2 px-4 text-left">File Name</th>
                                <th class="py-2 px-4 text-left">File Size</th>
                                <th class="py-2 px-4 text-left">Download Count</th>
                                <th class="py-2 px-4 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-2 px-4"><?php echo htmlspecialchars($file['file_name']); ?></td>
                                <td class="py-2 px-4"><?php echo formatSizeUnits($file['file_size']); ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($file['download_count']); ?></td>
                                <td class="py-2 px-4">
                                    <a href="download.php?code=<?php echo urlencode($file['code']); ?>" class="text-blue-500 hover:text-blue-700 mr-2">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <a href="functions/delete_file.php?id=<?php echo urlencode($file['id']); ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this file?');">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-gray-800 text-white p-4 mt-8">
        <div class="container mx-auto text-center">
            <p>&copy; 2024 OneNetly. All rights reserved.</p>
        </div>
    </footer>
    
</body>
</html>