<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ViralMagical - Create for free, forever.</title>
    <meta name="description" content="ViralMagical is a 100% free, truly democratic AI creativity platform funded entirely through product placement. Create consistent characters and apps with natural language.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="background-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <header>
        <div class="logo">
            <span class="vm-logo">VM</span> ViralMagical
        </div>
        <nav>
            <a href="index.php" class="nav-link">Apps</a>
            <a href="#" class="nav-link">About</a>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h1>Create for free, forever.<br><span class="highlight">Powered by product sponsorship.</span></h1>
            <p class="subtitle">Democratic AI creativity. Cross-image consistency. Natural language apps.</p>
            
            <div class="creator-interface glass-panel">
                <div class="input-row">
                    <div class="input-group">
                        <textarea id="promptInput" placeholder="Describe your idea... e.g., 'A cyberpunk detective drinking coffee in a neon city'"></textarea>
                    </div>

                    <div class="image-upload-section">
                        <label class="upload-label">
                            <span class="icon">📷</span> Upload Reference Image (up to 8)
                            <input type="file" id="imageInput" accept="image/png,image/jpeg,image/jpg,image/webp,.heic,.heif" multiple hidden>
                        </label>
                        <div id="imagePreview" class="image-preview-grid hidden">
                            <!-- Thumbnails will be added here dynamically -->
                        </div>
                    </div>
                </div>
                
                <div class="sponsor-selection">
                    <label>Choose your product sponsor to fund this creation:</label>
                    <div class="sponsor-options">
                        <div class="sponsor-card selected" data-sponsor="can">
                            <div class="sponsor-icon">
                                <img src="https://viralmagical.s3.us-east-1.amazonaws.com/icons/la-croix.png" alt="La Croix">
                            </div>
                            <span>La Croix</span>
                        </div>
                        <div class="sponsor-card" data-sponsor="keycap">
                            <div class="sponsor-icon">
                                <img src="https://viralmagical.s3.us-east-1.amazonaws.com/icons/claude-key.png" alt="Keycap">
                            </div>
                            <span>Keycap</span>
                        </div>
                        <div class="sponsor-card" data-sponsor="hat">
                            <div class="sponsor-icon">
                                <img src="https://viralmagical.s3.us-east-1.amazonaws.com/icons/bfl-fal-hat.png" alt="Hat">
                            </div>
                            <span>Hat</span>
                        </div>
                    </div>
                </div>

                <button id="createBtn" class="cta-button">
                    <span class="btn-text">CREATE MAGIC</span>
                    <div class="loader hidden"></div>
                </button>
            </div>
        </section>

        <section class="gallery-section">
            <h2>Recent Creations</h2>
            <div id="galleryGrid" class="gallery-grid">
                <!-- Gallery items will be loaded here -->
                <div class="gallery-item glass-panel skeleton"></div>
                <div class="gallery-item glass-panel skeleton"></div>
                <div class="gallery-item glass-panel skeleton"></div>
            </div>
        </section>
    </main>

    <!-- Creation Progress Modal -->
    <div id="creationModal" class="modal hidden">
        <div class="modal-content glass-panel">
            <div class="modal-header">
                <h2>Creating Your Magic ✨</h2>
            </div>
            <div class="modal-body">
                <div class="progress-spinner"></div>
                <p id="modalStatus" class="modal-status">Processing your images...</p>
                <div id="modalResult" class="modal-result hidden"></div>
            </div>
            <button id="modalClose" class="modal-close-btn hidden">Close & View in Gallery</button>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 ViralMagical. 100% Free & Democratic.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/heic-to@1.3.0/dist/iife/heic-to.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
    <script src="script.js?v=<?php echo filemtime('script.js'); ?>"></script>
</body>
</html>
