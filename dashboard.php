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

// Pagination
$itemsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Fetch total number of files
$totalResult = $conn->query("SELECT COUNT(*) as total FROM files WHERE user_id = '$userId'");
$totalFiles = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalFiles / $itemsPerPage);

// Fetch user files with pagination
$result = $conn->query("SELECT * FROM files WHERE user_id = '$userId' ORDER BY upload_time DESC LIMIT $offset, $itemsPerPage");
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
    <title>User Dashboard - FilesWith</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="/"><h1 class="text-2xl font-bold">FilesWith</h1></a>
            <nav>
                <a href="index.php" class="text-white hover:text-blue-200 mr-4">
                    <i class="fas fa-home mr-2"></i>Home
                </a>
                <a href="logout.php" class="text-white hover:text-blue-200">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </nav>
        </div>
    </header>

<main class="flex-grow container mx-auto mt-8 p-4">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
            <h2 class="text-xl font-bold mb-4">Welcome, <?php echo htmlspecialchars($userName); ?>!</h2>
            <p class="text-gray-600 text-sm sm:text-base"><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($userEmail); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
            <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
            <a href="index.php" class="bg-blue-500 text-white px-3 py-2 sm:px-4 sm:py-2 rounded hover:bg-blue-600 transition duration-300 inline-block mb-2 text-sm sm:text-base">
                <i class="fas fa-upload mr-2"></i>Upload New File
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
        <h2 class="text-xl sm:text-2xl font-bold mb-4">Your Files</h2>
        <?php if (empty($files)): ?>
            <p class="text-gray-600">You haven't uploaded any files yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-2 sm:px-4 text-left text-xs sm:text-sm">File Name</th>
                            <th class="py-2 px-2 sm:px-4 text-left text-xs sm:text-sm">File Size</th>
                            <th class="py-2 px-2 sm:px-4 text-left text-xs sm:text-sm">Date</th>
                            <th class="py-2 px-2 sm:px-4 text-left text-xs sm:text-sm">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-2 px-2 sm:px-4 text-xs sm:text-sm"><?php echo htmlspecialchars($file['file_name']); ?></td>
                            <td class="py-2 px-2 sm:px-4 text-xs sm:text-sm"><?php echo formatSizeUnits($file['file_size']); ?></td>
                            <td class="py-2 px-2 sm:px-4 text-xs sm:text-sm"><?php echo date('M d, Y', strtotime($file['upload_time'])); ?></td>
                            <td class="py-2 px-2 sm:px-4 text-xs sm:text-sm">
                                <a href="download.php?code=<?php echo urlencode($file['code']); ?>" class="text-blue-500 hover:text-blue-700 mr-2" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                                
                                <a href="functions/delete_file.php?id=<?php echo urlencode($file['id']); ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this file?');" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="mt-6 flex justify-center">
                <?php if ($totalPages > 1): ?>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="relative inline-flex items-center px-2 sm:px-4 py-2 border border-gray-300 bg-white text-xs sm:text-sm font-medium <?php echo $i === $page ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

    <footer class="bg-gray-800 text-white p-4 mt-8">
        <div class="container mx-auto text-center">
            <p>&copy; 2024 FilesWith. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>