<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OneNetly - Secure File Upload</title>
    <link href="css/output.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    
    <main class="container mx-auto p-8">
        <h1 class="text-4xl font-bold mb-8 text-center text-blue-600">Secure File Upload</h1>
        
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
            <form id="uploadForm" action="functions/upload.php" method="post" enctype="multipart/form-data" class="space-y-6">
                <div class="flex items-center justify-center w-full">
                    <label for="file-upload" class="flex flex-col items-center justify-center w-full h-64 border-2 border-blue-300 border-dashed rounded-lg cursor-pointer bg-blue-50 hover:bg-blue-100">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-10 h-10 mb-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <p class="mb-2 text-sm text-blue-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                            <p class="text-xs text-blue-500">Any file up to 100MB</p>
                        </div>
                        <input id="file-upload" type="file" name="file" class="hidden" required />
                    </label>
                </div>
                <div>
                    <button type="submit" class="w-full bg-blue-500 text-white px-4 py-3 rounded-lg font-semibold hover:bg-blue-600 transition duration-300">Upload File</button>
                </div>
            </form>
            <div id="progressContainer" class="mt-4 hidden">
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
                <p id="progressText" class="text-center mt-2">0%</p>
            </div>
        </div>

        <?php if (isset($_SESSION['download_link'])): ?>
        <div class="mt-8 max-w-2xl mx-auto">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline"> Your file has been uploaded.</span>
            </div>
            <div class="mt-4 bg-white p-4 rounded-lg shadow-md">
                <p class="mb-2 font-semibold text-gray-700">Download Link:</p>
                <div class="flex items-center">
                    <input type="text" id="downloadLink" value="<?php echo htmlspecialchars($_SESSION['download_link']); ?>" readonly class="flex-grow p-2 border rounded-l-lg bg-gray-100 text-gray-800" />
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
        <?php unset($_SESSION['download_link']); ?>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <script>
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

    function uploadLargeFile(file) {
  const chunkSize = 5 * 1024 * 1024; // 5MB chunks
  const totalChunks = Math.ceil(file.size / chunkSize);
  let currentChunk = 0;

  function uploadNextChunk() {
    const start = currentChunk * chunkSize;
    const end = Math.min(file.size, start + chunkSize);
    const chunk = file.slice(start, end);

    const formData = new FormData();
    formData.append('file', chunk, file.name);
    formData.append('chunkNumber', currentChunk + 1);
    formData.append('totalChunks', totalChunks);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'functions/upload.php', true);
    xhr.onload = function() {
      if (xhr.status === 200) {
        const response = JSON.parse(xhr.responseText);
        if (response.success) {
          currentChunk++;
          if (currentChunk < totalChunks) {
            uploadNextChunk();
          } else {
            alert('File uploaded successfully!');
          }
        } else {
          alert('Upload failed: ' + response.message);
        }
      } else {
        alert('An error occurred during upload.');
      }
    };
    xhr.onerror = function() {
      alert('An error occurred during upload.');
    };
    xhr.send(formData);
  }

  uploadNextChunk();
}

    function viewDownloadLink() {
        var downloadLink = document.getElementById("downloadLink").value;
        window.open(downloadLink, '_blank');
    }

    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var xhr = new XMLHttpRequest();
        
        xhr.open('POST', this.action, true);
        
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                var percentComplete = (e.loaded / e.total) * 100;
                updateProgress(percentComplete);
            }};
            
            xhr.onloadend = function() {
                        console.log('Response status:', xhr.status);
                        console.log('Response text:', xhr.responseText);
            
                        document.getElementById('progressContainer').classList.add('hidden');
            
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                console.log('Parsed response:', response);
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'File uploaded successfully.',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    showError(response.message || 'An error occurred during upload.');
                                    if (response.debug) {
                                        console.error('Debug information:', response.debug);
                                    }
                                }
                            } catch (e) {
                                console.error('Error parsing response:', e);
                                console.log('Raw response:', xhr.responseText);
                                showError('An unexpected error occurred while processing the server response. Check console for details.');
                            }
                        } else {
                            showError('An error occurred during upload. Server responded with status ' + xhr.status);
                        }
                    };
                    
                    document.getElementById('progressContainer').classList.remove('hidden');
                    xhr.send(formData);
                });
            
                function updateProgress(percent) {
                    var progressBar = document.getElementById('progressBar');
                    var progressText = document.getElementById('progressText');
                    percent = Math.min(100, Math.max(0, percent));
                    progressBar.style.width = percent + '%';
                    progressText.textContent = Math.round(percent) + '%';
                }
            
                function showError(message) {
                    console.error('Error:', message);
                    Swal.fire({
                        title: 'Error!',
                        text: message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            
                document.getElementById('file-upload').addEventListener('change', function(e) {
                    var fileName = e.target.files[0].name;
                    var fileLabel = document.querySelector('[for=file-upload] p:first-of-type');
                    fileLabel.textContent = 'Selected file: ' + fileName;
                });
                </script>
            </body>
            </html>