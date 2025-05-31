<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartTextPDF - Compare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/main.css">
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
</head>

<body>
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <iframe id="pdfIframe" width="100%" height="500px" style="border: none;"></iframe>
        </div>
    </div>

    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="logo">
                <img src="../assets/SmarText PDF_sidebar-logo.svg" alt="SmarTextPDF Logo" class="sidebar-logo">
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="upload.php">Upload PDF</a></li>
                <li class="active"><a href="comparison.php" aria-current="page">Compare PDFs</a></li>
                <li><a href="#" onclick="logout()" class="logout-link">Logout</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-bar">
                <h1>Compare PDFs</h1>
                <div class="user-info">
                    <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                </div>
            </header>

            <div class="comparison-container" id="comparison_main_container" hidden>
                <div class="comparison-header">
                    <div class="comparison-title">
                        <h3>Document Comparison</h3>
                        <p class="file-name" id="filenamePDF"></p>
                    </div>
                    <div class="comparison-actions">
                        <button class="btn-primary" id="acceptAllBtn" disabled>
                            <span class="button-text">Accept All Changes</span>
                        </button>
                        <button class="btn-primary" id="previewBtn" onclick="displayPDF()">
                            <span class="button-text">Preview PDF</span>
                        </button>
                        <button class="btn-primary" id="downloadBtn">
                            <span class="button-text">Download PDF</span>
                        </button>
                    </div>
                </div>

                <div class="comparison-content">
                    <div class="comparison-panel original">
                        <h4>Original Text</h4>
                        <div class="text-content" id="originalText">
                        </div>
                    </div>

                    <div class="comparison-panel revised">
                        <h4>Revised Text</h4>
                        <div class="text-content" id="revisedText">
                        </div>
                    </div>
                </div>

                <div class="tts-controls">
                    <div class="tts-header">
                        <h4>Text-to-Speech</h4>
                        <div class="tts-actions">
                            <button class="btn-secondary" id="ttsPlayBtn" aria-label="Play text">
                                <span class="button-text">Play</span>
                            </button>
                            <button class="btn-secondary" id="ttsPauseBtn" disabled aria-label="Pause text">
                                <span class="button-text">Pause</span>
                            </button>
                            <button class="btn-secondary" id="ttsStopBtn" disabled aria-label="Stop text">
                                <span class="button-text">Stop</span>
                            </button>
                        </div>
                    </div>
                    <div class="tts-settings">
                        <div class="speed-control">
                            <label for="ttsSpeed">Speed:</label>
                            <input type="range" id="ttsSpeed" min="0.5" max="2" step="0.1" value="1" aria-label="Speech speed">
                            <span id="speedValue">1x</span>
                        </div>
                        <div class="voice-select">
                            <label for="ttsVoice">Voice:</label>
                            <select id="ttsVoice" aria-label="Select voice"></select>
                        </div>
                    </div>
                    <div class="tts-progress">
                        <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress" id="ttsProgress"></div>
                        </div>
                        <div class="time-display">
                            <span id="currentTime">0:00</span> / <span id="totalTime">0:00</span>
                        </div>
                    </div>
                </div>

                <div class="comparison-legend">
                    <div class="legend-item">
                        <span class="legend-color error"></span>
                        <span>Error</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color suggestion"></span>
                        <span>Suggestion</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color final"></span>
                        <span>Accepted</span>
                    </div>
                </div>
            </div>
            <div class="comparison-container" id="comparison_secondary_container" style="display: flex; justify-content: center; align-items: center; height: 50vh;" hidden>
                <div class="comparison-header" style="text-align: center;">
                    <div class="comparison-title" style="margin: 20px;">
                        <h3>Direct to Dashboard to Specify File for Comparison</h3>
                        <button class="btn btn-primary" style="display: flex; align-items: center; justify-content: center; padding: 10px 20px; cursor: pointer; border-radius: 4px; transition: background-color 0.3s; font-size: 16px;" onclick="window.location.href='./dashboard.php'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-house-door" viewBox="0 0 16 16" style="margin-right: 8px;">
                                <path d="M8 3.293l3.5 3.5V9h2v5H3V9h2V6.793L8 3.293zM7 8v3H5V8H4V5h1V3.5l4-4 4 4V5h1v2H9z" />
                            </svg>
                            Go to Dashboard
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        let processedFileInformation = null

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

        async function fetchFileInformation(id) {
            try {
                const response = await fetch(`../api/get_current_file_data.php?processed_id=${id}`)

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`)
                }

                const data = await response.json()
                if (data.message === 'success') {
                    console.log('Fetched File Data:', data.info)
                    processedFileInformation = data.info
                    displayComparison(data.info[0]?.proof_data_path)
                    document.getElementById('filenamePDF').innerHTML = processedFileInformation[0]?.processed_file_path
                } else {
                    console.error('Error from API:', data.message)
                }

            } catch (error) {
                console.error('Fetch error:', error)
            }
        }

        function displayPDF() {
            if (processedFileInformation) {
                console.log(processedFileInformation[0]?.processed_file_path)
                openModal(processedFileInformation[0]?.processed_file_path)
                document.getElementById('filenamePDF').innerHTML = processedFileInformation[0]?.processed_file_path
            }
        }



        function openModal(pdfPath) {
            document.getElementById("myModal").style.display = 'block';
            document.getElementById("pdfIframe").src = '../generated/' + pdfPath;
        }

        function closeModal() {
            document.getElementById("myModal").style.display = 'none';
            document.getElementById("pdfIframe").src = ''; // Clear the iframe source when closing
        }




        async function displayComparison(json_file) {
            try {
                const response = await fetch(`../json/${json_file}`)
                if (!response.ok) throw new Error('Network response was not ok')

                const data = await response.json()

                const originalTextField = document.getElementById('originalText')
                const revisedTextField = document.getElementById('revisedText')

                // clear the content
                originalTextField.innerHTML = ''
                revisedTextField.innerHTML = ''

                console.log(data.original_token)
                console.log(data.revised_text)

                // Loop through the original tokens and check if they exist in original_text
                const originalTokens = data.original_token.map((word, index) => {
                    const originalError = data.original_text.find(item => item.index === index);

                    // If there's an error in the original text, apply error styling
                    // if index of error find, create a span to add style (red backgroud) in able to identify what is the text has been revised.
                    if (originalError && originalError.type === 'error') {
                        return `<span class="error">${word}</span>`;
                    }

                    // Default case if no errors or suggestions
                    return word;
                });

                // Loop through the revised tokens and display them
                const revisedTokens = data.proofread_token.map((word, index) => {
                    const revisedWord = data.revised_text.find(item => item.index === index);

                    // If there are suggestions for the word in revised text, show suggestion
                    if (revisedWord && revisedWord.suggestions && revisedWord.suggestions.length > 0) {
                        const firstSuggestion = revisedWord.suggestions[0];

                        // If only one suggestion, apply it
                        if (revisedWord.suggestions.length === 1) {
                            return `<span class="suggestion-container"><span class="accepted">${firstSuggestion}</span></span>`;
                        }

                        // If multiple suggestions, show dropdown
                        const options = revisedWord.suggestions
                            .map(s => `<option value="${s}">${s}</option>`)
                            .join('');

                        return `
                    <span class="suggestion-container">
                        <span class="suggestion-word">${firstSuggestion}</span>
                        <select class="floating-select" style="display:none;">
                            ${options}
                        </select>
                    </span>
                `;
                    }

                    return word;
                });

                // Render HTML for original and revised text
                originalTextField.innerHTML = `<p>${originalTokens.join(' ')}</p>`;
                revisedTextField.innerHTML = `<p>${revisedTokens.join(' ')}</p>`;

                // Show dropdown on click
                document.querySelectorAll('.suggestion-word').forEach((wordElement) => {
                    wordElement.addEventListener('click', function(event) {
                        document.querySelectorAll('.floating-select').forEach(dropdown => {
                            dropdown.style.display = 'none';
                        });

                        const select = this.nextElementSibling;
                        select.style.display = select.style.display === 'block' ? 'none' : 'block';
                        event.stopPropagation();
                    });
                });

                // Update suggestion on selection
                document.querySelectorAll('.floating-select').forEach((dropdown) => {
                    dropdown.addEventListener('change', function() {
                        const selectedOption = this.value;
                        const suggestionWord = this.previousElementSibling;
                        suggestionWord.textContent = selectedOption;
                        this.style.display = 'none';
                        document.getElementById('acceptAllBtn').disabled = false
                    });
                });

                // Close dropdown if clicked outside
                document.addEventListener('click', function(event) {
                    document.querySelectorAll('.floating-select').forEach((dropdown) => {
                        if (!dropdown.contains(event.target) && !dropdown.previousElementSibling.contains(event.target)) {
                            dropdown.style.display = 'none';
                        }
                    });
                });

            } catch (error) {
                console.error('There was a problem with the fetch operation:', error);
            }
        }

        document.getElementById('acceptAllBtn').addEventListener('click', function() {
            this.disabled = true;
            document.getElementById('previewBtn').disabled = true
            document.getElementById('downloadBtn').disabled = true
            this.innerHTML = 'Saving changes...';
        });


        window.onload = async () => {
            const params = new URLSearchParams(window.location.search);
            const id = params.get('id');

            const mainContainer = document.getElementById('comparison_main_container');
            const secondaryContainer = document.getElementById('comparison_secondary_container');

            if (mainContainer && secondaryContainer) {
                if (id) {
                    // Show the main container and hide the secondary container
                    mainContainer.style.display = 'block';
                    secondaryContainer.style.display = 'none';
                    // await displayComparison(id);
                    fetchFileInformation(id)
                } else {
                    // Hide the main container and show the secondary container
                    mainContainer.style.display = 'none';
                    secondaryContainer.style.display = 'flex';
                }
            }
        };
    </script>
    <script src="../scripts/auth.js"></script>
    <script src="../scripts/comparison.js"></script>
    <script src="../scripts/tts.js"></script>
</body>

</html>

<style>
    .modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
    }

    /* Modal Content */
    .modal-content {
        border-radius: 5px;
        background-color: #fefefe;
        margin: 0.5% auto;
        /* 15% from the top and centered */
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        height: 95%;
        /* Could be more or less, depending on screen size */
    }

    .modal-content iframe {
        height: 100%;
        padding: 2rem;
    }

    /* The Close Button */
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
</style>