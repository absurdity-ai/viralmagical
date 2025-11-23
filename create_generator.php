<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ViralMagical - Create Generator</title>
    <meta name="description" content="Forge new AI apps. Define the format, inputs, and logic.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="/style-generator.css">
</head>
<body>
    <div class="background-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <header>
        <div class="logo">
            <span class="vm-logo">VM</span> <span class="logo-text">Generator Forge</span>
        </div>
        <nav>
            <a href="index.php" class="nav-link">Back to Apps</a>
        </nav>
    </header>

    <main class="generator-main">
        <section class="hero-compact">
            <h1>Forge a New App</h1>
            <p class="subtitle">Define the rules. Create the format. Own the logic.</p>
        </section>

        <div class="generator-container glass-panel">
            <!-- Step Indicators -->
            <div class="steps-indicator">
                <div class="step active" data-step="1">1. Format</div>
                <div class="step" data-step="2">2. Inputs</div>
                <div class="step" data-step="3">3. Mapping</div>
                <div class="step" data-step="4">4. Preview</div>
                <div class="step" data-step="5">5. Publish</div>
            </div>

            <!-- STEP 1: FORMAT -->
            <div class="step-content active" id="step-1">
                <div class="section-header">
                    <h2>Step 1: The Format</h2>
                    <p>Define the skeleton of your app. How many outputs? What are they?</p>
                </div>
                
                <div class="form-group">
                    <label>App Name</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="appName" placeholder="e.g., Cyberpunk ID Card, 3-Panel Comic...">
                        <button id="magicFillBtn" class="btn-secondary" style="white-space: nowrap;">✨ Magic Auto-Fill</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <input type="text" id="appDescription" placeholder="What does this app do?">
                </div>

                <div class="panels-editor">
                    <h3>Output Panels / Sections</h3>
                    <div id="panelsContainer">
                        <!-- Dynamic Panels will be added here -->
                        <div class="panel-item" data-id="1">
                            <span class="panel-number">1</span>
                            <input type="text" class="panel-name" placeholder="Section Name (e.g., Setup, Avatar)" value="Panel 1">
                            <button class="btn-icon remove-panel" disabled>×</button>
                        </div>
                    </div>
                    <button id="addPanelBtn" class="btn-secondary">+ Add Panel</button>
                </div>

                <div class="nav-buttons">
                    <button class="btn-primary next-step" data-next="2">Next: Define Inputs →</button>
                </div>
            </div>

            <!-- STEP 2: INPUTS -->
            <div class="step-content hidden" id="step-2">
                <div class="section-header">
                    <h2>Step 2: The Inputs</h2>
                    <p>What does the user need to provide?</p>
                </div>

                <div class="inputs-editor">
                    <div id="inputsContainer">
                        <!-- Dynamic Inputs -->
                        <div class="input-item" data-id="1">
                            <div class="input-header">
                                <span class="input-number">Input 1</span>
                                <button class="btn-icon remove-input" disabled>×</button>
                            </div>
                            <div class="input-row">
                                <input type="text" class="input-role" placeholder="Role ID (e.g., character_image)" value="character">
                                <input type="text" class="input-label" placeholder="User Label (e.g., Upload Character)" value="Character Image">
                            </div>
                            <div class="input-row">
                                <select class="input-type">
                                    <option value="image">Image</option>
                                    <option value="text">Text</option>
                                </select>
                                <label class="checkbox-label">
                                    <input type="checkbox" class="input-required" checked> Required
                                </label>
                            </div>
                        </div>
                    </div>
                    <button id="addInputBtn" class="btn-secondary">+ Add Input</button>
                </div>

                <div class="nav-buttons">
                    <button class="btn-secondary prev-step" data-prev="1">← Back</button>
                    <button class="btn-primary next-step" data-next="3">Next: Map Logic →</button>
                </div>
            </div>

            <!-- STEP 3: MAPPING -->
            <div class="step-content hidden" id="step-3">
                <div class="section-header">
                    <h2>Step 3: The Logic</h2>
                    <p>Map your Inputs to your Format sections. This is the "Recipe".</p>
                </div>

                <div id="mappingContainer">
                    <!-- Dynamic Mapping Fields based on Panels -->
                </div>

                <div class="nav-buttons">
                    <button class="btn-secondary prev-step" data-prev="2">← Back</button>
                    <button class="btn-primary next-step" data-next="4">Next: Preview →</button>
                </div>
            </div>

            <!-- STEP 4: PREVIEW (NEW) -->
            <div class="step-content hidden" id="step-4">
                <div class="section-header">
                    <h2>Step 4: Preview Your Generator</h2>
                    <p>Test semantic consistency before publishing.</p>
                </div>

                <div class="preview-config">
                    <div id="previewInputsContainer">
                        <!-- Dynamic Preview Inputs will be rendered here -->
                    </div>
                    <button id="generatePreviewBtn" class="btn-primary cta-button">
                        <span class="btn-text">Generate Preview</span>
                        <div class="loader hidden"></div>
                    </button>
                </div>

                <div id="previewResults" class="hidden">
                    <h3>Preview Results</h3>
                    <div class="preview-strip" id="previewStrip">
                        <!-- Images go here -->
                    </div>
                    
                    <div class="rating-panel">
                        <h4>How did this generator perform?</h4>
                        <div class="checklist">
                            <label><input type="checkbox"> Identity drift: character changed</label>
                            <label><input type="checkbox"> Style drift: art not consistent</label>
                            <label><input type="checkbox"> Caption mismatch: tone incorrect</label>
                            <label><input type="checkbox"> Looks correct</label>
                        </div>
                    </div>
                </div>

                <div class="nav-buttons">
                    <button class="btn-secondary prev-step" data-prev="3">← Back</button>
                    <button class="btn-primary next-step" data-next="5">Next: Publish →</button>
                </div>
            </div>

             <!-- STEP 5: PUBLISH -->
             <div class="step-content hidden" id="step-5">
                <div class="section-header">
                    <h2>Step 5: Publish</h2>
                    <p>Review your app and launch it.</p>
                </div>

                <div class="review-card">
                    <h3 id="reviewAppName">App Name</h3>
                    <p id="reviewAppDesc">Description</p>
                    <div class="review-grid">
                        <div class="review-section">
                            <h4>Format</h4>
                            <ul id="reviewFormat"></ul>
                        </div>
                        <div class="review-section">
                            <h4>Inputs</h4>
                            <ul id="reviewInputs"></ul>
                        </div>
                    </div>
                    <div class="review-json">
                        <h4>Generated Recipe (JSON)</h4>
                        <pre id="reviewJson"></pre>
                    </div>
                </div>

                <div class="nav-buttons">
                    <button class="btn-secondary prev-step" data-prev="4">← Back</button>
                    <button id="publishBtn" class="btn-primary cta-button">
                        <span class="btn-text">🚀 PUBLISH APP</span>
                        <div class="loader hidden"></div>
                    </button>
                </div>
            </div>

        </div>
    </main>

    <footer>
        <p>&copy; 2025 ViralMagical. Forge Mode.</p>
    </footer>

    <script src="/script-generator.js"></script>
</body>
</html>
