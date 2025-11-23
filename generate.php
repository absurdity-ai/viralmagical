<?php
require_once 'app_config.php';
require_once 'sponsor_config.php';

$app_id = $_GET['app'] ?? null;
$current_app = $app_id ? ($image_apps[$app_id] ?? null) : null;

// If no app selected, maybe redirect to index or show a selector?
// For now, if no app, we can't really render the "multiple input panels based on generator design".
// But maybe we can have a default "Freeform" app?
if (!$current_app) {
    // Fallback or redirect
    // header("Location: index.php");
    // exit;
    // Or just show a message
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ViralMagical - Generate <?php echo $current_app ? $current_app['name'] : ''; ?></title>
    <meta name="description" content="Create amazing AI content with ViralMagical.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="/style-generator.css">
    <style>
        /* Overrides for Generate Mode */
        .generator-container {
            max-width: 800px; /* Slightly wider for preview */
            margin: 0 auto;
        }
        .preview-strip {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 10px 0;
            justify-content: center;
        }
        .preview-image {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .step-content {
            display: block !important; /* No steps here, just one view */
            animation: none;
        }
        .section-header {
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="background-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <header>
        <div class="logo">
            <a href="/" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:0.5rem;">
                <span class="vm-logo">VM</span> ViralMagical
            </a>
        </div>
        <nav>
            <a href="/" class="nav-link">Apps</a>
            <a href="/create-generator" class="nav-link">Forge New</a>
        </nav>
    </header>

    <main class="generator-main">
        <?php if ($current_app): ?>
            <section class="hero-compact">
                <h1><?php echo htmlspecialchars($current_app['name']); ?></h1>
                <p class="subtitle"><?php echo htmlspecialchars($current_app['description']); ?></p>
            </section>

            <div class="generator-container glass-panel">
                
                <div class="section-header">
                    <h2>Configure Your Generation</h2>
                    <p>Fill in the details below to create your magic.</p>
                </div>

                <form id="generateForm">
                    <input type="hidden" id="appId" value="<?php echo htmlspecialchars($current_app['id']); ?>">
                    
                    <div class="preview-config">
                        <div id="inputsContainer">
                            <!-- Dynamic Inputs Rendered Here -->
                            <?php foreach ($current_app['inputs'] as $input): ?>
                                <div class="form-group">
                                    <label>
                                        <?php echo htmlspecialchars($input['label']); ?> 
                                        <?php if (!empty($input['required'])): ?>
                                            <span style="color: #ff0055;">*</span>
                                        <?php endif; ?>
                                    </label>
                                    
                                    <?php 
                                    // Determine type based on role or explicit type
                                    // Existing apps don't have 'type' field in inputs, but we can infer or default to image?
                                    // Actually, existing apps in app_config.php don't specify type explicitly, but roles like 'character', 'background' imply image.
                                    // 'style' might be text?
                                    // Let's assume everything is an image unless specified otherwise, or we can check the role name?
                                    // Wait, create_generator.php allows specifying type.
                                    // We should update app_config.php to include types, or default to text if not image-like?
                                    // Actually, most inputs in current apps are images.
                                    // Let's assume image for now, or check if we can add type to app_config.
                                    
                                    $inputType = $input['type'] ?? 'image'; // Default to image for now as most are
                                    // But wait, 'style' in collage app is likely text?
                                    // Let's check if role is 'style' or 'prompt'
                                    if (in_array($input['role'], ['style', 'prompt', 'text'])) {
                                        $inputType = 'text';
                                    }
                                    ?>

                                    <?php if ($inputType === 'image'): ?>
                                        <div class="image-upload-section">
                                            <label class="upload-label">
                                                <span class="icon">📷</span> Upload <?php echo htmlspecialchars($input['label']); ?>
                                                <input type="file" class="app-input" data-role="<?php echo $input['role']; ?>" accept="image/png,image/jpeg,image/jpg,image/webp,.heic,.heif" <?php echo !empty($input['required']) ? 'required' : ''; ?> hidden>
                                            </label>
                                            <div id="preview-<?php echo $input['role']; ?>" class="image-preview-grid hidden"></div>
                                        </div>
                                    <?php else: ?>
                                        <textarea class="app-input" data-role="<?php echo $input['role']; ?>" placeholder="Enter text for <?php echo htmlspecialchars($input['label']); ?>" <?php echo !empty($input['required']) ? 'required' : ''; ?>></textarea>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Optional Global Prompt -->
                            <div class="form-group">
                                <label>Additional Instructions (Optional)</label>
                                <textarea id="globalPrompt" class="app-input" data-role="extra_prompt" placeholder="Add extra details, vibe, or style instructions..."></textarea>
                            </div>
                        </div>

                        <div class="sponsor-selection" style="margin-top: 2rem;">
                            <label>Powered by Sponsor (Free):</label>
                            <div class="sponsor-options">
                                <?php foreach ($sponsors as $key => $sponsor): ?>
                                    <div class="sponsor-card <?php echo $key === 'la_croix' ? 'selected' : ''; ?>" data-sponsor="<?php echo $key; ?>">
                                        <div class="sponsor-icon">
                                            <img src="<?php echo $sponsor['image']; ?>" alt="<?php echo $sponsor['name']; ?>">
                                        </div>
                                        <span><?php echo $sponsor['name']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" id="generateBtn" class="btn-primary cta-button" style="margin-top: 2rem; width: 100%;">
                            <span class="btn-text">✨ GENERATE</span>
                            <div class="loader hidden"></div>
                        </button>
                    </div>
                </form>

                <div id="resultsArea" class="hidden" style="margin-top: 2rem; text-align: center;">
                    <h3>Your Result</h3>
                    <div class="preview-strip" id="resultImageContainer">
                        <!-- Image goes here -->
                    </div>
                    <div class="nav-buttons" style="justify-content: center; margin-top: 1rem;">
                        <a href="#" id="shareBtn" class="btn-secondary">🔗 Share</a>
                        <a href="/" class="btn-secondary">🏠 Home</a>
                    </div>
                </div>

            </div>
        <?php else: ?>
            <!-- FREEFORM / REMIX MODE -->
            <section class="hero">
                <h1>Create Magic</h1>
                <p class="subtitle">Describe your idea or remix an existing style.</p>
                
                <div class="creator-interface glass-panel">
                    <form id="freeformForm">
                        <div class="input-row">
                            <div class="input-group" style="flex: 1;">
                                <textarea id="promptInput" class="app-input" data-role="prompt" placeholder="Describe your idea... e.g., 'A cyberpunk detective drinking coffee in a neon city'" style="height: 120px; width: 100%;"><?php echo htmlspecialchars($_GET['remix'] ?? ''); ?></textarea>
                            </div>
    
                            <div class="image-upload-section" style="flex: 1;">
                                <label class="upload-label">
                                    <span class="icon">📷</span> Upload Reference Images
                                    <input type="file" id="imageInput" accept="image/png,image/jpeg,image/jpg,image/webp,.heic,.heif" multiple hidden>
                                </label>
                                <div id="imagePreview" class="image-preview-grid hidden"></div>
                            </div>
                        </div>
                        
                        <div class="sponsor-selection">
                            <label>Powered by Sponsor (Free):</label>
                            <div class="sponsor-options">
                                <?php foreach ($sponsors as $key => $sponsor): ?>
                                    <div class="sponsor-card <?php echo $key === 'la_croix' ? 'selected' : ''; ?>" data-sponsor="<?php echo $key; ?>">
                                        <div class="sponsor-icon">
                                            <img src="<?php echo $sponsor['image']; ?>" alt="<?php echo $sponsor['name']; ?>">
                                        </div>
                                        <span><?php echo $sponsor['name']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
    
                        <button type="submit" id="createBtn" class="cta-button">
                            <span class="btn-text">CREATE MAGIC</span>
                            <div class="loader hidden"></div>
                        </button>
                    </form>
                </div>
                
                <div id="resultsArea" class="hidden" style="margin-top: 2rem; text-align: center;">
                    <h3>Your Result</h3>
                    <div class="preview-strip" id="resultImageContainer"></div>
                    <div class="nav-buttons" style="justify-content: center; margin-top: 1rem;">
                        <a href="#" id="shareBtn" class="btn-secondary">🔗 Share</a>
                        <a href="/" class="btn-secondary">🏠 Home</a>
                    </div>
                </div>
            </section>

            <script>
            // Freeform Logic
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.getElementById('freeformForm');
                if (!form) return; // Only run if freeform form exists
                
                const createBtn = document.getElementById('createBtn');
                const resultsArea = document.getElementById('resultsArea');
                const resultImageContainer = document.getElementById('resultImageContainer');
                const shareBtn = document.getElementById('shareBtn');
                const imageInput = document.getElementById('imageInput');
                const imagePreview = document.getElementById('imagePreview');
                
                // Sponsor Selection
                const sponsorCards = document.querySelectorAll('.sponsor-card');
                let selectedSponsor = 'la_croix';
                sponsorCards.forEach(card => {
                    card.addEventListener('click', () => {
                        sponsorCards.forEach(c => c.classList.remove('selected'));
                        card.classList.add('selected');
                        selectedSponsor = card.dataset.sponsor;
                    });
                });

                // Image Preview
                imageInput.addEventListener('change', (e) => {
                    imagePreview.innerHTML = '';
                    imagePreview.classList.remove('hidden');
                    Array.from(e.target.files).forEach(file => {
                        const reader = new FileReader();
                        reader.onload = (ev) => {
                            const img = document.createElement('img');
                            img.src = ev.target.result;
                            img.className = 'preview-thumb';
                            imagePreview.appendChild(img);
                        };
                        reader.readAsDataURL(file);
                    });
                });

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    
                    const promptVal = document.getElementById('promptInput').value.trim();
                    const files = imageInput.files;
                    
                    if (!promptVal && files.length === 0) {
                        alert('Please provide a prompt or images.');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('prompt', promptVal);
                    formData.append('sponsor', selectedSponsor);
                    
                    // Handle legacy images[]
                    for (let i = 0; i < files.length; i++) {
                        formData.append('images[]', files[i]);
                    }
                    
                    // Handle remix images from URL if any (not implemented in this simple view, but API handles it)
                    
                    // UI Loading
                    createBtn.disabled = true;
                    createBtn.querySelector('.loader').classList.remove('hidden');
                    createBtn.querySelector('.btn-text').textContent = 'Creating Magic...';
                    resultsArea.classList.add('hidden');
                    resultImageContainer.innerHTML = '';

                    try {
                        const response = await fetch('api/create.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            resultsArea.classList.remove('hidden');
                            const img = document.createElement('img');
                            img.src = result.app.image_url;
                            img.className = 'preview-image';
                            resultImageContainer.appendChild(img);
                            shareBtn.href = result.app.share_url;
                            resultsArea.scrollIntoView({ behavior: 'smooth' });
                        } else {
                            alert('Generation failed: ' + (result.error || 'Unknown error'));
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Network error.');
                    } finally {
                        createBtn.disabled = false;
                        createBtn.querySelector('.loader').classList.add('hidden');
                        createBtn.querySelector('.btn-text').textContent = 'CREATE MAGIC';
                    }
                });
            });
            </script>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 ViralMagical.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/heic-to@1.3.0/dist/iife/heic-to.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('generateForm');
        const generateBtn = document.getElementById('generateBtn');
        const resultsArea = document.getElementById('resultsArea');
        const resultImageContainer = document.getElementById('resultImageContainer');
        const shareBtn = document.getElementById('shareBtn');
        
        // Store processed files: role => File object
        const processedFiles = {}; 

        // Sponsor Selection
        const sponsorCards = document.querySelectorAll('.sponsor-card');
        let selectedSponsor = 'la_croix';
        
        sponsorCards.forEach(card => {
            card.addEventListener('click', () => {
                sponsorCards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                selectedSponsor = card.dataset.sponsor;
            });
        });

        // Handle File Inputs (HEIC + Preview)
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            const role = input.dataset.role;
            const previewContainer = document.getElementById(`preview-${role}`);
            
            input.addEventListener('change', async (e) => {
                if (e.target.files && e.target.files[0]) {
                    const file = e.target.files[0];
                    
                    // Show loading or processing state if needed
                    // For now, just process
                    try {
                        let finalFile = file;

                        // HEIC Conversion
                        if (file.type === 'image/heic' || file.type === 'image/heif' || file.name.toLowerCase().endsWith('.heic')) {
                            if (window.HeicTo) {
                                const blob = await window.HeicTo({ blob: file, type: 'image/jpeg' });
                                finalFile = new File([blob], file.name.replace(/\.heic$/i, '.jpg'), { type: 'image/jpeg' });
                            }
                        }

                        // Compression
                        if (window.imageCompression) {
                            const options = { maxSizeMB: 1, maxWidthOrHeight: 2048, useWebWorker: true };
                            finalFile = await window.imageCompression(finalFile, options);
                        }

                        // Store processed file
                        processedFiles[role] = finalFile;

                        // Show Preview
                        const reader = new FileReader();
                        reader.onload = (ev) => {
                            previewContainer.innerHTML = '';
                            previewContainer.classList.remove('hidden');
                            
                            const wrapper = document.createElement('div');
                            wrapper.className = 'thumbnail-wrapper';
                            
                            const img = document.createElement('img');
                            img.src = ev.target.result;
                            
                            const removeBtn = document.createElement('button');
                            removeBtn.className = 'thumbnail-remove';
                            removeBtn.innerHTML = '×';
                            removeBtn.onclick = (evt) => {
                                evt.preventDefault();
                                evt.stopPropagation();
                                delete processedFiles[role];
                                input.value = ''; // Clear input
                                previewContainer.innerHTML = '';
                                previewContainer.classList.add('hidden');
                            };
                            
                            wrapper.appendChild(img);
                            wrapper.appendChild(removeBtn);
                            previewContainer.appendChild(wrapper);
                        };
                        reader.readAsDataURL(finalFile);

                    } catch (err) {
                        console.error("Error processing image:", err);
                        alert("Error processing image. Please try another one.");
                    }
                }
            });
        });

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                // Validate
                const inputs = document.querySelectorAll('.app-input');
                const formData = new FormData();
                
                formData.append('app_id', document.getElementById('appId').value);
                formData.append('sponsor', selectedSponsor);
                
                let hasError = false;
                
                inputs.forEach(input => {
                    const role = input.dataset.role;
                    if (input.type === 'file') {
                        // Check processed files first
                        if (processedFiles[role]) {
                            formData.append(role, processedFiles[role]);
                        } else if (input.files.length > 0) {
                            // Fallback to raw file if processing failed or skipped (shouldn't happen if we block)
                            formData.append(role, input.files[0]);
                        } else if (input.hasAttribute('required')) {
                            alert(`Please upload an image for ${role}`);
                            hasError = true;
                        }
                    } else {
                        const val = input.value.trim();
                        if (val) {
                            formData.append(role, val);
                        } else if (input.hasAttribute('required')) {
                            alert(`Please enter text for ${role}`);
                            hasError = true;
                        }
                    }
                });
                
                if (hasError) return;

                // UI Loading
                generateBtn.disabled = true;
                generateBtn.querySelector('.loader').classList.remove('hidden');
                generateBtn.querySelector('.btn-text').textContent = 'Creating Magic...';
                resultsArea.classList.add('hidden');
                resultImageContainer.innerHTML = '';

                try {
                    const response = await fetch('/api/create.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show result
                        resultsArea.classList.remove('hidden');
                        const img = document.createElement('img');
                        img.src = result.app.image_url;
                        img.className = 'preview-image';
                        resultImageContainer.appendChild(img);
                        
                        // Update share link
                        shareBtn.href = result.app.share_url;
                        
                        // Scroll to result
                        resultsArea.scrollIntoView({ behavior: 'smooth' });
                    } else {
                        alert('Generation failed: ' + (result.error || 'Unknown error'));
                    }
                } catch (e) {
                    console.error(e);
                    alert('Network error.');
                } finally {
                    generateBtn.disabled = false;
                    generateBtn.querySelector('.loader').classList.add('hidden');
                    generateBtn.querySelector('.btn-text').textContent = '✨ GENERATE';
                }
            });
        }
    });
    </script>
</body>
</html>
