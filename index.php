<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$downloadLink = isset($_SESSION['download_link']) ? $_SESSION['download_link'] : '';
$showDownloadSection = !empty($downloadLink);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OneNetly File Upload and Share</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">OneNetly</h1>
            <nav>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="text-white hover:text-blue-200">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-white hover:text-blue-200">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container mx-auto mt-8 p-4 flex-grow">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
            <h2 class="text-3xl font-bold mb-6 text-center text-gray-800">Upload and Share Files</h2>

            <!-- File upload form -->
            <form id="uploadForm" enctype="multipart/form-data" class="mb-8">
    <div class="mb-4">
        <label for="fileInput" class="block text-gray-700 text-sm font-bold mb-2">Select File:</label>
        <div class="relative border-2 border-gray-300 border-dashed rounded-lg p-6 hover:border-blue-500 transition duration-300 ease-in-out">
            <input type="file" id="fileInput" name="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="updateFileName()">
            <div class="text-center">
                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                <p id="fileNameDisplay" class="text-gray-500">Drag and drop your file here or click to browse</p>
            </div>
        </div>
    </div>
    <?php if (isset($_SESSION['user_id'])): ?>
        <button type="button" onclick="uploadFile()" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
            <i class="fas fa-upload mr-2"></i> Upload File
        </button>
    <?php else: ?>
        <a href="login.php" class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline transition duration-300 ease-in-out inline-block text-center">
            <i class="fas fa-sign-in-alt mr-2"></i> Login to Upload
        </a>
    <?php endif; ?>
</form>

            <!-- Progress bar (hidden by default) -->
            <div id="progressContainer" class="hidden mb-4">
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div id="progressBar" class="bg-blue-600 h-4 rounded-full transition-all duration-300 ease-in-out" style="width: 0%"></div>
                </div>
                <p id="progressText" class="text-sm text-gray-600 mt-2 text-center">0%</p>
            </div>

            <!-- Download section -->
            <div id="downloadSection" class="<?php echo $showDownloadSection ? '' : 'hidden'; ?> mt-8 bg-gray-100 p-6 rounded-lg">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">Download Link</h3>
                <div class="flex items-center">
                    <input type="text" id="downloadLink" value="<?php echo htmlspecialchars($downloadLink); ?>" readonly class="flex-grow p-3 border rounded-l-lg bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button onclick="copyToClipboard()" class="bg-blue-500 text-white px-4 py-3 hover:bg-blue-600 transition duration-300 ease-in-out" aria-label="Copy download link">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button onclick="viewDownloadLink()" class="bg-green-500 text-white px-4 py-3 rounded-r-lg hover:bg-green-600 transition duration-300 ease-in-out" aria-label="View download link">
                        <i class="fas fa-external-link-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white p-4 mt-8">
        <div class="container mx-auto text-center">
            <p>&copy; 2024 OneNetly. All rights reserved.</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function uploadFile() {
        const fileInput = document.getElementById('fileInput');
        const file = fileInput.files[0];
        if (!file) {
            Swal.fire('Error', 'Please select a file first.', 'error');
            return;
        }

            const formData = new FormData();
            formData.append('file', file);

            // Show progress bar
            document.getElementById('progressContainer').classList.remove('hidden');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload.php', true);

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    updateProgress(percentComplete);
                }
            };

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            Swal.fire('Success', response.message, 'success');
                            if (response.download_link) {
                                document.getElementById('downloadLink').value = response.download_link;
                                document.getElementById('downloadSection').classList.remove('hidden');
                            }
                        } else {
                            throw new Error(response.message || 'Upload failed');
                        }
                    } catch (error) {
                        console.error('Error parsing response:', xhr.responseText);
                        Swal.fire('Error', 'An error occurred during upload: ' + error.message, 'error');
                    }
                } else {
                    Swal.fire('Error', 'Server responded with status ' + xhr.status, 'error');
                }
            };

            xhr.onerror = function() {
                console.error('Error:', xhr.statusText);
                Swal.fire('Error', 'An error occurred during upload: ' + xhr.statusText, 'error');
            };

            xhr.send(formData);
        }

        function updateProgress(percent) {
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            progressBar.style.width = percent + '%';
            progressText.textContent = Math.round(percent) + '%';
        }

        function copyToClipboard() {
            var copyText = document.getElementById("downloadLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            
            Swal.fire({
                title: 'Copied!',
                text: 'Download link has been copied to clipboard.',
                icon: 'success',
                confirmButtonText: 'OK'
            });
        }

        function viewDownloadLink() {
            var downloadLink = document.getElementById("downloadLink").value;
            window.open(downloadLink, '_blank');
        }
    </script>
</body>
</html>