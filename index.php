<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in (adjust this according to your authentication system)
if (!isset($_SESSION['user_id'])) {
    // Do not redirect to login page here, we will handle it in the HTML code
}

// Initialize variables
$downloadLink = isset($_SESSION['download_link']) ? $_SESSION['download_link'] : '';
$showDownloadSection = !empty($downloadLink);
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
    <main class="container mx-auto mt-6 p-4">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
            <h1 class="text-2xl font-bold mb-6">Upload and Share Files</h1>

            <!-- File upload form -->
            <form id="uploadForm" enctype="multipart/form-data" class="mb-8">
                <div class="mb-4">
                    <label for="fileInput" class="block text-gray-700 text-sm font-bold mb-2">Select File:</label>
                    <input type="file" id="fileInput" name="file" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button type="button" onclick="uploadFile()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Upload File
                    </button>
                <?php else: ?>
                    <a href="login.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Login to upload
                    </a>
                <?php endif; ?>
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
                        Copy
                    </button>
                    <button onclick="viewDownloadLink()" class="bg-green-500 text-white px-4 py-2 rounded-r-lg hover:bg-green-600 transition duration-300 flex items-center" aria-label="View download link">
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