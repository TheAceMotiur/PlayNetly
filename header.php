<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Navbar</title>
    <link href="css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="/" class="text-white text-2xl font-bold">OneNetly</a>
            <div class="hidden md:flex space-x-4">
                <a href="#" class="text-white hover:bg-blue-700 px-3 py-2 rounded">Home</a>
                <a href="#" class="text-white hover:bg-blue-700 px-3 py-2 rounded">About</a>
                <a href="#" class="text-white hover:bg-blue-700 px-3 py-2 rounded">Services</a>
                <a href="#" class="text-white hover:bg-blue-700 px-3 py-2 rounded">Contact</a>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded">Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded">Login</a>
                <?php endif; ?>
            </div>
            <div class="md:hidden">
                <button id="menu-btn" class="text-white focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div id="menu" class="hidden md:hidden">
            <a href="#" class="block text-white hover:bg-blue-700 px-3 py-2 rounded">Home</a>
            <a href="#" class="block text-white hover:bg-blue-700 px-3 py-2 rounded">About</a>
            <a href="#" class="block text-white hover:bg-blue-700 px-3 py-2 rounded">Services</a>
            <a href="#" class="block text-white hover:bg-blue-700 px-3 py-2 rounded">Contact</a>
            <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="block text-white hover:bg-blue-700 px-3 py-2 rounded">Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="block text-white hover:bg-blue-700 px-3 py-2 rounded">Login</a>
            <?php endif; ?>
        </div>
    </nav>
</body>
</html>