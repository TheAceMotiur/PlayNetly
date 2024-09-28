<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../../config.php';

// Fetch all Dropbox accounts
$result = $conn->query("SELECT * FROM dropbox_accounts");

if (!$result) {
    die("Database query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Dropbox Accounts</title>
    <link href="../../css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <header class="bg-blue-600 text-white p-4">
            <h1 class="text-2xl">Manage Dropbox Accounts</h1>
        </header>
        <div class="flex flex-1">
            <aside class="w-64 bg-gray-800 text-white p-4">
                <nav>
                    <ul>
                        <li class="mb-2"><a href="../dashboard.php" class="block p-2 hover:bg-gray-700">Dashboard</a></li>
                        <li class="mb-2"><a href="../users" class="block p-2 hover:bg-gray-700">Users</a></li>
                        <li class="mb-2"><a href="index.php" class="block p-2 hover:bg-gray-700">Accounts</a></li>
                        <li class="mb-2"><a href="../logout.php" class="block p-2 hover:bg-gray-700">Logout</a></li>
                    </ul>
                </nav>
            </aside>
            <main class="flex-1 p-4">
                <h2 class="text-xl mb-4">Dropbox Accounts</h2>
                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-500 text-white p-2 rounded mb-4">
                        Refresh token generated successfully!
                    </div>
                <?php endif; ?>
                <a href="add_account.php" class="bg-blue-500 text-white p-2 rounded">Add Dropbox Account</a>
                <table class="min-w-full bg-white mt-4">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">ID</th>
                            <th class="py-2 px-4 border-b">App Key</th>
                            <th class="py-2 px-4 border-b">App Secret</th>
                            <th class="py-2 px-4 border-b">Access Token</th>
                            <th class="py-2 px-4 border-b">Refresh Token</th>
                            <th class="py-2 px-4 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="py-2 px-4 border-b"><?php echo $row['id']; ?></td>
                            <td class="py-2 px-4 border-b"><?php echo $row['app_key']; ?></td>
                            <td class="py-2 px-4 border-b"><?php echo $row['app_secret']; ?></td>
                            <td class="py-2 px-4 border-b"><?php echo $row['access_token']; ?></td>
                            <td class="py-2 px-4 border-b"><?php echo $row['refresh_token']; ?></td>
                            <td class="py-2 px-4 border-b">
                                <a href="edit_account.php?id=<?php echo $row['id']; ?>" class="text-blue-500">Edit</a>
                                <a href="delete_account.php?id=<?php echo $row['id']; ?>" class="text-red-500">Delete</a>
                                <a href="authorize.php?id=<?php echo $row['id']; ?>" class="text-green-500">Generate Refresh Token</a>
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