<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
    <link href="css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-4">Upload File</h1>
        <?php if (isset($_SESSION['download_link'])): ?>
            <div class="mt-4">
                <div class="bg-gray-200 p-4 rounded">
                    <p class="mb-2">Download Link:</p>
                    <div class="flex items-center">
                        <input type="text" id="downloadLink" value="<?php echo htmlspecialchars($_SESSION['download_link']); ?>" readonly class="flex-grow p-2 border rounded">
                        <button onclick="copyToClipboard()" class="bg-blue-500 text-white px-4 py-2 rounded ml-2 copy-button">Copy</button>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['download_link']); ?>
        <?php endif; ?>
        <form action="functions/upload_handler.php" method="post" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Upload</button>
        </form>
    </div>
    <?php include 'footer.php'; ?>
    <script>
        function copyToClipboard() {
            var copyText = document.getElementById("downloadLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999); // For mobile devices
            document.execCommand("copy");
            alert("Copied the link: " + copyText.value);
        }
    </script>
</body>
</html>