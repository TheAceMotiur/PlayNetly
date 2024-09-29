<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$downloadLink = '';
$showDownloadSection = false;

// Check if a file has been uploaded
if (isset($_SESSION['download_link'])) {
    $downloadLink = $_SESSION['download_link'];
    $showDownloadSection = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload and Share</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>

    <main class="container mx-auto mt-6 p-4">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
            <h1 class="text-2xl font-bold mb-6">Upload and Share Files</h1>

            <!-- File upload form -->
            <form id="uploadForm" enctype="multipart/form-data" class="mb-8">
                <div class="mb-4">
                    <label for="fileInput" class="block text-gray-700 text-sm font-bold mb-2">Select File:</label>
                    <input type="file" id="fileInput" name="file" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <button type="button" onclick="uploadFile()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Upload File
                </button>
            </form>

            <!-- Progress bar (hidden by default) -->
            <div id="progressContainer" class="hidden mb-4">
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
                <p id="progressText" class="text-sm text-gray-600 mt-1">0%</p>
            </div>

            <!-- Download section -->
            <div id="downloadSection" class="<?php echo $showDownloadSection ? '' : 'hidden'; ?> mt-4 bg-white p-4 rounded-lg shadow-md">
                <p class="mb-2 font-semibold text-gray-700">Download Link:</p>
                <div class="flex items-center">
                    <input type="text" id="downloadLink" value="<?php echo htmlspecialchars($downloadLink); ?>" readonly class="flex-grow p-2 border rounded-l-lg bg-gray-100 text-gray-800" />
                    <button onclick="copyToClipboard()" class="bg-blue-500 text-white px-4 py-2 hover:bg-blue-600 transition duration-300 flex items-center" aria-label="Copy download link">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Copy
                    </button>
                    <button onclick="viewDownloadLink()" class="bg-green-500 text-white px-4 py-2 rounded-r-lg hover:bg-green-600 transition duration-300 flex items-center" aria-label="View download link">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View
                    </button>
                </div>
            </div>
        </div>
    </main>

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
        xhr.open('POST', 'functions/upload.php', true);

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                updateProgress(percentComplete);
            }
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
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
            } else {
                throw new Error('Server responded with status ' + xhr.status);
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
        
            <?php include 'footer.php'; ?>
        </body>
        </html>