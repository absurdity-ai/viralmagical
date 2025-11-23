<?php
require_once 'app_config.php';
require_once 'sponsor_config.php';

$app_id = $_GET['app'] ?? null;
$current_app = $app_id ? ($image_apps[$app_id] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ViralMagical - <?php echo $current_app ? $current_app['name'] : 'AI Image Playground'; ?></title>
    <meta name="description" content="ViralMagical is a 100% free, truly democratic AI creativity platform.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
    <style>
        .app-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            padding: 2rem 0;
        }
        .app-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            transition: transform 0.2s, background 0.2s;
            text-decoration: none;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .app-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.1);
        }
        .app-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .app-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .app-desc {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 1.5rem;
            line-height: 1.4;
        }
        .app-btn {
            margin-top: auto;
            background: linear-gradient(45deg, #ff0055, #0055ff);
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            width: 100%;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
        }
        .back-link:hover {
            color: white;
        }
        .slot-guide {
            background: rgba(255,255,255,0.05);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: left;
        }
        .slot-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .slot-num {
            background: rgba(255,255,255,0.2);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
            font-size: 0.8rem;
            font-weight: bold;
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
            <span class="vm-logo">VM</span> ViralMagical
        </div>
        <nav>
            <a href="/" class="nav-link">Apps</a>
            <a href="/create-app" class="nav-link">Advanced</a>
        </nav>
    </header>

    <main>
        <?php if ($current_app): ?>
            <!-- APP CREATION MODE -->
            <section class="hero">
                <a href="/" class="back-link">← Back to Apps</a>
                <h1><?php echo htmlspecialchars($current_app['name']); ?></h1>
                <p class="subtitle"><?php echo htmlspecialchars($current_app['description']); ?></p>
                
                <div class="creator-interface glass-panel">
                    <input type="hidden" id="appId" value="<?php echo htmlspecialchars($current_app['id']); ?>">
                    
                    <div class="input-row">
                        <div class="input-group" style="flex: 1;">
                            <div class="slot-guide">
                                <p style="margin-bottom:0.5rem; font-weight:600;">How to use:</p>
                                <?php foreach ($current_app['inputs'] as $input): ?>
                                    <div class="slot-item">
                                        <span class="slot-num"><?php echo $input['slot']; ?></span>
                                        <span><?php echo htmlspecialchars($input['label']); ?> <?php echo $input['required'] ? '*' : '(optional)'; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <!-- Optional Vibe/Details Input -->
                            <textarea id="promptInput" placeholder="Optional: Add extra details, vibe, or style instructions..." style="height: 80px;"></textarea>
                        </div>

                        <div class="image-upload-section" style="flex: 1;">
                            <label class="upload-label">
                                <span class="icon">📷</span> Upload Images (Select in order)
                                <input type="file" id="imageInput" accept="image/png,image/jpeg,image/jpg,image/webp,.heic,.heif" multiple hidden>
                            </label>
                            <div id="imagePreview" class="image-preview-grid hidden"></div>
                            <p style="font-size: 0.8rem; opacity: 0.6; margin-top: 0.5rem; text-align: center;">
                                Please upload images in the order listed on the left.
                            </p>
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

                    <button id="createBtn" class="cta-button">
                        <span class="btn-text">CREATE MAGIC</span>
                        <div class="loader hidden"></div>
                    </button>
                </div>
            </section>

        <?php else: ?>
            <!-- GALLERY MODE -->
            <section class="hero" style="min-height: auto; padding-bottom: 2rem;">
                <h1>Viral Image Apps<br><span class="highlight">Create with one click.</span><span class="highlight">
You choose the sponsor.</span><span class="highlight">
Free forever.</span></h1>
                <p class="subtitle">Pick an app. No prompting. You own everything you create.</p>
                
                <div class="app-grid">
                    <?php foreach ($image_apps as $app): ?>
                        <a href="/load/<?php echo $app['id']; ?>" class="app-card">
                            <div class="app-icon"><?php echo $app['icon']; ?></div>
                            <div class="app-name"><?php echo htmlspecialchars($app['name']); ?></div>
                            <div class="app-desc"><?php echo htmlspecialchars($app['description']); ?></div>
                            <button class="app-btn">Create</button>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 3rem; text-align: center;">
                    <a href="/create-app" style="color: rgba(255,255,255,0.5); text-decoration: none; border-bottom: 1px dotted rgba(255,255,255,0.3); padding-bottom: 2px;">
                        ✨ Advanced: build your own app / custom JSON
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <?php include 'includes/gallery_section.php'; ?>
    </main>

    <!-- Creation Progress Modal (Same as before) -->
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
    <script src="/script.js?v=<?php echo filemtime('script.js'); ?>"></script>
</body>
</html>
