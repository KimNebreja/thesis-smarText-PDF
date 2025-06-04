<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmarTextPDF - Upload</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="logo">
                <img src="../assets/SmarText PDF_sidebar-logo.svg" alt="SmarTextPDF Logo" class="sidebar-logo">
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li class="active"><a href="upload.php" aria-current="page">Upload PDF</a></li>
                <li><a href="comparison.php">Compare PDFs</a></li>
                <li><a href="#" onclick="logout()" class="logout-link">Logout</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-bar">
                <div class="header-left">
                    <h1>Upload PDF</h1>
                </div>
                <div class="user-info">
                    <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </header>

            <div class="upload-container">
                <div id="notify" class="alert" style="text-align: center;" hidden></div>

                <div class="upload-card">
                    <div class="upload-header">
                        <img src="../assets/SmarText PDF_main-logo.svg" alt="SmarTextPDF Logo" class="main-logo">
                    </div>

                    <form method="POST" action="" enctype="multipart/form-data" class="upload-form" id="uploadForm">
                        <div class="upload-area" id="dropZone">
                            <input type="file" id="pdf_file" name="pdf_file" accept=".pdf"
                                class="file-input" onchange="handleFileSelect(event)">
                            <p class="upload-text">Click to Upload or Drag and drop your PDF here</p>
                            <div class="upload-icon">
                                <img src="../assets/SmarText PDF_folder-icon.svg" alt="Upload Icon" class="folder-icon">
                            </div>
                            <div class="upload-btn-container">
                                <button type="button" class="btn-secondary upload-btn" onclick="document.getElementById('pdf_file').click()">
                                    <img src="../assets/SmarText PDF_upload-btn.svg" alt="Upload Button" class="upload-btn-icon">
                                </button>
                            </div>
                            <p class="file-info" id="fileInfo"></p>
                        </div>

                        <button type="submit" class="btn-primary btn-block" id="uploadBtn" disabled>
                            Upload and Process
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../scripts/auth.js"></script>
    <script src="../assets/showAlert.js"></script>
    <script>
        // File upload handling
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('pdf_file');
        const fileInfo = document.getElementById('fileInfo');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadForm = document.getElementById('uploadForm');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop zone when dragging over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        dropZone.addEventListener('drop', handleDrop, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            dropZone.classList.add('highlight');
        }

        function unhighlight(e) {
            dropZone.classList.remove('highlight');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            // Set the files to the input element
            fileInput.files = files;
            // Trigger change event so 'required' is satisfied
            const event = new Event('change', {
                bubbles: true
            });
            fileInput.dispatchEvent(event);
            handleFiles(files);
        }

        // This function handles the file selection
        function handleFileSelect(e) {
            const files = e.target.files; // Get the files from the input
            handleFiles(files); // Handle the selected files
        }

        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0];
                if (file.type === 'application/pdf') {
                    fileInfo.textContent = `Selected file: ${file.name} (${formatFileSize(file.size)})`;
                    uploadBtn.disabled = false;
                } else {
                    fileInfo.textContent = 'Please select a PDF file';
                    uploadBtn.disabled = true;
                }
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        async function processProofReadingByGPT(filename) {
            const url = `../api/submit_proofreading.php?dbFilename=${encodeURIComponent(filename)}`;
            try {
                const response = await fetch(url, {
                    method: 'GET',
                })
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Error processing proofreading:', error);
                return {
                    error: true,
                    message: error.message
                };
            }
        }

        async function updateProcessedFiles(upload_id, pdf, json, time, improvements) {
            const url = `../api/submit_processed_files.php`;

            const data = {
                id: upload_id,
                pdf: pdf,
                json: json,
                time: time,
                improvements: improvements
            };

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const responseData = await response.json();
                return responseData;

            } catch (error) {
                console.error('Error processing proofreading:', error);
                return {
                    error: true,
                    message: error.message
                };
            }
        }


        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const file = fileInput.files[0];
            const formData = new FormData();

            if (!file) {
                alert('Please select a file to upload');
                return;
            }

            if (file.type !== 'application/pdf') {
                alert('Only PDF files are allowed');
                return;
            }

            uploadBtn.innerHTML = 'Proofreading the File. Please wait...';

            formData.append('file', file);

            try {
                const response = await fetch('../api/submit_upload_files.php', {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();

                const data = JSON.parse(responseText);

                if (data.success) {
                    const proofread = await processProofReadingByGPT(data.generatedfilename);

                    if (proofread?.message === 'success') {

                        let upload_id = data.upload_id
                        let elapsed_time = proofread?.info.elapsed_time_seconds
                        let generated_pdf = proofread?.info.final_pdf_filename
                        let generated_json = proofread?.info.json_filename
                        let errors = proofread?.info.total_improvements

                        let updateProcessFile = await updateProcessedFiles(
                            upload_id,
                            generated_pdf,
                            generated_json,
                            elapsed_time,
                            errors
                        )

                        if (updateProcessFile.message == 'success') {
                            let secondsLeft = 5;
                            showAlert('success', `File uploaded successfully! <br> Redirecting to Dashboard in ${secondsLeft}...`);

                            uploadBtn.innerHTML = 'Upload and Process';
                            document.getElementById('uploadBtn').disabled = true;
                            document.getElementById('pdf_file').disabled = false;
                            document.getElementById('pdf_file').value = '';
                            fileInfo.textContent = '';

                            const countdownInterval = setInterval(() => {
                                secondsLeft--;
                                showAlert('success', `File uploaded successfully! <br> Redirecting to Dashboard in ${secondsLeft}...`);
                            }, 1000);

                            setTimeout(() => {
                                clearInterval(countdownInterval);
                                // window.location.href = './dashboard.php';
                            }, 5000);
                        } else {
                            showAlert('error', 'Proofreading failed or unexpected response.');
                        }

                    } else {
                        showAlert('error', 'Proofreading failed or unexpected response.');
                    }
                } else {
                    showAlert('error', data.message);
                }
            } catch (error) {
                console.error('Upload error:', error);
                showAlert('error', 'An error occurred while uploading the file!');
            }
        });

        // Logout function
        function logout() {
            fetch('../api/logout.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '../index.php';
                    }
                })
                .catch(error => console.error('Error logging out:', error));
        }
    </script>
</body>

</html>