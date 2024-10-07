<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../../config.php';

$result = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="../../css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <header class="bg-blue-600 text-white p-4">
            <h1 class="text-2xl">Manage Users</h1>
        </header>
        <div class="flex flex-1">
            <aside class="w-64 bg-gray-800 text-white p-4">
                <nav>
                    <ul>
                        <li class="mb-2"><a href="../dashboard.php" class="block p-2 hover:bg-gray-700">Dashboard</a></li>
                        <li class="mb-2"><a href="index.php" class="block p-2 hover:bg-gray-700">Users</a></li>
                        <li class="mb-2"><a href="../accounts" class="block p-2 hover:bg-gray-700">Accounts</a></li>
                        <li class="mb-2"><a href="../logout.php" class="block p-2 hover:bg-gray-700">Logout</a></li>
                    </ul>
                </nav>
            </aside>
            <main class="flex-1 p-4">
                <h2 class="text-xl mb-4">User List</h2>
                <table class="min-w-full bg-white mt-4">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">ID</th>
                            <th class="py-2 px-4 border-b">Name</th>
                            <th class="py-2 px-4 border-b">Email</th>
                            <th class="py-2 px-4 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="py-2 px-4 border-b"><?php echo $row['id']; ?></td>
                            <td class="py-2 px-4 border-b"><?php echo $row['user_name']; ?></td>
                            <td class="py-2 px-4 border-b"><?php echo $row['user_email']; ?></td>
                            <td class="py-2 px-4 border-b">
                                <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="text-blue-500">Edit</a>
                                <a href="delete_user.php?id=<?php echo $row['id']; ?>" class="text-red-500">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </main>
        </div>
        <footer class="bg-gray-800 text-white p-4 text-center">
            &copy; 2024 OneNetly, All rights reserved.
        </footer>
    </div>
</body>
</html>