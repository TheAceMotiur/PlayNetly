<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../../config.php';

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM dropbox_accounts WHERE id = $id");
$account = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $app_key = $_POST['app_key'];
    $app_secret = $_POST['app_secret'];
    $access_token = $_POST['access_token'];
    $refresh_token = $_POST['refresh_token'];
    $conn->query("UPDATE dropbox_accounts SET app_key = '$app_key', app_secret = '$app_secret', access_token = '$access_token', refresh_token = '$refresh_token' WHERE id = $id");
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dropbox Account</title>
    <link href="../../css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <header class="bg-blue-600 text-white p-4">
            <h1 class="text-2xl">Edit Dropbox Account</h1>
        </header>
        <main class="flex-1 p-4">
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="app_key" class="block text-gray-700">App Key</label>
                    <input type="text" id="app_key" name="app_key" class="w-full p-2 border rounded" value="<?php echo $account['app_key']; ?>" required>
                </div>
                <div class="mb-4">
                    <label for="app_secret" class="block text-gray-700">App Secret</label>
                    <input type="text" id="app_secret" name="app_secret" class="w-full p-2 border rounded" value="<?php echo $account['app_secret']; ?>" required>
                </div>
                <div class="mb-4">
                    <label for="access_token" class="block text-gray-700">Access Token</label>
                    <input type="text" id="access_token" name="access_token" class="w-full p-2 border rounded" value="<?php echo $account['access_token']; ?>" required>
                </div>
                <div class="mb-4">
                    <label for="refresh_token" class="block text-gray-700">Refresh Token</label>
                    <input type="text" id="refresh_token" name="refresh_token" class="w-full p-2 border rounded" value="<?php echo $account['refresh_token']; ?>" required>
                </div>
                <button type="submit" class="bg-blue-500 text-white p-2 rounded">Update Account</button>
            </form>
        </main>
    </div>
</body>
</html>