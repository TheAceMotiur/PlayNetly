<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../config.php';
$domain = $_SERVER['HTTP_HOST'];
$userId = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM files WHERE user_id = '$userId'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Files</title>
    <link href="../css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../header.php'; ?>
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-4">Your Files</h1>
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
                <?php while ($file = $result->fetch_assoc()): ?>
                <tr>
                    <td class="py-2 px-4 border-b"><?php echo $file['file_name']; ?></td>
                    <td class="py-2 px-4 border-b"><?php echo $file['file_size']; ?> bytes</td>
                    <td class="py-2 px-4 border-b"><?php echo $file['download_count']; ?></td>
                    <td class="py-2 px-4 border-b">
                        <a href="<?php echo $domain;?>/download.php?file_id=<?php echo $file['id']; ?>" class="text-blue-500">Download</a>
                        <a href="delete_file.php?id=<?php echo $file['id']; ?>" class="text-red-500">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php include '../footer.php'; ?>
</body>
</html>