<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../../config.php';

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM users WHERE id = $id");
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_name = $_POST['user_name'];
    $user_email = $_POST['user_email'];
    $conn->query("UPDATE users SET user_name = '$user_name', user_email = '$user_email' WHERE id = $id");
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="../../css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <header class="bg-blue-600 text-white p-4">
            <h1 class="text-2xl">Edit User</h1>
        </header>
        <div class="flex flex-1">
            <aside class="w-64 bg-gray-800 text-white p-4">
                <nav>
                    <ul>
                        <li class="mb-2"><a href="../dashboard.php" class="block p-2 hover:bg-gray-700">Dashboard</a></li>
                        <li class="mb-2"><a href="index.php" class="block p-2 hover:bg-gray-700">Users</a></li>
                        <li class="mb-2"><a href="../settings.php" class="block p-2 hover:bg-gray-700">Settings</a></li>
                        <li class="mb-2"><a href="../logout.php" class="block p-2 hover:bg-gray-700">Logout</a></li>
                    </ul>
                </nav>
            </aside>
            <main class="flex-1 p-4">
                <h2 class="text-xl mb-4">Edit User</h2>
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="user_name" class="block text-gray-700">Name</label>
                        <input type="text" id="user_name" name="user_name" class="w-full p-2 border rounded" value="<?php echo $user['user_name']; ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="user_email" class="block text-gray-700">Email</label>
                        <input type="email" id="user_email" name="user_email" class="w-full p-2 border rounded" value="<?php echo $user['user_email']; ?>" required>
                    </div>
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded">Update User</button>
                </form>
            </main>
        </div>
        <footer class="bg-gray-800 text-white p-4 text-center">
            &copy; 2024 OneNetly, All rights reserved.
        </footer>
    </div>
</body>
</html>