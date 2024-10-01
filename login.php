<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FilesWith</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-600 text-white p-4">
        <div class="container mx-auto">
            <h1 class="text-2xl font-bold">FilesWith</h1>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Login to Your Account</h2>
            
            <div class="mb-6 text-center">
                <p class="text-gray-600 mb-4">Use your OneNetly account to log in:</p>
                <a href="https://onenetly.com/api/oauth?app_id=8746867909080" class="inline-block">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                        <i class="fas fa-sign-in-alt mr-2"></i> Log in with OneNetly
                    </button>
                </a>
            </div>

            <div class="mt-8 border-t pt-6 text-center">
                <p class="text-gray-600">Don't have a OneNetly account?</p>
                <a href="https://onenetly.com/signup" "_blank" class="font-bold text-blue-500 hover:text-blue-800">
                    Sign up for OneNetly
                </a>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white p-4 mt-8">
        <div class="container mx-auto text-center">
            <p>&copy; 2024 FilesWith. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>