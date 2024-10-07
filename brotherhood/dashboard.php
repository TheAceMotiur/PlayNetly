<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}
require_once '../config.php';

$result = $conn->query("SELECT COUNT(*) AS user_count FROM users");
$user_count = $result->fetch_assoc()['user_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="../css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <header class="bg-blue-600 text-white p-4">
            <h1 class="text-2xl">Admin Dashboard</h1>
        </header>
        <div class="flex flex-1">
            <aside class="w-64 bg-gray-800 text-white p-4">
                <nav>
                    <ul>
                        <li class="mb-2"><a href="dashboard.php" class="block p-2 hover:bg-gray-700">Dashboard</a></li>
                        <li class="mb-2"><a href="./users" class="block p-2 hover:bg-gray-700">Users</a></li>
                        <li class="mb-2"><a href="./accounts" class="block p-2 hover:bg-gray-700">Accounts</a></li>
                        <li class="mb-2"><a href="logout.php" class="block p-2 hover:bg-gray-700">Logout</a></li>
                    </ul>
                </nav>
            </aside>
            <main class="flex-1 p-4">
                <h2 class="text-xl mb-4">Welcome to the admin dashboard!</h2>
                <div class="bg-white p-4 rounded shadow">
                    <p>Here you can manage your application settings, users, and more.</p>
                    <p>Total Users: <?php echo $user_count; ?></p>
                </div>
            </main>
        </div>
        <footer class="bg-gray-800 text-white p-4 text-center">
            &copy; 2024 OneNetly, All rights reserved.
        </footer>
    </div>
</body>
</html>